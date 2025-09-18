<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->timestamps();
        });

        Schema::create('uploads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('disk');
            $table->string('path');
            $table->string('mime');
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('uploads');
        Schema::dropIfExists('settings');
    }
};

