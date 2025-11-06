<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_schedule_progress', function (Blueprint $table) {
            $table->string('status')->default('pending')->index()->after('is_checked'); // pending, sent, accepted, rejected
            $table->timestamp('sent_at')->nullable()->after('status');
            $table->text('rejection_reason')->nullable()->after('sent_at');
            $table->unsignedTinyInteger('rating')->nullable()->after('rejection_reason'); // 1-5 stars
            $table->json('rating_titles')->nullable()->after('rating'); // Array of rating criteria/titles
            $table->text('rating_comment')->nullable()->after('rating_titles');
        });
    }

    public function down(): void
    {
        Schema::table('user_schedule_progress', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'sent_at',
                'rejection_reason',
                'rating',
                'rating_titles',
                'rating_comment',
            ]);
        });
    }
};

