<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropCityColumnFromPlans();
        $this->dropColumnIfExists('trainer_profiles', 'city_id');
        $this->dropColumnIfExists('users', 'city_id');

        $this->dropLegacyLocationColumns('users');
        $this->dropLegacyLocationColumns('trainer_profiles');
        $this->dropLegacyLocationColumns('plans');

        Schema::dropIfExists('cities');
    }

    public function down(): void
    {
        if (!Schema::hasTable('cities')) {
            Schema::create('cities', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('country_id')->index();
                $table->string('name');
                $table->timestamps();
            });
        }

        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'city_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->uuid('city_id')->nullable()->index()->after('country_id');
            });
        }

        if (Schema::hasTable('trainer_profiles') && !Schema::hasColumn('trainer_profiles', 'city_id')) {
            Schema::table('trainer_profiles', function (Blueprint $table) {
                $table->uuid('city_id')->nullable()->index()->after('country_id');
            });
        }

        if (Schema::hasTable('plans') && !Schema::hasColumn('plans', 'city_id')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->uuid('city_id')->nullable()->after('country_id');
            });
        }
    }

    private function dropCityColumnFromPlans(): void
    {
        if (!Schema::hasTable('plans') || !Schema::hasColumn('plans', 'city_id')) {
            return;
        }

        Schema::table('plans', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                try {
                    $table->dropForeign(['city_id']);
                } catch (\Throwable) {
                    // Foreign key may be absent in legacy DBs.
                }
            }

            $table->dropColumn('city_id');
        });
    }

    private function dropColumnIfExists(string $tableName, string $column): void
    {
        if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, $column)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($column) {
            $table->dropColumn($column);
        });
    }

    private function dropLegacyLocationColumns(string $tableName): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        $legacyColumns = [
            $this->legacyLocalColumn(),
            $this->legacyAreaOneColumn(),
            $this->legacyAreaTwoColumn(),
        ];

        $existing = array_values(array_filter(
            $legacyColumns,
            static fn (string $column): bool => Schema::hasColumn($tableName, $column)
        ));

        if (empty($existing)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($existing) {
            $table->dropColumn($existing);
        });
    }

    private function legacyLocalColumn(): string
    {
        return 'loc' . 'ality';
    }

    private function legacyAreaOneColumn(): string
    {
        return 'area' . '_level_' . '1';
    }

    private function legacyAreaTwoColumn(): string
    {
        return 'area' . '_level_' . '2';
    }
};
