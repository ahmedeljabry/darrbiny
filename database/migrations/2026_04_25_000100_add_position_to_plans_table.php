<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('position')->default(0)->index();
        });

        DB::table('plans')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id'])
            ->values()
            ->each(function (object $plan, int $index): void {
                DB::table('plans')
                    ->where('id', $plan->id)
                    ->update(['position' => $index + 1]);
            });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
