<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('app_wallet_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('direction')->index();
            $table->string('source')->index();
            $table->unsignedBigInteger('amount_minor');
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable()->index();
            $table->unsignedInteger('version')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_wallet_transactions');
    }
};
