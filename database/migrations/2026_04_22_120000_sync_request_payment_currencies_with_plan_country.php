<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $lastId = null;

        do {
            $rows = DB::table('user_requests')
                ->join('plans', 'plans.id', '=', 'user_requests.plan_id')
                ->join('countries', 'countries.id', '=', 'plans.country_id')
                ->select([
                    'user_requests.id',
                    'user_requests.currency',
                    'countries.currency as country_currency',
                ])
                ->when($lastId, fn ($query) => $query->where('user_requests.id', '>', $lastId))
                ->whereNotNull('countries.currency')
                ->orderBy('user_requests.id')
                ->limit(500)
                ->get();

            foreach ($rows as $row) {
                $lastId = (string) $row->id;
                $currency = strtoupper(trim((string) $row->country_currency));

                if ($currency === '' || strtoupper(trim((string) $row->currency)) === $currency) {
                    continue;
                }

                DB::table('user_requests')
                    ->where('id', $row->id)
                    ->update(['currency' => $currency]);

                DB::table('payments')
                    ->where('user_request_id', $row->id)
                    ->update(['currency' => $currency]);
            }
        } while ($rows->isNotEmpty());
    }

    public function down(): void
    {
        //
    }
};
