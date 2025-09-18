<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view_admin',
            'manage_users',
            'manage_roles',
            'manage_permissions',
            'manage_plans',
            'manage_payments',
            'manage_reports',
            'manage_geo',
            'manage_ratings',
            'manage_wallets',
            'manage_notifications',
            'manage_settings',
            'manage_payouts',
            'manage_rewards',
            'verify_trainers',
        ];
        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        foreach (['USER','TRAINER','ADMIN'] as $role) {
            $r = Role::firstOrCreate(['name' => $role]);
            if ($role === 'ADMIN') {
                $r->givePermissionTo($permissions);
            }
        }
    }
}
