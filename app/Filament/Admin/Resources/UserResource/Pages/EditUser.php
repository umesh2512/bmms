<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    // Pre-fill the role field from the user's current role
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['role'] = $this->getRecord()->getRoleNames()->first() ?? 'board_member';
        return $data;
    }

    protected function afterSave(): void
    {
        $role = $this->data['role'] ?? null;
        if ($role) {
            $this->getRecord()->syncRoles([$role]);
        }
    }
}
