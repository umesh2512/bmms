<?php

namespace App\Filament\Tenant\Resources\DepartmentResource\Pages;

use App\Filament\Tenant\Resources\DepartmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDepartment extends CreateRecord
{
    protected static string $resource = DepartmentResource::class;
}
