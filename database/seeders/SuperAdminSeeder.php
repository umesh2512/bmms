<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@bmms.in'],
            [
                'name'              => 'BMMS Superadmin',
                'password'          => Hash::make('Admin@1234'),
                'status'            => 'active',
                'email_verified_at' => now(),
                'tenant_id'         => null,
            ]
        );

        $admin->assignRole('superadmin');
    }
}
