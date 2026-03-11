<?php

namespace App\Filament\Widgets;

use App\Models\Vendor;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalRevenue = Payment::completed()->sum('total_amount');
        $monthlyRevenue = Payment::completed()
            ->paidBetween(now()->startOfMonth(), now()->endOfMonth())
            ->sum('total_amount');

        return [
            Stat::make('Total Vendors', Vendor::count())
                ->description(Vendor::where('status', 'pending')->count() . ' pending approval')
                ->descriptionIcon('heroicon-m-clock')
                ->descriptionColor('warning')
                ->color('primary')
                ->icon('heroicon-o-building-storefront'),

            Stat::make('Active Users', User::where('status', 'active')->count())
                ->description(User::where('status', 'suspended')->count() . ' suspended')
                ->descriptionIcon('heroicon-m-no-symbol')
                ->descriptionColor('danger')
                ->color('success')
                ->icon('heroicon-o-users'),

            Stat::make('Active Subscriptions', Subscription::whereIn('status', ['active', 'trialing'])->count())
                ->description(Subscription::expiringSoon(7)->count() . ' expiring soon')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->descriptionColor('warning')
                ->color('info')
                ->icon('heroicon-o-arrow-path-rounded-square'),

            Stat::make('Total Revenue', '$' . number_format($totalRevenue, 2))
                ->description('+$' . number_format($monthlyRevenue, 2) . ' this month')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->descriptionColor('success')
                ->color('success')
                ->icon('heroicon-o-banknotes'),
        ];
    }
}
