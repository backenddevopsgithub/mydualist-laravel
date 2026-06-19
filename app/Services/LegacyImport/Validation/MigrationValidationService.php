<?php

namespace App\Services\LegacyImport\Validation;

use App\Domains\Billing\Services\EntitlementResolverService;
use App\Domains\Billing\Services\ListSubmissionQuotaService;
use App\Enums\BillingPurchaseStatus;
use App\Models\BillingPurchase;
use App\Models\CommunityDua;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\DuaSuggestion;
use App\Models\EntitlementGrant;
use App\Models\User;
use App\Services\LegacyImport\LegacyImportReport;
use App\Services\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MigrationValidationService extends Service
{
    public function __construct(
        private readonly EntitlementResolverService $entitlements,
        private readonly ListSubmissionQuotaService $quota,
    ) {}

    public function validate(): LegacyImportReport
    {
        $report = new LegacyImportReport('validate');

        $totals = [
            'users' => User::query()->count(),
            'users_with_wp_legacy_id' => User::query()->whereNotNull('wp_legacy_id')->count(),
            'lists' => DuaList::query()->count(),
            'lists_with_wp_post_id' => DuaList::query()->whereNotNull('wp_post_id')->count(),
            'submissions' => DuaSubmission::query()->count(),
            'submissions_with_wp_post_id' => DuaSubmission::query()->whereNotNull('wp_post_id')->count(),
            'suggestions' => DuaSuggestion::query()->count(),
            'community_duas' => CommunityDua::query()->count(),
            'purchases' => BillingPurchase::query()->count(),
            'purchases_with_wp_order_id' => BillingPurchase::query()->whereNotNull('wp_order_id')->count(),
            'entitlement_grants' => EntitlementGrant::query()->count(),
            'locked_submissions' => DuaSubmission::query()->quotaLocked()->count(),
        ];

        $failures = [];
        $warnings = [];
        $mismatches = [];

        $orphanSubmissions = DuaSubmission::query()
            ->whereNotIn('dua_list_id', DuaList::query()->select('id'))
            ->count();

        if ($orphanSubmissions > 0) {
            $failures[] = [
                'type' => 'orphan_submissions',
                'count' => $orphanSubmissions,
            ];
        }

        $orphanLists = DuaList::query()
            ->whereNotIn('user_id', User::query()->select('id'))
            ->count();

        if ($orphanLists > 0) {
            $failures[] = [
                'type' => 'orphan_lists',
                'count' => $orphanLists,
            ];
        }

        $duplicateSlugs = DuaList::query()
            ->select('slug', DB::raw('COUNT(*) as total'))
            ->groupBy('slug')
            ->having('total', '>', 1)
            ->pluck('total', 'slug');

        foreach ($duplicateSlugs as $slug => $count) {
            $failures[] = [
                'type' => 'duplicate_slug',
                'slug' => $slug,
                'count' => (int) $count,
            ];
        }

        DuaList::query()
            ->with('user')
            ->whereNotNull('cover_image_path')
            ->chunkById(100, function ($lists) use (&$warnings): void {
                foreach ($lists as $list) {
                    if ($list->cover_image_path && ! Storage::disk('public')->exists($list->cover_image_path)) {
                        $warnings[] = [
                            'type' => 'missing_cover_image',
                            'dua_list_id' => $list->id,
                            'wp_post_id' => $list->wp_post_id,
                            'path' => $list->cover_image_path,
                        ];
                    }
                }
            });

        $unfulfilledPurchases = BillingPurchase::query()
            ->whereNull('fulfilled_at')
            ->where('status', BillingPurchaseStatus::Succeeded)
            ->count();

        if ($unfulfilledPurchases > 0) {
            $warnings[] = [
                'type' => 'unfulfilled_purchases',
                'count' => $unfulfilledPurchases,
            ];
        }

        DuaList::query()
            ->with('user')
            ->chunkById(50, function ($lists) use (&$mismatches): void {
                foreach ($lists as $list) {
                    if ($list->user === null) {
                        continue;
                    }

                    $inspection = $this->quota->inspect($list->user, $list);

                    if ($inspection['exceeds']) {
                        $mismatches[] = [
                            'type' => 'visible_exceeds_quota',
                            'dua_list_id' => $list->id,
                            'wp_post_id' => $list->wp_post_id,
                            'visible' => $inspection['visible'],
                            'quota' => $inspection['quota'],
                        ];
                    }

                    $lockedCount = $this->entitlements->lockedSubmissionCount($list->user, $list);
                    $persistedLocked = DuaSubmission::query()
                        ->where('dua_list_id', $list->id)
                        ->quotaLocked()
                        ->count();

                    if ($lockedCount !== $persistedLocked) {
                        $mismatches[] = [
                            'type' => 'locked_count_mismatch',
                            'dua_list_id' => $list->id,
                            'expected' => $lockedCount,
                            'persisted' => $persistedLocked,
                        ];
                    }
                }
            });

        $grantsWithoutPurchase = EntitlementGrant::query()
            ->whereNull('source_purchase_id')
            ->whereNotNull('dedupe_key')
            ->count();

        if ($grantsWithoutPurchase > 0) {
            $warnings[] = [
                'type' => 'entitlement_grants_without_purchase',
                'count' => $grantsWithoutPurchase,
            ];
        }

        $report->validation = [
            'totals' => $totals,
            'failures' => $failures,
            'warnings' => $warnings,
            'mismatches' => $mismatches,
            'passed' => $failures === [] && $mismatches === [],
        ];

        return $report;
    }
}
