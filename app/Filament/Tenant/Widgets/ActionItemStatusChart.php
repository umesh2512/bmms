<?php

namespace App\Filament\Tenant\Widgets;

use App\Services\AnalyticsService;
use Filament\Widgets\ChartWidget;

class ActionItemStatusChart extends ChartWidget
{
    protected static ?int $sort  = 5;
    protected ?string $heading   = 'Action Item Status';
    protected ?string $maxHeight = '280px';

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $tenantId = auth()->user()->tenant_id;
        $stats    = app(AnalyticsService::class)->tenantActionItemStats($tenantId);

        return [
            'labels'   => ['Open / In Progress', 'Done', 'Overdue', 'Cancelled'],
            'datasets' => [
                [
                    'label'           => 'Action Items',
                    'data'            => [
                        $stats['open'],
                        $stats['done'],
                        $stats['overdue'],
                        $stats['cancelled'],
                    ],
                    'backgroundColor' => ['#3b82f6', '#10b981', '#ef4444', '#6b7280'],
                    'hoverOffset'     => 6,
                ],
            ],
        ];
    }
}
