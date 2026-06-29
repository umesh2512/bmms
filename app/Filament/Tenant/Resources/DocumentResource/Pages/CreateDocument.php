<?php

namespace App\Filament\Tenant\Resources\DocumentResource\Pages;

use App\Filament\Tenant\Resources\DocumentResource;
use App\Models\DocumentVersion;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $filePath = $data['file_path'] ?? null;

        $data['tenant_id']       = auth()->user()->tenant_id;
        $data['uploaded_by']     = auth()->id();
        $data['current_version'] = 1;

        if ($filePath && Storage::disk('local')->exists($filePath)) {
            $data['file_size'] = Storage::disk('local')->size($filePath);
            $data['file_type'] = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        DocumentVersion::create([
            'document_id'    => $record->id,
            'version_number' => 1,
            'file_path'      => $record->file_path,
            'file_size'      => $record->file_size,
            'change_note'    => 'Initial upload',
            'uploaded_by'    => $record->uploaded_by,
        ]);
    }
}
