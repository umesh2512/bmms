<?php

namespace App\Filament\Admin\Widgets;

use App\Services\AnalyticsService;
use Filament\Widgets\ChartWidget;

class PlatformMeetingsChart extends ChartWidget
{
    protected static ?int $sort  = 3;
    protected ?string $heading   = 'Platform Meetings per Month';
    protected ?string $maxHeight = '280px';

    protected function getType(): string
    {
        return 'line';
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
        $months = (int) ($this->filter ?? 12);
        $result = app(AnalyticsService::class)->platformMeetingsByMonth($months);

        return [
            'labels'   => $result['labels'],
            'datasets' => [
                [
                    'label'           => 'Meetings',
                    'data'            => $result['data'],
                    'borderColor'     => '#f59e0b',
                    'backgroundColor' => 'rgba(245,158,11,0.1)',
                    'fill'            => true,
                    'tension'         => 0.4,
                    'pointRadius'     => 4,
                ],
            ],
        ];
    }
}
