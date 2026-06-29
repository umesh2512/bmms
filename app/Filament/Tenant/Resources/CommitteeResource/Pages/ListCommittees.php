<?php

namespace App\Filament\Tenant\Resources\CommitteeResource\Pages;

use App\Filament\Tenant\Resources\CommitteeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCommittees extends ListRecords
{
    protected static string $resource = CommitteeResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
