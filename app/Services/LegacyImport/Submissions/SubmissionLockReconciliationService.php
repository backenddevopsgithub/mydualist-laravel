<?php

namespace App\Services\LegacyImport\Submissions;

use App\Domains\Billing\Fulfillment\SubmissionUnlockService;
use App\Domains\Billing\Services\EntitlementResolverService;
use App\Domains\Billing\Services\ListSubmissionQuotaService;
use App\Enums\BillingProductCode;
use App\Enums\SubmissionLockReason;
use App\Models\BillingPurchase;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Services\Service;
use Illuminate\Support\Collection;

class SubmissionLockReconciliationService extends Service
{
    public function __construct(
        private readonly EntitlementResolverService $entitlements,
        private readonly ListSubmissionQuotaService $quota,
        private readonly SubmissionUnlockService $unlocker,
    ) {}

    /**
     * @return array{
     *     lists_processed: int,
     *     submissions_updated: int,
     *     mismatches: list<array<string, mixed>>,
     *     unlocks_replayed: int,
     * }
     */
    public function reconcile(bool $dryRun = false): array
    {
        $result = [
            'lists_processed' => 0,
            'submissions_updated' => 0,
            'mismatches' => [],
            'unlocks_replayed' => 0,
        ];

        DuaList::query()
            ->with('user')
            ->orderBy('id')
            ->chunkById(50, function (Collection $lists) use (&$result, $dryRun): void {
                foreach ($lists as $list) {
                    if ($list->user === null) {
                        continue;
                    }

                    $result['lists_processed']++;
                    $reconcile = $this->reconcileList($list, $dryRun);
                    $result['submissions_updated'] += $reconcile['updated'];
                    $result['mismatches'] = array_merge($result['mismatches'], $reconcile['mismatches']);
                }
            });

        if (! $dryRun) {
            $result['unlocks_replayed'] = $this->replayPurchaseUnlocks();
        }

        return $result;
    }

    /**
     * @return array{updated: int, mismatches: list<array<string, mixed>>}
     */
    private function reconcileList(DuaList $list, bool $dryRun): array
    {
        $owner = $list->user;
        $quota = $this->entitlements->effectiveVisibleQuota($owner, $list);
        $hasUnlimited = $this->entitlements->hasListUnlimitedOverride($owner, $list);

        $submissions = DuaSubmission::query()
            ->where('dua_list_id', $list->id)
            ->orderBy('id')
            ->get();

        $updated = 0;
        $mismatches = [];
        $regularRank = 0;

        foreach ($submissions as $submission) {
            if ($submission->is_personal_dua) {
                $expectedLocked = false;
                $expectedReason = null;
                $expectedQuota = null;
            } elseif ($hasUnlimited) {
                $expectedLocked = false;
                $expectedReason = null;
                $expectedQuota = null;
            } elseif ($submission->unlocked_at !== null) {
                $expectedLocked = false;
                $expectedReason = null;
                $expectedQuota = null;
            } else {
                $regularRank++;
                $expectedLocked = $regularRank > $quota;
                $expectedReason = $expectedLocked ? SubmissionLockReason::VisibleQuotaExhausted : null;
                $expectedQuota = $expectedLocked ? $quota : null;
            }

            $legacyMismatch = $submission->is_locked !== $expectedLocked
                && $submission->unlocked_at === null;

            if ($legacyMismatch) {
                $mismatches[] = [
                    'dua_list_id' => $list->id,
                    'wp_post_id' => $submission->wp_post_id,
                    'submission_id' => $submission->id,
                    'legacy_is_locked' => $submission->is_locked,
                    'expected_is_locked' => $expectedLocked,
                    'quota' => $quota,
                    'regular_rank' => $submission->is_personal_dua ? null : $regularRank,
                ];
            }

            if ($dryRun) {
                continue;
            }

            if ($submission->is_personal_dua || $hasUnlimited) {
                if ($submission->is_locked || $submission->locked_reason !== null) {
                    $submission->forceFill([
                        'is_locked' => false,
                        'locked_reason' => null,
                        'locked_at_quota' => null,
                    ])->save();
                    $updated++;
                }

                continue;
            }

            if ($submission->unlocked_at !== null) {
                continue;
            }

            $attributes = [
                'is_locked' => $expectedLocked,
                'locked_reason' => $expectedReason,
                'locked_at_quota' => $expectedQuota,
            ];

            if ($submission->is_locked !== $expectedLocked
                || $submission->locked_reason !== $expectedReason
                || $submission->locked_at_quota !== $expectedQuota) {
                $submission->forceFill($attributes)->save();
                $updated++;
            }
        }

        $lockedPersisted = $submissions
            ->where('is_personal_dua', false)
            ->where('is_locked', true)
            ->whereNull('unlocked_at')
            ->count();

        $expectedLockedCount = max(0, $submissions->where('is_personal_dua', false)->count() - $quota - $submissions->where('unlocked_at', '!=', null)->count());

        $inspection = $this->quota->inspect($owner, $list);

        if ($inspection['exceeds']) {
            $mismatches[] = [
                'dua_list_id' => $list->id,
                'type' => 'visible_exceeds_quota',
                'visible' => $inspection['visible'],
                'quota' => $inspection['quota'],
                'locked_persisted' => $lockedPersisted,
                'expected_locked_count' => $expectedLockedCount,
            ];
        }

        return ['updated' => $updated, 'mismatches' => $mismatches];
    }

    private function replayPurchaseUnlocks(): int
    {
        $unlocked = 0;

        BillingPurchase::query()
            ->with('product')
            ->whereNotNull('fulfilled_at')
            ->orderBy('fulfilled_at')
            ->orderBy('id')
            ->chunkById(100, function (Collection $purchases) use (&$unlocked): void {
                foreach ($purchases as $purchase) {
                    $productCode = BillingProductCode::tryFrom((string) optional($purchase->product)->code);

                    if ($productCode === null) {
                        continue;
                    }

                    $unlocked += match ($productCode) {
                        BillingProductCode::RequestPack25 => $this->unlocker->unlockEligibleForList(
                            $purchase,
                            (int) $purchase->dua_list_id,
                            (int) config('billing.request_pack_unlock_batch'),
                        ),
                        BillingProductCode::UnlimitedOneList => $this->unlocker->unlockEligibleForList(
                            $purchase,
                            (int) $purchase->dua_list_id,
                        ),
                        BillingProductCode::UnlimitedForever => $this->unlocker->unlockEligibleForUserLists(
                            $purchase,
                            $purchase->user,
                        ),
                        default => 0,
                    };
                }
            });

        return $unlocked;
    }
}
