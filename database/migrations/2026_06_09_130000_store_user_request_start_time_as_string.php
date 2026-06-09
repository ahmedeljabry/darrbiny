<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_requests') || ! Schema::hasColumn('user_requests', 'start_time')) {
            return;
        }

        match (DB::getDriverName()) {
            'mysql', 'mariadb' => DB::statement('ALTER TABLE user_requests MODIFY start_time VARCHAR(64) NULL'),
            'pgsql' => DB::statement('ALTER TABLE user_requests ALTER COLUMN start_time TYPE VARCHAR(64) USING start_time::text'),
            'sqlsrv' => DB::statement('ALTER TABLE user_requests ALTER COLUMN start_time NVARCHAR(64) NULL'),
            default => null,
        };
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_requests') || ! Schema::hasColumn('user_requests', 'start_time')) {
            return;
        }

        match (DB::getDriverName()) {
            'mysql', 'mariadb' => DB::statement('ALTER TABLE user_requests MODIFY start_time TIME NULL'),
            'pgsql' => DB::statement("ALTER TABLE user_requests ALTER COLUMN start_time TYPE TIME(0) WITHOUT TIME ZONE USING NULLIF(start_time, '')::time"),
            'sqlsrv' => DB::statement('ALTER TABLE user_requests ALTER COLUMN start_time TIME NULL'),
            default => null,
        };
    }
};
