<?php

namespace App\Filament\Tenant\Widgets;

use App\Models\Meeting;
use Filament\Widgets\Widget;

class QuickStartWidget extends Widget
{
    protected static ?int $sort = 0;
    protected string $view = 'filament.tenant.widgets.quick-start';

    protected function getViewData(): array
    {
        $tenantId      = auth()->user()->tenant_id;
        $meetingCount  = Meeting::withoutGlobalScope('tenant')->where('tenant_id', $tenantId)->count();

        return [
            'orgName'      => auth()->user()->tenant?->name ?? 'Your Organisation',
            'userName'     => auth()->user()->name,
            'meetingCount' => $meetingCount,
            'isNew'        => $meetingCount === 0,
        ];
    }
}
