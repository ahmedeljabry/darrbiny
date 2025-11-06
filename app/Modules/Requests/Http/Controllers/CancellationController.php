<?php

declare(strict_types=1);

namespace App\Modules\Requests\Http\Controllers;

use App\Models\CancellationRequest;
use App\Models\UserRequest;
use App\Modules\Requests\Http\Resources\SubscriptionResource;
use App\Notifications\CancellationRequestNotification;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class CancellationController extends BaseController
{
    /**
     * User cancels a course with reason
     */
    public function cancel(Request $request, string $id)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $userRequest = UserRequest::with(['user', 'plan'])->findOrFail($id);
        
        // Check if user owns this request
        abort_unless($userRequest->user_id === $request->user()->id, 403, 'Unauthorized');
        
        // Check if already cancelled or has pending cancellation
        abort_if($userRequest->status === UserRequest::STATUS_CANCELLED, 422, 'Course already cancelled');
        
        $existingCancellation = CancellationRequest::where('user_request_id', $id)
            ->where('status', CancellationRequest::STATUS_PENDING)
            ->first();
        abort_if($existingCancellation, 422, 'Cancellation request already pending');

        // Check if course can be cancelled (not completed)
        abort_if($userRequest->status === UserRequest::STATUS_COMPLETED, 422, 'Cannot cancel completed course');

        $cancellationRequest = DB::transaction(function () use ($userRequest, $validated, $request) {
            // Create cancellation request
            $cancellation = CancellationRequest::create([
                'user_request_id' => $userRequest->id,
                'user_id' => $request->user()->id,
                'reason' => $validated['reason'],
                'status' => CancellationRequest::STATUS_PENDING,
            ]);

            // Notify admins
            $admins = \App\Models\User::role('ADMIN')->get();
            Notification::send($admins, new CancellationRequestNotification($cancellation));

            return $cancellation;
        });

        // Reload user request with all relationships for subscription resource
        $userRequest->load([
            'user',
            'plan',
            'plan.country',
            'plan.city',
            'offers',
            'offers.trainer',
            'offers.trainer.trainerProfile',
            'cancellationRequest',
        ]);

        return response()->json([
            'data' => [
                'cancellation' => [
                    'id' => $cancellationRequest->id,
                    'user_request_id' => $cancellationRequest->user_request_id,
                    'status' => $cancellationRequest->status,
                    'reason' => $cancellationRequest->reason,
                    'admin_notes' => $cancellationRequest->admin_notes,
                    'created_at' => $cancellationRequest->created_at->toIso8601String(),
                ],
                'course' => new SubscriptionResource($userRequest),
            ],
        ], 201);
    }

    /**
     * Get cancellation status for a course
     */
    public function status(Request $request, string $id)
    {
        $userRequest = UserRequest::with([
            'user',
            'plan',
            'plan.country',
            'plan.city',
            'offers',
            'offers.trainer',
            'offers.trainer.trainerProfile',
            'cancellationRequest',
            'cancellationRequest.processedBy',
        ])->findOrFail($id);
        
        // Check if user owns this request
        abort_unless($userRequest->user_id === $request->user()->id, 403, 'Unauthorized');

        $cancellation = $userRequest->cancellationRequest;

        if (!$cancellation) {
            return response()->json([
                'data' => [
                    'cancellation' => null,
                    'course' => new SubscriptionResource($userRequest),
                ],
            ]);
        }

        return response()->json([
            'data' => [
                'cancellation' => [
                    'id' => $cancellation->id,
                    'status' => $cancellation->status,
                    'reason' => $cancellation->reason,
                    'admin_notes' => $cancellation->admin_notes,
                    'processed_at' => $cancellation->processed_at?->toIso8601String(),
                    'processed_by' => $cancellation->processedBy ? [
                        'id' => $cancellation->processedBy->id,
                        'name' => $cancellation->processedBy->name,
                    ] : null,
                    'created_at' => $cancellation->created_at->toIso8601String(),
                ],
                'course' => new SubscriptionResource($userRequest),
            ],
        ]);
    }
}

