<?php

declare(strict_types=1);

namespace App\Modules\Requests\Http\Controllers;

use App\Models\UserRequest;
use App\Modules\Requests\Services\UserScheduleService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class TrainerScheduleController extends BaseController
{
    public function __construct(private readonly UserScheduleService $service) {}

    /**
     * Trainer sends schedule item for today
     */
    public function send(Request $request, string $id, int $dayNumber)
    {
        $userRequest = UserRequest::with(['offers'])->findOrFail($id);
        $trainer = $request->user();
        
        // Verify trainer is assigned to this request
        $hasAcceptedOffer = $userRequest->offers()
            ->where('trainer_id', $trainer->id)
            ->where('status', \App\Models\TrainerOffer::STATUS_ACCEPTED)
            ->exists();
        
        abort_unless($hasAcceptedOffer, 403, 'Unauthorized: Trainer not assigned to this request');

        // Validate course is in training
        abort_unless(
            $userRequest->status === UserRequest::STATUS_IN_TRAINING,
            422,
            'Course must be in training to send schedule items'
        );

        $progress = $this->service->sendScheduleItem($userRequest, $dayNumber, $trainer->id);

        return response()->json([
            'data' => [
                'id' => $progress->id,
                'day_number' => $progress->day_number,
                'status' => $progress->status,
                'sent_at' => $progress->sent_at?->toIso8601String(),
            ],
        ], 201);
    }
}

