<?php

namespace App\Filament\Tenant\Widgets;

use App\Services\AnalyticsService;
use Filament\Widgets\ChartWidget;

class ResolutionOutcomeChart extends ChartWidget
{
    protected static ?int $sort  = 4;
    protected ?string $heading   = 'Resolution Outcomes';
    protected ?string $maxHeight = '280px';

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $tenantId = auth()->user()->tenant_id;
        $stats    = app(AnalyticsService::class)->tenantResolutionStats($tenantId);

        return [
            'labels'   => ['Passed', 'Failed', 'Pending', 'Withdrawn/Deferred'],
            'datasets' => [
                [
                    'label'           => 'Resolutions',
                    'data'            => [
                        $stats['passed'],
                        $stats['failed'],
                        $stats['pending'],
                        $stats['withdrawn'],
                    ],
                    'backgroundColor' => ['#10b981', '#ef4444', '#f59e0b', '#6b7280'],
                    'hoverOffset'     => 6,
                ],
            ],
        ];
    }
}
