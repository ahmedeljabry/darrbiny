<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();
            $table->unsignedBigInteger('amount'); // Amount in major units (same as points_balance)
            $table->string('type')->index(); // 'topup_request', 'refund', 'payment', etc.
            $table->string('status')->default('pending')->index(); // pending, approved, rejected
            $table->text('rejection_reason')->nullable();
            $table->uuid('processed_by')->nullable()->index(); // Admin user ID
            $table->timestamp('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};

