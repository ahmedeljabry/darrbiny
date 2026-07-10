<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('gateway_reference')->nullable()->after('payment_method')->index();
            $table->text('gateway_checkout_url')->nullable()->after('gateway_reference');
            $table->string('gateway_status')->nullable()->after('gateway_checkout_url')->index();
            $table->json('gateway_payload')->nullable()->after('gateway_status');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex(['gateway_reference']);
            $table->dropIndex(['gateway_status']);
            $table->dropColumn([
                'gateway_reference',
                'gateway_checkout_url',
                'gateway_status',
                'gateway_payload',
            ]);
        });
    }
};
