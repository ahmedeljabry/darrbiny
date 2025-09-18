<?php

declare(strict_types=1);

namespace App\Services\Admin;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

final class RolesService
{
    public function list(): array
    {
        return [
            'roles' => Role::with('permissions')->orderBy('name')->get(),
            'perms' => Permission::orderBy('name')->get(),
        ];
    }

    public function create(string $name): Role
    {
        return Role::firstOrCreate(['name' => $name]);
    }

    public function updatePermissions(string $roleId, array $permissionNames): void
    {
        $role = Role::findById($roleId);
        $perms = Permission::whereIn('name', $permissionNames)->get();
        $role->syncPermissions($perms);
    }

    public function delete(string $roleId): void
    {
        $role = Role::findById($roleId);
        abort_if($role->name === 'ADMIN', 422, 'Cannot delete ADMIN role');
        $role->delete();
    }
}

