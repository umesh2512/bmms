<?php

namespace App\Filament\Tenant\Resources\MeetingResource\Pages;

use App\Filament\Tenant\Resources\MeetingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMeeting extends CreateRecord
{
    protected static string $resource = MeetingResource::class;
}
