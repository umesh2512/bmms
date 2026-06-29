<?php

namespace App\Filament\Tenant\Resources\DecisionRegisterResource\Pages;

use App\Filament\Tenant\Resources\DecisionRegisterResource;
use App\Models\Resolution;
use App\Services\ResolutionService;
use Filament\Resources\Pages\CreateRecord;

class CreateDecision extends CreateRecord
{
    protected static string $resource = DecisionRegisterResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = auth()->user()->tenant_id;
        $data['status']    = 'proposed';
        $data['proposed_by'] = $data['proposed_by'] ?? auth()->id();
        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Resolution $record */
        $record = $this->getRecord();

        if ($record->type === 'circular') {
            app(ResolutionService::class)->openVoting($record);
            app(ResolutionService::class)->notifyCircularResolutionVoters($record);
        }
    }
}
