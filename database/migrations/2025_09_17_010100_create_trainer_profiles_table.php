<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trainer_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->unique();
            $table->text('bio')->nullable();
            $table->uuid('country_id')->nullable()->index();
            $table->uuid('city_id')->nullable()->index();
            $table->boolean('car_available')->default(false);
            $table->boolean('pickup_available')->default(false);
            $table->unsignedInteger('rating_count')->default(0);
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->timestamp('verified_at')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainer_profiles');
    }
};

