<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (!Schema::hasColumn('plans', 'price_max')) {
                $table->decimal('price_max', 10, 2)->nullable()->after('price_min');
            }
        });

        if (Schema::hasColumn('plans', 'price_max')) {
            DB::table('plans')
                ->whereNull('price_max')
                ->update(['price_max' => DB::raw('price_min')]);
        }
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (Schema::hasColumn('plans', 'price_max')) {
                $table->dropColumn('price_max');
            }
        });
    }
};
