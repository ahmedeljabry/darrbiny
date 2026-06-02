<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ORDER_NUMBER_START = 5000;
    private const SEQUENCE_KEY = 'sequences.user_requests.order_number';

    public function up(): void
    {
        if (! Schema::hasColumn('user_requests', 'order_number')) {
            return;
        }

        DB::transaction(function (): void {
            $nextOrderNumber = $this->nextOrderNumber();

            DB::table('user_requests')
                ->whereNull('order_number')
                ->orderBy('created_at')
                ->orderBy('id')
                ->get(['id'])
                ->each(function (object $request) use (&$nextOrderNumber): void {
                    DB::table('user_requests')
                        ->where('id', $request->id)
                        ->update(['order_number' => $nextOrderNumber++]);
                });

            $this->syncSequence($nextOrderNumber - 1);
        });
    }

    public function down(): void
    {
        // This migration repairs production data and intentionally keeps assigned order numbers.
    }

    private function nextOrderNumber(): int
    {
        $currentMax = (int) (DB::table('user_requests')->max('order_number') ?? 0);
        $sequenceValue = (int) (DB::table('settings')
            ->where('key', self::SEQUENCE_KEY)
            ->value('value') ?? 0);

        return max(self::ORDER_NUMBER_START - 1, $currentMax, $sequenceValue) + 1;
    }

    private function syncSequence(int $lastAssignedOrderNumber): void
    {
        $lastAssignedOrderNumber = max(
            self::ORDER_NUMBER_START - 1,
            $lastAssignedOrderNumber,
            (int) (DB::table('user_requests')->max('order_number') ?? 0)
        );
        $timestamp = now();

        DB::table('settings')->updateOrInsert(
            ['key' => self::SEQUENCE_KEY],
            [
                'value' => (string) $lastAssignedOrderNumber,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]
        );
    }
};
