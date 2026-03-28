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
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',

            'users.view',
            'users.create',
            'users.edit',
            'users.delete',

            'customers.view',
            'customers.create',
            'customers.edit',
            'customers.delete',

            'works.view',
            'works.create',
            'works.update',
            'works.delete',

            'budgets.view',
            'budgets.create',
            'budgets.update',
            'budgets.delete',

            'stock.view',
            'stock.create',
            'stock.edit',
            'stock.delete',

            'items.view',
            'items.create',
            'items.edit',
            'items.delete',

            'activity-logs.view',

            'settings.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $rolePermissions = [
            'admin' => $permissions,
            'vendas' => [
                'dashboard.view',
                'customers.view',
                'customers.create',
                'customers.edit',
                'budgets.view',
                'budgets.create',
                'budgets.update',
                'works.view',
                'items.view',
            ],
            'compras' => [
                'dashboard.view',
                'items.view',
                'items.create',
                'items.edit',
                'stock.view',
            ],
            'stocks' => [
                'dashboard.view',
                'stock.view',
                'items.view',
                'items.edit',
            ],
            'obras' => [
                'dashboard.view',
                'works.view',
                'works.create',
                'works.update',
                'works.delete',
                'customers.view',
                'budgets.view',
            ],
            'financeiro' => [
                'dashboard.view',
                'budgets.view',
                'budgets.update',
                'customers.view',
                'activity-logs.view',
            ],
            'funcionario' => [
                'dashboard.view',
                'customers.view',
                'works.view',
                'items.view',
                'stock.view',
            ],
        ];

        foreach ($rolePermissions as $roleName => $permissionNames) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($permissionNames);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
