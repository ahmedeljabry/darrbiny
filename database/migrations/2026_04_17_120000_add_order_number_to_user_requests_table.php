<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ORDER_NUMBER_START = 5000;
    private const SEQUENCE_KEY = 'sequences.user_requests.order_number';

    public function up(): void
    {
        Schema::table('user_requests', function (Blueprint $table): void {
            $table->unsignedBigInteger('order_number')->nullable()->after('id');
        });

        $nextOrderNumber = self::ORDER_NUMBER_START;

        DB::table('user_requests')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id'])
            ->each(function (object $request) use (&$nextOrderNumber): void {
                DB::table('user_requests')
                    ->where('id', $request->id)
                    ->update(['order_number' => $nextOrderNumber++]);
            });

        $lastAssignedOrderNumber = max(self::ORDER_NUMBER_START - 1, $nextOrderNumber - 1);
        $timestamp = now();

        DB::table('settings')->updateOrInsert(
            ['key' => self::SEQUENCE_KEY],
            [
                'value' => (string) $lastAssignedOrderNumber,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]
        );

        Schema::table('user_requests', function (Blueprint $table): void {
            $table->unique('order_number', 'user_requests_order_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('user_requests', function (Blueprint $table): void {
            $table->dropUnique('user_requests_order_number_unique');
            $table->dropColumn('order_number');
        });

        DB::table('settings')
            ->where('key', self::SEQUENCE_KEY)
            ->delete();
    }
};
