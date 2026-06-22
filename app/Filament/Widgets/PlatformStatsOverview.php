<?php

namespace App\Filament\Widgets;

use App\Enums\BillingPurchaseStatus;
use App\Enums\EntitlementKey;
use App\Models\BillingPurchase;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\UserEntitlement;
use App\Services\AdminDashboardCacheService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class PlatformStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $data = app(AdminDashboardCacheService::class)->remember(
            'platform_stats',
            fn (): array => $this->platformStatsData(),
        );

        return $this->statsFromData($data);
    }

    /**
     * @return array{
     *     premium_users: int,
     *     total_users: int,
     *     paid_revenue: int,
     *     total_lists: int,
     *     active_lists: int,
     *     archived_lists: int,
     *     total_submissions: int,
     *     completed_submissions: int,
     *     moderation_alerts: int,
     *     support_tickets: int,
     *     support_tickets_last_7_days: int,
     * }
     */
    private function platformStatsData(): array
    {
        $listStats = DuaList::query()
            ->selectRaw(
                'COUNT(*) as total_lists,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as active_lists,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as archived_lists',
                [DuaList::STATUS_ACTIVE, DuaList::STATUS_ARCHIVED],
            )
            ->first();

        $submissionStats = DuaSubmission::query()
            ->selectRaw(
                'COUNT(*) as total_submissions,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed_submissions,
                SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as moderation_alerts',
                [
                    DuaSubmission::STATUS_COMPLETED,
                    DuaSubmission::STATUS_REPORTED,
                    DuaSubmission::STATUS_HIDDEN,
                ],
            )
            ->first();

        return [
            'premium_users' => User::query()
                ->where(function (Builder $query): void {
                    $query->whereHas('entitlementGrants', fn (Builder $grantQuery): Builder => $grantQuery
                        ->where('entitlement_key', EntitlementKey::UserUnlimitedForever)
                        ->where(function (Builder $expiryQuery): void {
                            $expiryQuery->whereNull('expires_at')->orWhere('expires_at', '>', now());
                        }))
                        ->orWhereHas('entitlements', fn (Builder $legacyQuery): Builder => $legacyQuery
                            ->where('key', UserEntitlement::KEY_PREMIUM)
                            ->where('active', true)
                            ->where(function (Builder $expiryQuery): void {
                                $expiryQuery->whereNull('expires_at')->orWhere('expires_at', '>', now());
                            }));
                })
                ->count(),
            'total_users' => User::query()->count(),
            'paid_revenue' => (int) BillingPurchase::query()
                ->where('status', BillingPurchaseStatus::Succeeded)
                ->sum('amount_minor'),
            'total_lists' => (int) ($listStats->total_lists ?? 0),
            'active_lists' => (int) ($listStats->active_lists ?? 0),
            'archived_lists' => (int) ($listStats->archived_lists ?? 0),
            'total_submissions' => (int) ($submissionStats->total_submissions ?? 0),
            'completed_submissions' => (int) ($submissionStats->completed_submissions ?? 0),
            'moderation_alerts' => (int) ($submissionStats->moderation_alerts ?? 0),
            'support_tickets' => SupportTicket::query()->count(),
            'support_tickets_last_7_days' => SupportTicket::query()
                ->where('created_at', '>=', now()->subDays(7))
                ->count(),
        ];
    }

    /**
     * @param  array{
     *     premium_users: int,
     *     total_users: int,
     *     paid_revenue: int,
     *     total_lists: int,
     *     active_lists: int,
     *     archived_lists: int,
     *     total_submissions: int,
     *     completed_submissions: int,
     *     moderation_alerts: int,
     *     support_tickets: int,
     *     support_tickets_last_7_days: int,
     * }  $data
     * @return list<Stat>
     */
    private function statsFromData(array $data): array
    {
        $totalUsers = $data['total_users'];

        return [
            Stat::make('Total Users', number_format($totalUsers))
                ->description('Registered accounts'),
            Stat::make('Premium Users', number_format($data['premium_users']))
                ->description($totalUsers > 0 ? round(($data['premium_users'] / $totalUsers) * 100, 1).'% conversion' : '0% conversion')
                ->color('success'),
            Stat::make('Total Lists', number_format($data['total_lists']))
                ->description($data['active_lists'].' active / '.$data['archived_lists'].' archived'),
            Stat::make('Submissions', number_format($data['total_submissions']))
                ->description($data['completed_submissions'].' completed'),
            Stat::make('Moderation Alerts', number_format($data['moderation_alerts']))
                ->description('Reported and hidden duas')
                ->color('warning'),
            Stat::make('Paid Revenue', '$'.number_format($data['paid_revenue'] / 100, 2))
                ->description('Succeeded billing purchases')
                ->color('success'),
            Stat::make('Support Tickets', number_format($data['support_tickets']))
                ->description($data['support_tickets_last_7_days'].' opened in 7 days'),
        ];
    }
}
