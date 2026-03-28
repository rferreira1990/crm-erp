<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Limpar cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Permissões
        $permissions = [
            'customers.view',
            'customers.create',
            'customers.edit',
            'customers.delete',
            'budgets.view',
            'budgets.create',
            'budgets.update',
            'budgets.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Role admin
        $admin = Role::firstOrCreate(['name' => 'admin']);

        $admin->givePermissionTo($permissions);
    }
}
