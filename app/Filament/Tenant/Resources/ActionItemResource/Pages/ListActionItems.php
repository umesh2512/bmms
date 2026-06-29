<?php

namespace App\Filament\Tenant\Resources\ActionItemResource\Pages;

use App\Filament\Tenant\Resources\ActionItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListActionItems extends ListRecords
{
    protected static string $resource = ActionItemResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Add Action Item')];
    }
}
