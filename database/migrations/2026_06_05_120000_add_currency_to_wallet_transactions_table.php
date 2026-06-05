<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wallet_transactions') || Schema::hasColumn('wallet_transactions', 'currency')) {
            return;
        }

        Schema::table('wallet_transactions', function (Blueprint $table): void {
            $table->char('currency', 3)->nullable()->index()->after('amount');
        });

        DB::table('wallet_transactions')
            ->select(['id', 'user_id'])
            ->orderBy('id')
            ->get()
            ->each(function (object $transaction): void {
                $currency = DB::table('users')
                    ->leftJoin('countries as user_countries', 'user_countries.id', '=', 'users.country_id')
                    ->leftJoin('countries as bank_countries', 'bank_countries.id', '=', 'users.bank_country_id')
                    ->where('users.id', $transaction->user_id)
                    ->value(DB::raw("
                        COALESCE(
                            NULLIF(users.currency, ''),
                            NULLIF(user_countries.currency, ''),
                            NULLIF(bank_countries.currency, ''),
                            'SAR'
                        )
                    "));

                DB::table('wallet_transactions')
                    ->where('id', $transaction->id)
                    ->update(['currency' => $currency ?: 'SAR']);
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('wallet_transactions') || ! Schema::hasColumn('wallet_transactions', 'currency')) {
            return;
        }

        Schema::table('wallet_transactions', function (Blueprint $table): void {
            $table->dropColumn('currency');
        });
    }
};
