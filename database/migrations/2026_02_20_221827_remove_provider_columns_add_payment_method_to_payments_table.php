<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_method')->after('type');
            $table->dropIndex(['provider_ref']);
            $table->dropColumn(['provider', 'provider_ref']);
        });

        // Migrate existing data: copy provider values to payment_method
        // (already done via column default if needed)
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('provider')->after('type');
            $table->string('provider_ref')->index()->after('provider');
            $table->dropColumn('payment_method');
        });
    }
};
