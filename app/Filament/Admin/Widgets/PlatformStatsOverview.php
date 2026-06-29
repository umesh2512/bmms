<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Tenant;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalTenants  = Tenant::count();
        $activeTenants = Tenant::where('status', 'active')->count();
        $trialTenants  = Tenant::where('status', 'trial')->count();
        $totalUsers    = User::withoutGlobalScope('tenant')->where('status', '!=', 'deactivated')->count();
        $activeUsers   = User::withoutGlobalScope('tenant')->where('status', 'active')->count();
        $invitedUsers  = User::withoutGlobalScope('tenant')->where('status', 'invited')->count();

        return [
            Stat::make('Total Organisations', $totalTenants)
                ->description("{$activeTenants} active · {$trialTenants} on trial")
                ->color('primary'),

            Stat::make('Active Organisations', $activeTenants)
                ->description(($totalTenants - $activeTenants) . ' inactive / suspended / cancelled')
                ->color('success'),

            Stat::make('Total Users', $totalUsers)
                ->description("{$activeUsers} active · {$invitedUsers} pending invitation")
                ->color('info'),
        ];
    }
}
