<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_requests', function (Blueprint $table) {
            $table->uuid('trainer_id')->nullable()->index()->after('user_id');
            $table->decimal('latitude', 10, 8)->nullable()->after('needs_pickup');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            
            $table->foreign('trainer_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('user_requests', function (Blueprint $table) {
            $table->dropForeign(['trainer_id']);
            $table->dropColumn(['trainer_id', 'latitude', 'longitude']);
        });
    }
};

