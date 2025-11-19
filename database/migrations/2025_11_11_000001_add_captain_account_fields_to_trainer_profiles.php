<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('trainer_profiles', function (Blueprint $table) {
            $table->string('car_type', 120)->nullable()->after('pickup_available');
            $table->string('car_model_year', 20)->nullable()->after('car_type');
            $table->boolean('has_driving_license')->default(false)->after('car_model_year');
        });
    }

    public function down(): void
    {
        Schema::table('trainer_profiles', function (Blueprint $table) {
            $table->dropColumn(['car_type', 'car_model_year', 'has_driving_license']);
        });
    }
};
