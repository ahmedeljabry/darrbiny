<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('trainer_id')->index();
            $table->uuid('user_request_id')->index();
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->string('status')->index();
            $table->string('bank_ref')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('ratings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();
            $table->uuid('trainer_id')->index();
            $table->uuid('user_request_id')->index();
            $table->unsignedTinyInteger('stars');
            $table->text('comment')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['user_id','trainer_id','user_request_id']);
        });

        Schema::create('referrals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('owner_user_id')->unique();
            $table->string('code')->unique();
            $table->unsignedBigInteger('total_points_earned')->default(0);
            $table->unsignedBigInteger('total_redemptions')->default(0);
            $table->timestamps();
        });

        Schema::create('rewards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->unsignedBigInteger('required_points');
            $table->unsignedInteger('stock')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('reward_redemptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();
            $table->uuid('reward_id')->index();
            $table->unsignedBigInteger('points_spent');
            $table->string('status')->index();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_redemptions');
        Schema::dropIfExists('rewards');
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('ratings');
        Schema::dropIfExists('payouts');
    }
};

