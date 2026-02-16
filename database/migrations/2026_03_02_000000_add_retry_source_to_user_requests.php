<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_requests', function (Blueprint $table) {
            $table->uuid('retry_source_request_id')->nullable()->unique()->after('total_paid_minor');
            $table->foreign('retry_source_request_id')
                ->references('id')
                ->on('user_requests')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('user_requests', function (Blueprint $table) {
            $table->dropForeign(['retry_source_request_id']);
            $table->dropUnique(['retry_source_request_id']);
            $table->dropColumn('retry_source_request_id');
        });
    }
};
