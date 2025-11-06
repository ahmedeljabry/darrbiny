<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_schedule_progress', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_request_id')->index();
            $table->uuid('plan_schedule_item_id')->index();
            $table->unsignedInteger('day_number'); // For quick lookup
            $table->boolean('is_checked')->default(false);
            $table->timestamp('checked_at')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            
            $table->unique(['user_request_id', 'plan_schedule_item_id'], 'user_schedule_progress_unique');
            $table->index(['user_request_id', 'day_number'], 'user_schedule_progress_user_day_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_schedule_progress');
    }
};

