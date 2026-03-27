<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the application's initial administrator user.
     */
    public function run(): void
    {
        $email = env('ADMIN_USER_EMAIL');
        $password = env('ADMIN_USER_PASSWORD');
        $name = env('ADMIN_USER_NAME', 'Administrador');

        if (blank($email) || blank($password)) {
            $this->command?->warn('AdminUserSeeder ignorado: define ADMIN_USER_EMAIL e ADMIN_USER_PASSWORD no ambiente para criar o utilizador admin inicial.');
            return;
        }

        $admin = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
            ]
        );

        if (! $admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }
    }
}
