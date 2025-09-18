<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('hours_count');
            $table->string('training_type');
            $table->uuid('country_id')->nullable()->index();
            $table->uuid('city_id')->nullable()->index();
            $table->unsignedBigInteger('base_price_minor');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};

