<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('how_it_works_sections', function (Blueprint $table) {
            if (!Schema::hasColumn('how_it_works_sections', 'position')) {
                $table->unsignedInteger('position')->default(0)->index()->after('title');
            }
        });

        Schema::table('how_it_works_steps', function (Blueprint $table) {
            if (!Schema::hasColumn('how_it_works_steps', 'position')) {
                $table->unsignedInteger('position')->default(0)->index()->after('title');
            }
        });
    }

    public function down(): void
    {
        Schema::table('how_it_works_steps', function (Blueprint $table) {
            if (Schema::hasColumn('how_it_works_steps', 'position')) {
                $table->dropColumn('position');
            }
        });

        Schema::table('how_it_works_sections', function (Blueprint $table) {
            if (Schema::hasColumn('how_it_works_sections', 'position')) {
                $table->dropColumn('position');
            }
        });
    }
};
