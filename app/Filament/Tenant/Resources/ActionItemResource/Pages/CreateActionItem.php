<?php

namespace App\Filament\Tenant\Resources\ActionItemResource\Pages;

use App\Filament\Tenant\Resources\ActionItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateActionItem extends CreateRecord
{
    protected static string $resource = ActionItemResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id']   = auth()->user()->tenant_id;
        $data['assigned_by'] = auth()->id();
        return $data;
    }
}
