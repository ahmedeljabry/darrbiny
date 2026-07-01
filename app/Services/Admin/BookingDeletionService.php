<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\UserRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class BookingDeletionService
{
    /**
     * @param  array<int, string>  $bookingIds
     */
    public function deleteMany(array $bookingIds): int
    {
        $ids = collect($bookingIds)
            ->map(fn ($id) => trim((string) $id))
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return 0;
        }

        return DB::transaction(function () use ($ids): int {
            $existingIds = UserRequest::query()
                ->whereIn('id', $ids)
                ->lockForUpdate()
                ->pluck('id');

            if ($existingIds->isEmpty()) {
                return 0;
            }

            $this->deleteRelatedRows($existingIds);

            return UserRequest::query()
                ->whereIn('id', $existingIds)
                ->delete();
        });
    }

    private function deleteRelatedRows(Collection $bookingIds): void
    {
        DB::table('user_requests')
            ->whereIn('retry_source_request_id', $bookingIds)
            ->update(['retry_source_request_id' => null]);

        foreach ([
            'ratings',
            'payouts',
            'cancellation_requests',
            'user_schedule_progress',
            'training_days',
            'trainer_offers',
            'payments',
        ] as $table) {
            DB::table($table)
                ->whereIn('user_request_id', $bookingIds)
                ->delete();
        }
    }
}
