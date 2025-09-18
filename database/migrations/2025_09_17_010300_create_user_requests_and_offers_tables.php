<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();
            $table->uuid('plan_id')->index();
            $table->date('start_date');
            $table->boolean('has_user_car')->default(false);
            $table->boolean('wants_trainer_car')->default(false);
            $table->boolean('needs_pickup')->default(false);
            $table->string('status')->index();
            $table->char('currency', 3)->index();
            $table->unsignedBigInteger('app_fee_reserved_minor')->default(0);
            $table->unsignedBigInteger('total_paid_minor')->default(0);
            $table->unsignedInteger('version')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('trainer_offers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_request_id')->index();
            $table->uuid('trainer_id')->index();
            $table->unsignedBigInteger('price_minor');
            $table->text('message')->nullable();
            $table->string('status')->index();
            $table->unsignedInteger('version')->default(1);
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['user_request_id', 'trainer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainer_offers');
        Schema::dropIfExists('user_requests');
    }
};

