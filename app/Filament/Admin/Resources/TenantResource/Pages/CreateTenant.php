<?php

namespace App\Filament\Admin\Resources\TenantResource\Pages;

use App\Filament\Admin\Resources\TenantResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    private ?string $adminName     = null;
    private ?string $adminEmail    = null;
    private ?string $adminPassword = null;

    // Strip admin-user fields before saving the Tenant record
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->adminName     = $data['admin_name'] ?? null;
        $this->adminEmail    = $data['admin_email'] ?? null;
        $this->adminPassword = $data['admin_password'] ?? null;

        unset($data['admin_name'], $data['admin_email'], $data['admin_password']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if (! $this->adminEmail) {
            return;
        }

        $user = User::create([
            'name'      => $this->adminName,
            'email'     => $this->adminEmail,
            'password'  => Hash::make($this->adminPassword),
            'tenant_id' => $this->getRecord()->id,
            'status'    => 'active',
        ]);

        $user->assignRole('tenant_admin');
    }
}
