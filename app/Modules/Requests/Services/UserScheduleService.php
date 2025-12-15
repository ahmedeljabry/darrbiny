<?php

declare(strict_types=1);

namespace App\Modules\Requests\Services;

use App\Models\User;
use App\Models\UserScheduleProgress;
use App\Notifications\ScheduleItemSentNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserScheduleService
{
    /**
     * Get schedule with user's progress
     */
    public function getSchedule(Request $request,User $userRequest): array
    {
        $plan = $request->planId;
        $scheduleItems = \App\Models\PlanScheduleItem::where('plan_id', $plan->id)
            ->ordered()
            ->get();

        $progress = UserScheduleProgress::where('user_request_id', $userRequest->id)
            ->get()
            ->keyBy('plan_schedule_item_id');

        $schedule = [];
        foreach ($scheduleItems as $item) {
            $userProgress = $progress->get($item->id);
            $schedule[] = [
                'id' => $item->id,
                'day_number' => $item->day_number,
                'title' => $item->title,
                'is_checked' => $userProgress ? $userProgress->is_checked : false,
                'checked_at' => $userProgress?->checked_at?->toIso8601String(),
                'progress_id' => $userProgress?->id,
                'status' => $userProgress?->status ?? UserScheduleProgress::STATUS_PENDING,
                'sent_at' => $userProgress?->sent_at?->toIso8601String(),
                'rejection_reason' => $userProgress?->rejection_reason,
                'rating' => $userProgress?->rating,
                'rating_titles' => $userProgress?->rating_titles,
                'rating_comment' => $userProgress?->rating_comment,
            ];
        }

        return $schedule;
    }

    /**
     * Check a schedule day as completed
     */
    public function checkDay($userRequest, $planId, int $dayNumber): UserScheduleProgress
    {
        $plan = $planId;
        $scheduleItem = \App\Models\PlanScheduleItem::where('plan_id', $plan->id)
            ->where('day_number', $dayNumber)
            ->firstOrFail();

        return DB::transaction(function () use ($userRequest, $scheduleItem, $dayNumber) {
            $progress = UserScheduleProgress::firstOrCreate(
                [
                    'user_request_id' => $userRequest->id,
                    'plan_schedule_item_id' => $scheduleItem->id,
                ],
                [
                    'day_number' => $dayNumber,
                    'is_checked' => false,
                ]
            );

            $progress->is_checked = true;
            $progress->checked_at = now();
            $progress->save();

            return $progress;
        });
    }

    /**
     * Uncheck a schedule day
     */
    public function uncheckDay($userRequest, $planId, int $dayNumber): UserScheduleProgress
    {
        $plan = $planId;
        $scheduleItem = \App\Models\PlanScheduleItem::where('plan_id', $plan)
            ->where('day_number', $dayNumber)
            ->firstOrFail();

        $progress = UserScheduleProgress::where('user_request_id', $userRequest->id)
            ->where('plan_schedule_item_id', $scheduleItem->id)
            ->firstOrFail();

        $progress->is_checked = false;
        $progress->checked_at = null;
        $progress->save();

        return $progress;
    }

    /**
     * User accepts schedule item
     */
    public function acceptScheduleItem($userRequest, $planId,int $dayNumber): UserScheduleProgress
    {
        $plan = $planId;
        $scheduleItem = \App\Models\PlanScheduleItem::where('plan_id', $plan)
            ->where('day_number', $dayNumber)
            ->firstOrFail();

        $progress = UserScheduleProgress::where('user_request_id', $userRequest->id)
            ->where('plan_schedule_item_id', $scheduleItem->id)
            ->firstOrFail();

        if ($progress->status !== UserScheduleProgress::STATUS_SENT) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                response()->json(['message' => 'Schedule item must be sent before acceptance'], 422)
            );
        }

        $progress->status = UserScheduleProgress::STATUS_ACCEPTED;
        $progress->is_checked = true;
        $progress->checked_at = now();
        $progress->save();

        return $progress;
    }

    /**
     * User rejects schedule item with reason
     */
    public function rejectScheduleItem($userRequest, $planId,int $dayNumber, string $reason): UserScheduleProgress
    {
        $plan = $planId;
        $scheduleItem = \App\Models\PlanScheduleItem::where('plan_id', $plan)
            ->where('day_number', $dayNumber)
            ->firstOrFail();

        $progress = UserScheduleProgress::where('user_request_id', $userRequest->id)
            ->where('plan_schedule_item_id', $scheduleItem->id)
            ->firstOrFail();

        if ($progress->status !== UserScheduleProgress::STATUS_SENT) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                response()->json(['message' => 'Schedule item must be sent before rejection'], 422)
            );
        }

        $progress->status = UserScheduleProgress::STATUS_REJECTED;
        $progress->rejection_reason = $reason;
        $progress->save();

        return $progress;
    }

    /**
     * User rates schedule item (1-5 stars) with titles and comment
     */
    public function rateScheduleItem(
        $userRequest,
        int $dayNumber,
        int $rating,
        $planId,
        ?array $ratingTitles = null,
        ?string $ratingComment = null
    ): UserScheduleProgress {
        if ($rating < 1 || $rating > 5) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                response()->json(['message' => 'Rating must be between 1 and 5'], 422)
            );
        }

        $plan = $planId;
        $scheduleItem = \App\Models\PlanScheduleItem::where('plan_id', $plan)
            ->where('day_number', $dayNumber)
            ->firstOrFail();

        $progress = UserScheduleProgress::where('user_request_id', $userRequest->id)
            ->where('plan_schedule_item_id', $scheduleItem->id)
            ->firstOrFail();

        if ($progress->status !== UserScheduleProgress::STATUS_ACCEPTED) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                response()->json(['message' => 'Schedule item must be accepted before rating'], 422)
            );
        }

        $progress->rating = $rating;
        $progress->rating_titles = $ratingTitles;
        $progress->rating_comment = $ratingComment;
        $progress->save();

        return $progress;
    }
}

