<?php

namespace App\Filament\Tenant\Widgets;

use App\Services\AnalyticsService;
use Filament\Widgets\ChartWidget;

class AttendanceRateChart extends ChartWidget
{
    protected static ?int $sort  = 3;
    protected ?string $heading   = 'Attendance Rate (Last 10 Meetings)';
    protected ?string $maxHeight = '280px';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $tenantId = auth()->user()->tenant_id;
        $result   = app(AnalyticsService::class)->tenantAttendanceByMeeting($tenantId, 10);

        return [
            'labels'   => $result['labels'],
            'datasets' => [
                [
                    'label'           => 'Attendance %',
                    'data'            => $result['data'],
                    'borderColor'     => '#087f83',
                    'backgroundColor' => 'rgba(8,127,131,0.1)',
                    'fill'            => true,
                    'tension'         => 0.4,
                    'pointRadius'     => 4,
                ],
            ],
        ];
    }
}
