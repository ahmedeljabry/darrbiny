<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->string('name')->nullable()->after('user_id');
            $table->string('phone_with_cc')->nullable()->after('name');
            $table->string('email')->nullable()->after('phone_with_cc');
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropColumn(['name','phone_with_cc','email']);
        });
    }
};

