<?php

namespace App\Filament\Tenant\Pages;

use App\Filament\Tenant\Widgets\ActionItemStatusChart;
use App\Filament\Tenant\Widgets\AttendanceRateChart;
use App\Filament\Tenant\Widgets\MeetingsByMonthChart;
use App\Filament\Tenant\Widgets\ResolutionOutcomeChart;
use App\Filament\Tenant\Widgets\TenantMeetingStatsWidget;
use Filament\Actions\Action;
use Filament\Pages\Dashboard;

class AnalyticsDashboard extends Dashboard
{
    protected static ?string $navigationLabel                = 'Analytics';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';
    protected static string|\UnitEnum|null $navigationGroup  = 'Reports';
    protected static ?int    $navigationSort                 = 40;

    protected static string $routePath = 'analytics';

    public function getWidgets(): array
    {
        return [
            TenantMeetingStatsWidget::class,
            MeetingsByMonthChart::class,
            AttendanceRateChart::class,
            ResolutionOutcomeChart::class,
            ActionItemStatusChart::class,
        ];
    }

    public function getColumns(): int|array
    {
        return ['default' => 1, 'xl' => 2];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_csv')
                ->label('Export Meetings CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(route('analytics.export.meetings.csv'))
                ->openUrlInNewTab(),

            Action::make('export_actions_csv')
                ->label('Export Actions CSV')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('gray')
                ->url(route('analytics.export.actions.csv'))
                ->openUrlInNewTab(),

            Action::make('export_pdf')
                ->label('Governance Report PDF')
                ->icon('heroicon-o-document-text')
                ->color('primary')
                ->url(route('analytics.export.governance-pdf'))
                ->openUrlInNewTab(),
        ];
    }
}
