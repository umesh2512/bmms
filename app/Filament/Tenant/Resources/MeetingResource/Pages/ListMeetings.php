<?php

namespace App\Filament\Tenant\Resources\MeetingResource\Pages;

use App\Filament\Tenant\Resources\MeetingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMeetings extends ListRecords
{
    protected static string $resource = MeetingResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
