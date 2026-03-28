<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',

            'users.view',
            'users.create',
            'users.edit',
            'users.delete',

            'roles.view',
            'roles.create',
            'roles.edit',

            'customers.view',
            'customers.create',
            'customers.edit',
            'customers.delete',

            'jobs.view',
            'jobs.create',
            'jobs.edit',
            'jobs.delete',

            'budgets.view',
            'budgets.create',
            'budgets.edit',
            'budgets.delete',
            'budgets.update',

            'stock.view',
            'stock.create',
            'stock.edit',
            'stock.delete',

            'items.view',
            'items.create',
            'items.edit',
            'items.delete',

            // 🔥 NOVO
            'activity-logs.view',

            'settings.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $technicianRole = Role::firstOrCreate(['name' => 'tecnico']);
        $commercialRole = Role::firstOrCreate(['name' => 'comercial']);
        $employeeRole = Role::firstOrCreate(['name' => 'funcionario']);

        // Admin tem tudo
        $adminRole->syncPermissions(Permission::all());

        $technicianRole->syncPermissions([
            'dashboard.view',
            'customers.view',
            'jobs.view',
            'jobs.create',
            'jobs.edit',
            'stock.view',
        ]);

        $commercialRole->syncPermissions([
            'dashboard.view',
            'customers.view',
            'customers.create',
            'customers.edit',
            'budgets.view',
            'budgets.create',
            'budgets.edit',
            'budgets.update',
        ]);

        $employeeRole->syncPermissions([
            'dashboard.view',
            'customers.view',
            'jobs.view',
            'stock.view',
        ]);
    }
}
