<?php

namespace App\Filament\Tenant\Widgets;

use App\Services\AnalyticsService;
use Filament\Widgets\ChartWidget;

class MeetingsByMonthChart extends ChartWidget
{
    protected static ?int $sort  = 2;
    protected ?string $heading   = 'Meetings by Month';
    protected ?string $maxHeight = '280px';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getFilters(): ?array
    {
        return [
            '6'  => 'Last 6 months',
            '12' => 'Last 12 months',
            '24' => 'Last 24 months',
        ];
    }

    protected function getData(): array
    {
        $months   = (int) ($this->filter ?? 12);
        $tenantId = auth()->user()->tenant_id;
        $result   = app(AnalyticsService::class)->tenantMeetingsByMonth($tenantId, $months);

        return [
            'labels'   => $result['labels'],
            'datasets' => [
                [
                    'label'           => 'Meetings',
                    'data'            => $result['data'],
                    'backgroundColor' => '#087f83',
                    'borderRadius'    => 4,
                ],
            ],
        ];
    }
}
