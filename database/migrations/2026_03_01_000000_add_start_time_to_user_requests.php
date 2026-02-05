<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_requests', function (Blueprint $table) {
            $table->time('start_time')->nullable()->after('start_date');
        });
    }

    public function down(): void
    {
        Schema::table('user_requests', function (Blueprint $table) {
            $table->dropColumn('start_time');
        });
    }
};
