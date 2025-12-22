<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('trainer_profiles', function (Blueprint $table) {
            $table->boolean('pending_approval')->default(false)->after('verified_at');
            $table->text('pending_changes')->nullable()->after('pending_approval');
            $table->timestamp('pending_approval_at')->nullable()->after('pending_changes');
        });
    }

    public function down(): void
    {
        Schema::table('trainer_profiles', function (Blueprint $table) {
            $table->dropColumn(['pending_approval', 'pending_changes', 'pending_approval_at']);
        });
    }
};
