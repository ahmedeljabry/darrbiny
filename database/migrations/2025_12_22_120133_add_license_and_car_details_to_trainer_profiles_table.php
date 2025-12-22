<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trainer_profiles', function (Blueprint $table) {
            $table->string('license_number', 50)->nullable()->after('has_driving_license');
            $table->date('license_expiry_date')->nullable()->after('license_number');
            $table->string('car_model', 120)->nullable()->after('car_type');
            $table->integer('car_year')->nullable()->after('car_model');
            $table->string('car_plate_number', 20)->nullable()->after('car_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trainer_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'license_number',
                'license_expiry_date',
                'car_model',
                'car_year',
                'car_plate_number',
            ]);
        });
    }
};
