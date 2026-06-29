<?php

namespace App\Filament\Tenant\Widgets;

use App\Services\AnalyticsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TenantMeetingStatsWidget extends StatsOverviewWidget
{
    protected static ?int  $sort    = 1;
    protected ?string      $heading = 'Meeting Overview';

    protected function getStats(): array
    {
        $tenantId = auth()->user()->tenant_id;
        $svc      = app(AnalyticsService::class);

        $meetings    = $svc->tenantMeetingStats($tenantId);
        $attendance  = $svc->tenantAttendanceStats($tenantId);
        $resolutions = $svc->tenantResolutionStats($tenantId);
        $actions     = $svc->tenantActionItemStats($tenantId);
        $docs        = $svc->tenantDocumentStats($tenantId);

        return [
            Stat::make('Total Meetings', $meetings['total'])
                ->description($meetings['this_year'] . ' this year')
                ->icon('heroicon-o-calendar'),

            Stat::make('Attendance Rate', $attendance['attendance_rate'] . '%')
                ->description('RSVP response: ' . $attendance['rsvp_response_rate'] . '%')
                ->icon('heroicon-o-user-group')
                ->color($attendance['attendance_rate'] >= 75 ? 'success' : 'warning'),

            Stat::make('Resolution Pass Rate', $resolutions['pass_rate'] . '%')
                ->description($resolutions['passed'] . ' passed of ' . $resolutions['total'])
                ->icon('heroicon-o-check-badge')
                ->color($resolutions['pass_rate'] >= 60 ? 'success' : 'warning'),

            Stat::make('Action Items', $actions['total'])
                ->description($actions['overdue'] . ' overdue · ' . $actions['completion_rate'] . '% done')
                ->icon('heroicon-o-clipboard-document-list')
                ->color($actions['overdue'] > 0 ? 'danger' : 'success'),

            Stat::make('Documents', $docs['total'])
                ->description($docs['size_human'] . ' stored')
                ->icon('heroicon-o-document'),
        ];
    }
}
