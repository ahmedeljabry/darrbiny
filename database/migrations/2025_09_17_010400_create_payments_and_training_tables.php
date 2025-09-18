<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();
            $table->uuid('user_request_id')->index();
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->string('type');
            $table->string('provider');
            $table->string('provider_ref')->index();
            $table->string('status')->index();
            $table->unsignedBigInteger('app_fee_minor')->default(0);
            $table->unsignedBigInteger('trainer_net_minor')->default(0);
            $table->unsignedInteger('version')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('training_days', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_request_id')->index();
            $table->uuid('trainer_id')->index();
            $table->date('date');
            $table->unsignedInteger('hours_done');
            $table->text('notes')->nullable();
            $table->string('status')->index();
            $table->text('rejection_reason')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_days');
        Schema::dropIfExists('payments');
    }
};

