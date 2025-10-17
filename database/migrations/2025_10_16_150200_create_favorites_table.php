<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('trainer_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id','trainer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};

