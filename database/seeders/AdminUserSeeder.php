<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the application's initial administrator user.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@crm.local'],
            [
                'name' => 'Administrador',
                'password' => 'admin123456',
            ]
        );

        // Garante que o utilizador recebe sempre a role de admin.
        if (! $admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }
    }
}
