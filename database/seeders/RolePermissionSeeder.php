<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Vendor permissions
            'vendors.view',
            'vendors.create',
            'vendors.edit',
            'vendors.delete',
            'vendors.approve',
            'vendors.suspend',

            // User permissions
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'users.manage_roles',

            // Plan permissions
            'plans.view',
            'plans.create',
            'plans.edit',
            'plans.delete',

            // Subscription permissions
            'subscriptions.view',
            'subscriptions.create',
            'subscriptions.edit',
            'subscriptions.delete',
            'subscriptions.cancel',

            // Payment permissions
            'payments.view',
            'payments.create',
            'payments.edit',
            'payments.refund',

            // Settings permissions
            'settings.view',
            'settings.edit',

            // Activity logs
            'activity_logs.view',
            'activity_logs.delete',

            // Reports
            'reports.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo([
            'vendors.view', 'vendors.create', 'vendors.edit', 'vendors.approve', 'vendors.suspend',
            'users.view', 'users.create', 'users.edit',
            'plans.view', 'plans.create', 'plans.edit',
            'subscriptions.view', 'subscriptions.create', 'subscriptions.edit', 'subscriptions.cancel',
            'payments.view', 'payments.create', 'payments.edit', 'payments.refund',
            'settings.view', 'settings.edit',
            'activity_logs.view',
            'reports.view',
        ]);

        $user = Role::firstOrCreate(['name' => 'user']);
        $user->givePermissionTo([
            'vendors.view',
        ]);

        $this->command->info('Roles and permissions created successfully!');
    }
}
