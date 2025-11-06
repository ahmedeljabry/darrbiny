<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plan_schedule_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('plan_id')->index();
            $table->unsignedInteger('day_number'); // 1-based: 1, 2, 3...
            $table->string('title')->nullable();
            $table->unsignedInteger('position')->default(0); // For ordering
            $table->unsignedInteger('version')->default(1);
            $table->softDeletes();
            $table->timestamps();
            
            $table->unique(['plan_id', 'day_number'], 'plan_schedule_items_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_schedule_items');
    }
};

