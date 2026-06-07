<?php

namespace App\Filament\Widgets;

use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\StripePayment;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\UserEntitlement;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $premiumUsers = UserEntitlement::query()
            ->where('key', UserEntitlement::KEY_PREMIUM)
            ->where('active', true)
            ->distinct('user_id')
            ->count('user_id');

        $totalUsers = User::query()->count();
        $paidRevenue = StripePayment::query()
            ->where('status', StripePayment::STATUS_PAID)
            ->sum('amount_total');

        return [
            Stat::make('Total Users', number_format($totalUsers))
                ->description('Registered accounts'),
            Stat::make('Premium Users', number_format($premiumUsers))
                ->description($totalUsers > 0 ? round(($premiumUsers / $totalUsers) * 100, 1).'% conversion' : '0% conversion')
                ->color('success'),
            Stat::make('Total Lists', number_format(DuaList::query()->count()))
                ->description(DuaList::query()->active()->count().' active / '.DuaList::query()->archived()->count().' archived'),
            Stat::make('Submissions', number_format(DuaSubmission::query()->count()))
                ->description(DuaSubmission::query()->where('status', DuaSubmission::STATUS_COMPLETED)->count().' completed'),
            Stat::make('Moderation Alerts', number_format(DuaSubmission::query()->whereIn('status', [
                DuaSubmission::STATUS_REPORTED,
                DuaSubmission::STATUS_HIDDEN,
            ])->count()))
                ->description('Reported and hidden duas')
                ->color('warning'),
            Stat::make('Paid Revenue', '$'.number_format(((int) $paidRevenue) / 100, 2))
                ->description('Stripe paid checkout total')
                ->color('success'),
            Stat::make('Support Tickets', number_format(SupportTicket::query()->count()))
                ->description(SupportTicket::query()->where('created_at', '>=', now()->subDays(7))->count().' opened in 7 days'),
        ];
    }
}
