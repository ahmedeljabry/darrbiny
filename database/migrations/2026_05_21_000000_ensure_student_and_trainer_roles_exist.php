<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names', []);
        $columnNames = config('permission.column_names', []);

        $rolesTable = $tableNames['roles'] ?? 'roles';
        $modelRolesTable = $tableNames['model_has_roles'] ?? 'model_has_roles';
        $rolePivotKey = $columnNames['role_pivot_key'] ?? 'role_id';
        $modelMorphKey = $columnNames['model_morph_key'] ?? 'model_id';
        $guard = config('auth.defaults.guard', 'web');
        $now = now();

        if (!Schema::hasTable($rolesTable)) {
            return;
        }

        foreach (['USER', 'TRAINER', 'ADMIN'] as $roleName) {
            $exists = DB::table($rolesTable)
                ->where('name', $roleName)
                ->where('guard_name', $guard)
                ->exists();

            if ($exists) {
                DB::table($rolesTable)
                    ->where('name', $roleName)
                    ->where('guard_name', $guard)
                    ->update(['updated_at' => $now]);
                continue;
            }

            DB::table($rolesTable)->insert([
                'name' => $roleName,
                'guard_name' => $guard,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (!Schema::hasTable($modelRolesTable) || !Schema::hasTable('users')) {
            $this->clearPermissionCache();
            return;
        }

        $roleIds = DB::table($rolesTable)
            ->whereIn('name', ['USER', 'TRAINER', 'ADMIN'])
            ->where('guard_name', $guard)
            ->pluck('id', 'name');

        $adminOrTrainerUsers = DB::table($modelRolesTable)
            ->where('model_type', App\Models\User::class)
            ->whereIn($rolePivotKey, [(int) $roleIds['ADMIN'], (int) $roleIds['TRAINER']])
            ->select($modelMorphKey);

        $this->assignRoleToUsers(
            (int) $roleIds['USER'],
            DB::table('users')
                ->where('user_type', 'user')
                ->whereNotIn('id', $adminOrTrainerUsers)
                ->select('id'),
            $modelRolesTable,
            $rolePivotKey,
            $modelMorphKey
        );

        $this->assignRoleToUsers(
            (int) $roleIds['TRAINER'],
            DB::table('users')->where('user_type', 'captain')->select('id'),
            $modelRolesTable,
            $rolePivotKey,
            $modelMorphKey
        );

        $this->clearPermissionCache();
    }

    public function down(): void
    {
        // Data repair migration: keep repaired roles and assignments in place.
    }

    private function assignRoleToUsers(
        int $roleId,
        \Illuminate\Database\Query\Builder $userQuery,
        string $modelRolesTable,
        string $rolePivotKey,
        string $modelMorphKey
    ): void {
        $userQuery
            ->orderBy('id')
            ->chunk(500, function ($users) use ($roleId, $modelRolesTable, $rolePivotKey, $modelMorphKey): void {
                $rows = $users->map(static fn ($user): array => [
                    $rolePivotKey => $roleId,
                    'model_type' => App\Models\User::class,
                    $modelMorphKey => $user->id,
                ])->all();

                if ($rows !== []) {
                    DB::table($modelRolesTable)->insertOrIgnore($rows);
                }
            });
    }

    private function clearPermissionCache(): void
    {
        app('cache')
            ->store(config('permission.cache.store') !== 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }
};
