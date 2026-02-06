<?php

declare(strict_types=1);

namespace App\Modules\Requests\Http\Controllers;

use App\Models\User;
use App\Models\UserRequest;
use App\Modules\Requests\Services\UserScheduleService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class ScheduleController extends BaseController
{
    public function __construct(private readonly UserScheduleService $service) {}

    /**
     * Get schedule for a subscription
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $planId = $request->input('plan_id') ?? $request->input('planId');
        $userRequestId = $request->input('user_id') ?? $request->input('userRequestId');

        $userRequest = UserRequest::with(['plan.scheduleItems', 'scheduleProgress'])
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('trainer_id', $user->id);
            })
            ->when($userRequestId, fn ($q) => $q->where('id', $userRequestId))
            ->when($planId, fn ($q) => $q->where('plan_id', $planId))
            ->latest()
            ->first();

        if (!$userRequest) {
            return response()->json(['message' => 'Subscription not found'], 404);
        }

        $schedule = $this->service->getSchedule($userRequest, $userRequest->plan_id);

        return response()->json([
            'data' => [
                'user_request_id' => $userRequest->id,
                'plan_id' => $userRequest->plan_id,
                'duration_days' => (int) ($userRequest->plan->duration_days ?? 0),
                'schedule' => $schedule,
            ],
        ]);
    }

    /**
     * Check a schedule day as completed
     */
    public function check(Request $request, string $id, int $dayNumber)
    {
        $userRequest = UserRequest::findOrFail($id);
        $user = $request->user();

        abort_unless(in_array($user->id, [$userRequest->user_id, $userRequest->trainer_id], true), 403, 'Unauthorized');
        $progress = $this->service->checkDay($userRequest, $userRequest->plan_id, $dayNumber, $user);

        return response()->json([
            'data' => [
                'id' => $progress->id,
                'day_number' => $progress->day_number,
                'is_checked' => $progress->is_checked,
                'checked_at' => $progress->checked_at?->toIso8601String(),
                'status' => $progress->status,
                'sent_at' => $progress->sent_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Uncheck a schedule day
     */
    public function uncheck(Request $request, string $id, int $dayNumber)
    {
        $userRequest = UserRequest::findOrFail($id);

        abort_unless(in_array($request->user()->id, [$userRequest->user_id, $userRequest->trainer_id], true), 403, 'Unauthorized');
        $progress = $this->service->uncheckDay($userRequest, $userRequest->plan_id, $dayNumber);

        return response()->json([
            'data' => [
                'id' => $progress->id,
                'day_number' => $progress->day_number,
                'is_checked' => $progress->is_checked,
                'checked_at' => $progress->checked_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Accept a schedule item (user)
     */
    public function accept(Request $request, string $id, int $dayNumber)
    {
        $userRequest = UserRequest::findOrFail($id);
        abort_unless(in_array($request->user()->id, [$userRequest->user_id, $userRequest->trainer_id], true), 403, 'Unauthorized');

        $progress = $this->service->acceptScheduleItem($userRequest, $userRequest->plan_id, $dayNumber);

        return response()->json([
            'data' => [
                'id' => $progress->id,
                'day_number' => $progress->day_number,
                'status' => $progress->status,
                'is_checked' => $progress->is_checked,
                'checked_at' => $progress->checked_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Reject a schedule item with reason (user)
     */
    public function reject(Request $request, string $id, int $dayNumber)
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $userRequest = UserRequest::findOrFail($id);
        abort_unless(in_array($request->user()->id, [$userRequest->user_id, $userRequest->trainer_id], true), 403, 'Unauthorized');

        $progress = $this->service->rejectScheduleItem($userRequest, $userRequest->plan_id, $dayNumber, $request->input('reason'));

        return response()->json([
            'data' => [
                'id' => $progress->id,
                'day_number' => $progress->day_number,
                'status' => $progress->status,
                'rejection_reason' => $progress->rejection_reason,
            ],
        ]);
    }

    /**
     * Rate a schedule item (user)
     */
    public function rate(Request $request, string $id, int $dayNumber)
    {
        $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'rating_titles' => ['nullable', 'array'],
            'rating_titles.*' => ['string', 'max:255'],
            'rating_comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $userRequest = UserRequest::findOrFail($id);
        abort_unless(in_array($request->user()->id, [$userRequest->user_id, $userRequest->trainer_id], true), 403, 'Unauthorized');

        $progress = $this->service->rateScheduleItem(
            $userRequest,
            $dayNumber,
            $request->input('rating'),
            $userRequest->plan_id,
            $request->input('rating_titles'),
            $request->input('rating_comment')
        );

        return response()->json([
            'data' => [
                'id' => $progress->id,
                'day_number' => $progress->day_number,
                'rating' => $progress->rating,
                'rating_titles' => $progress->rating_titles,
                'rating_comment' => $progress->rating_comment,
            ],
        ]);
    }
}
