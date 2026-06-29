<?php

namespace App\Filament\Tenant\Resources\UserResource\Pages;

use App\Filament\Tenant\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => $this->getRecord()->id !== auth()->id()),
        ];
    }

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
