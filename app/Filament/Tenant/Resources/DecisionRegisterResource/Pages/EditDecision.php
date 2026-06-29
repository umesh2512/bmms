<?php

namespace App\Filament\Tenant\Resources\DecisionRegisterResource\Pages;

use App\Filament\Tenant\Resources\DecisionRegisterResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDecision extends EditRecord
{
    protected static string $resource = DecisionRegisterResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
