<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_one_id')->index();
            $table->uuid('user_two_id')->index();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamp('user_one_deleted_at')->nullable();
            $table->timestamp('user_two_deleted_at')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->softDeletes();
            $table->timestamps();
            
            // Ensure user_one_id < user_two_id for unique constraint
            // This will be enforced at application level
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};

