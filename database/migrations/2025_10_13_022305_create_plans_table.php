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
        Schema::create('plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('price_min', 10,2)->default(0);
            $table->string('badge_discount')->nullable();
            $table->decimal('deposit_amount', 10,2)->nullable();
            $table->string('duration_days');
            $table->unsignedInteger('hours_count')->nullable();
            $table->integer('session_count')->nullable();
            $table->string('level')->nullable();
            $table->foreignUuid('country_id')->constrained('countries')->cascadeOnDelete();
            $table->foreignUuid('city_id')->constrained('cities')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
