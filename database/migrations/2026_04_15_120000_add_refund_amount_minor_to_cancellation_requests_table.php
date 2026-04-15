<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cancellation_requests', function (Blueprint $table): void {
            $table->unsignedInteger('refund_amount_minor')->default(0)->after('admin_notes');
        });
    }

    public function down(): void
    {
        Schema::table('cancellation_requests', function (Blueprint $table): void {
            $table->dropColumn('refund_amount_minor');
        });
    }
};
