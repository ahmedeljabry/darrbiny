<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('countries', function (Blueprint $table): void {
            $table->decimal('vat_percent', 5, 2)->default(0)->after('reservation_fee_minor');
        });
    }

    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table): void {
            $table->dropColumn('vat_percent');
        });
    }
};
