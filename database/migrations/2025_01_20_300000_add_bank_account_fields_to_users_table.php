<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('bank_account')->nullable()->after('points_balance');
            $table->string('iban')->nullable()->after('bank_account');
            $table->string('bank_name')->nullable()->after('iban');
            $table->uuid('bank_country_id')->nullable()->index()->after('bank_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['bank_account', 'iban', 'bank_name', 'bank_country_id']);
        });
    }
};

