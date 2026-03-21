<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Seed the application's roles and permissions.
     */
    public function run(): void
    {
        // Limpa cache de permissões para evitar conflitos durante o seeding.
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        /*
         |--------------------------------------------------------------------------
         | Permissões base do sistema
         |--------------------------------------------------------------------------
         | Nesta fase vamos criar um conjunto inicial, simples e coerente.
         | Mais tarde podemos expandir por módulo sem partir a estrutura.
         */
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

             'settings.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        /*
         |--------------------------------------------------------------------------
         | Roles base
         |--------------------------------------------------------------------------
         */
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $technicianRole = Role::firstOrCreate(['name' => 'tecnico']);
        $commercialRole = Role::firstOrCreate(['name' => 'comercial']);
        $employeeRole = Role::firstOrCreate(['name' => 'funcionario']);

        // Admin tem acesso total.
        $adminRole->syncPermissions(Permission::all());

        // Técnico: foco em obras e stock.
        $technicianRole->syncPermissions([
            'dashboard.view',
            'customers.view',
            'jobs.view',
            'jobs.create',
            'jobs.edit',
            'stock.view',
        ]);

        // Comercial: foco em clientes e orçamentos.
        $commercialRole->syncPermissions([
            'dashboard.view',
            'customers.view',
            'customers.create',
            'customers.edit',
            'budgets.view',
            'budgets.create',
            'budgets.edit',
        ]);

        // Funcionário: acesso mais limitado.
        $employeeRole->syncPermissions([
            'dashboard.view',
            'customers.view',
            'jobs.view',
            'stock.view',
        ]);
    }
}
