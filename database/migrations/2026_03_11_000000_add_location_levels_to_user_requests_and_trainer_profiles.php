<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_requests')) {
            Schema::table('user_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('user_requests', 'country_id')) {
                    $table->uuid('country_id')->nullable()->after('plan_id')->index();
                }
                if (!Schema::hasColumn('user_requests', 'area_level_1')) {
                    $table->string('area_level_1', 120)->nullable()->after('country_id')->index();
                }
                if (!Schema::hasColumn('user_requests', 'area_level_2')) {
                    $table->string('area_level_2', 120)->nullable()->after('area_level_1');
                }
                if (!Schema::hasColumn('user_requests', 'area_level_3')) {
                    $table->string('area_level_3', 120)->nullable()->after('area_level_2');
                }
                if (!Schema::hasColumn('user_requests', 'locality')) {
                    $table->string('locality', 120)->nullable()->after('area_level_3');
                }
            });
        }

        if (Schema::hasTable('trainer_profiles')) {
            Schema::table('trainer_profiles', function (Blueprint $table) {
                if (!Schema::hasColumn('trainer_profiles', 'area_level_1')) {
                    $table->string('area_level_1', 120)->nullable()->after('country_id')->index();
                }
                if (!Schema::hasColumn('trainer_profiles', 'area_level_2')) {
                    $table->string('area_level_2', 120)->nullable()->after('area_level_1');
                }
                if (!Schema::hasColumn('trainer_profiles', 'area_level_3')) {
                    $table->string('area_level_3', 120)->nullable()->after('area_level_2');
                }
                if (!Schema::hasColumn('trainer_profiles', 'locality')) {
                    $table->string('locality', 120)->nullable()->after('area_level_3');
                }
            });
        }

        // Best-effort backfill country for legacy requests from related plan.
        if (
            Schema::hasTable('user_requests')
            && Schema::hasColumn('user_requests', 'country_id')
            && Schema::hasTable('plans')
            && Schema::hasColumn('plans', 'country_id')
        ) {
            DB::table('user_requests')
                ->whereNull('country_id')
                ->orderBy('id')
                ->chunkById(500, function ($rows) {
                    foreach ($rows as $row) {
                        $planCountryId = DB::table('plans')->where('id', $row->plan_id)->value('country_id');
                        if ($planCountryId) {
                            DB::table('user_requests')
                                ->where('id', $row->id)
                                ->update(['country_id' => $planCountryId]);
                        }
                    }
                }, 'id');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('user_requests')) {
            Schema::table('user_requests', function (Blueprint $table) {
                $columns = array_values(array_filter([
                    Schema::hasColumn('user_requests', 'country_id') ? 'country_id' : null,
                    Schema::hasColumn('user_requests', 'area_level_1') ? 'area_level_1' : null,
                    Schema::hasColumn('user_requests', 'area_level_2') ? 'area_level_2' : null,
                    Schema::hasColumn('user_requests', 'area_level_3') ? 'area_level_3' : null,
                    Schema::hasColumn('user_requests', 'locality') ? 'locality' : null,
                ]));

                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('trainer_profiles')) {
            Schema::table('trainer_profiles', function (Blueprint $table) {
                $columns = array_values(array_filter([
                    Schema::hasColumn('trainer_profiles', 'area_level_1') ? 'area_level_1' : null,
                    Schema::hasColumn('trainer_profiles', 'area_level_2') ? 'area_level_2' : null,
                    Schema::hasColumn('trainer_profiles', 'area_level_3') ? 'area_level_3' : null,
                    Schema::hasColumn('trainer_profiles', 'locality') ? 'locality' : null,
                ]));

                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
