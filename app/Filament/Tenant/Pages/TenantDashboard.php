<?php

namespace App\Filament\Tenant\Pages;

use App\Filament\Tenant\Widgets\QuickStartWidget;
use App\Filament\Tenant\Widgets\TenantMeetingStatsWidget;
use Filament\Pages\Dashboard;
use Filament\Widgets\AccountWidget;

class TenantDashboard extends Dashboard
{
    protected static ?string $navigationLabel                = 'Home';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';
    protected static ?int    $navigationSort                 = -2;

    protected static string $routePath = '/';

    public function getWidgets(): array
    {
        return [
            QuickStartWidget::class,
            TenantMeetingStatsWidget::class,
            AccountWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
