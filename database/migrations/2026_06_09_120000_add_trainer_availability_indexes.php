<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable('trainer_profiles')
            && Schema::hasColumn('trainer_profiles', 'country_id')
            && Schema::hasColumn('trainer_profiles', 'area_level_1')
            && Schema::hasColumn('trainer_profiles', 'area_level_2')
            && Schema::hasColumn('trainer_profiles', 'area_level_3')
            && Schema::hasColumn('trainer_profiles', 'verified_at')
            && Schema::hasColumn('trainer_profiles', 'deleted_at')
        ) {
            Schema::table('trainer_profiles', function (Blueprint $table): void {
                $table->index(
                    ['country_id', 'area_level_1', 'area_level_2', 'area_level_3', 'verified_at', 'deleted_at'],
                    'trainer_profiles_availability_idx'
                );
            });
        }

        if (
            Schema::hasTable('users')
            && Schema::hasColumn('users', 'user_type')
            && Schema::hasColumn('users', 'banned_until')
            && Schema::hasColumn('users', 'deleted_at')
        ) {
            Schema::table('users', function (Blueprint $table): void {
                $table->index(['user_type', 'banned_until', 'deleted_at'], 'users_availability_status_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('trainer_profiles')) {
            Schema::table('trainer_profiles', function (Blueprint $table): void {
                $table->dropIndex('trainer_profiles_availability_idx');
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropIndex('users_availability_status_idx');
            });
        }
    }
};
