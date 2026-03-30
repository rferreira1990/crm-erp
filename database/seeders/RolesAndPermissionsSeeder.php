<?php

namespace Database\Seeders;

use App\Models\User;
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

            'suppliers.view',
            'suppliers.create',
            'suppliers.update',
            'suppliers.delete',

            'purchases.view',
            'purchases.create',
            'purchases.update',
            'purchases.award',
            'purchases.delete',

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
                'suppliers.view',
                'purchases.view',
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
                'suppliers.view',
                'suppliers.create',
                'suppliers.update',
                'purchases.view',
                'purchases.create',
                'purchases.update',
                'purchases.award',
                'purchases.delete',
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
                'purchases.view',
            ],
            'financeiro' => [
                'dashboard.view',
                'budgets.view',
                'budgets.update',
                'customers.view',
                'purchases.view',
                'purchases.award',
                'activity-logs.view',
            ],
            'funcionario' => [
                'dashboard.view',
                'customers.view',
                'works.view',
                'items.view',
                'stock.view',
                'suppliers.view',
                'purchases.view',
            ],
        ];

        foreach ($rolePermissions as $roleName => $permissionNames) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($permissionNames);
        }

        $legacyRoleMap = [
            'tecnico' => 'obras',
            'comercial' => 'vendas',
        ];

        foreach ($legacyRoleMap as $legacyRole => $targetRole) {
            if (! Role::query()->where('name', $legacyRole)->exists()) {
                continue;
            }

            User::query()
                ->role($legacyRole)
                ->get()
                ->each(function (User $user) use ($targetRole) {
                    if (! $user->hasRole($targetRole)) {
                        $user->assignRole($targetRole);
                    }
                });

            Role::query()->where('name', $legacyRole)->delete();
        }

        $legacyPermissions = [
            'customers.update',
            'budgets.edit',
            'roles.view',
            'roles.create',
            'roles.edit',
            'jobs.view',
            'jobs.create',
            'jobs.edit',
            'jobs.delete',
        ];

        Permission::query()
            ->whereIn('name', $legacyPermissions)
            ->get()
            ->each(function (Permission $permission) {
                $permission->delete();
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
