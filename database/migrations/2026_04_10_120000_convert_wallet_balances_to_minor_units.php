<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            DB::table('users')->update([
                'points_balance' => DB::raw('points_balance * 100'),
            ]);
        }

        if (Schema::hasTable('wallet_transactions')) {
            DB::table('wallet_transactions')->update([
                'amount' => DB::raw('amount * 100'),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            DB::table('users')->update([
                'points_balance' => DB::raw('ROUND(points_balance / 100.0, 0)'),
            ]);
        }

        if (Schema::hasTable('wallet_transactions')) {
            DB::table('wallet_transactions')->update([
                'amount' => DB::raw('ROUND(amount / 100.0, 0)'),
            ]);
        }
    }
};
