<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cancellation_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_request_id')->unique()->index();
            $table->uuid('user_id')->index();
            $table->text('reason');
            $table->string('status')->index(); // pending, approved, rejected
            $table->text('admin_notes')->nullable();
            $table->uuid('processed_by')->nullable()->index(); // Admin user ID
            $table->timestamp('processed_at')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cancellation_requests');
    }
};


