<?php

namespace App\Filament\Admin\Widgets;

use App\Services\AnalyticsService;
use Filament\Widgets\ChartWidget;

class PlatformGrowthChart extends ChartWidget
{
    protected static ?int $sort  = 2;
    protected ?string $heading   = 'New Organisations per Month';
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
        $months = (int) ($this->filter ?? 12);
        $result = app(AnalyticsService::class)->platformTenantGrowth($months);

        return [
            'labels'   => $result['labels'],
            'datasets' => [
                [
                    'label'           => 'New Organisations',
                    'data'            => $result['data'],
                    'backgroundColor' => '#6366f1',
                    'borderRadius'    => 4,
                ],
            ],
        ];
    }
}
