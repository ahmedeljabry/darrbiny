<?php

declare(strict_types=1);

namespace App\Modules\Requests\Http\Controllers;

use App\Models\TrainerProfile;
use App\Models\UserRequest;
use App\Modules\Requests\Http\Requests\CheckTrainerAvailabilityRequest;
use App\Modules\Requests\Http\Requests\StoreUserRequest;
use App\Modules\Requests\Http\Resources\SubscriptionResource;
use App\Modules\Requests\Http\Resources\UserRequestResource;
use App\Modules\Requests\Services\RequestService;
use App\Modules\Requests\Services\TrainerAvailabilityService;
use App\Support\TrainerLocationMatcher;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class UserRequestController extends BaseController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly RequestService $service,
        private readonly TrainerAvailabilityService $trainerAvailabilityService
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $mine = $request->boolean('mine');
        if (! $user->hasRole('ADMIN')) {
            $mine = true;
        }
        $trainerId = $request->query('trainer_id');
        $trainerName = $request->query('trainer_name');

        $q = UserRequest::with(['user', 'country', 'plan', 'plan.country', 'offers.trainer']);

        if ($mine) {
            $q->where('user_id', $user->id);
        }

        if ($trainerId) {
            $q->where(function ($query) use ($trainerId) {
                $query->whereHas('offers', function ($offerQuery) use ($trainerId) {
                    $offerQuery->where('trainer_id', $trainerId);
                })
                    ->orWhereHas('trainingDays', function ($trainingQuery) use ($trainerId) {
                        $trainingQuery->where('trainer_id', $trainerId);
                    })
                    ->orWhere('trainer_id', $trainerId);
            });
        }

        if ($trainerName) {
            $q->where(function ($query) use ($trainerName) {
                $query->whereHas('offers.trainer', function ($trainerQuery) use ($trainerName) {
                    $trainerQuery->where('name', 'like', "%{$trainerName}%");
                })
                    ->orWhereHas('trainingDays.trainer', function ($trainerQuery) use ($trainerName) {
                        $trainerQuery->where('name', 'like', "%{$trainerName}%");
                    });
            });
        }

        $bookings = $q->latest()->paginate(20);

        return UserRequestResource::collection($bookings)->response();
    }

    public function show(string $id)
    {
        $req = UserRequest::with([
            'user',
            'country',
            'user.rates',
            'trainer',
            'trainer.trainerProfile',
            'plan',
            'plan.country',
            'plan.features',
            'offers',
            'offers.trainer',
            'payments',
            'trainingDays',
        ])->findOrFail($id);
        $this->authorize('view', $req);

        return response()->json(['data' => new UserRequestResource($req)]);
    }

    public function store(StoreUserRequest $request)
    {
        $req = $this->service->create($request->validated(), $request->user()->id);
        $relationships = ['user', 'country', 'plan', 'plan.country'];
        if ($req->trainer_id) {
            $relationships[] = 'trainer';
        }
        $req->load($relationships);

        return response()->json(['data' => new UserRequestResource($req)], 201);
    }

    public function checkTrainerAvailability(CheckTrainerAvailabilityRequest $request)
    {
        $available = $this->trainerAvailabilityService->hasEligibleTrainerForLocation($request->validated());

        return response()->json([
            'data' => [
                'available' => $available,
                'message' => $available ? null : RequestService::NO_TRAINERS_AVAILABLE_MESSAGE,
            ],
        ]);
    }

    /**
     * Get awaiting offer requests for a specific trainer.
     */
    public function trainerBookings(Request $request, string $trainerId)
    {
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $viewer = $request->user();
        $canSeeOpenRequests = $viewer->hasRole('ADMIN') || strcasecmp((string) $viewer->id, $trainerId) === 0;
        $profile = $canSeeOpenRequests
            ? TrainerProfile::where('user_id', $trainerId)->first()
            : null;
        $canSeeLocationMatchedOpenRequests = $canSeeOpenRequests
            && $profile instanceof TrainerProfile
            && TrainerLocationMatcher::hasBaseLocation($profile);

        $q = UserRequest::with([
            'user',
            'country',
            'plan',
            'plan.country',
            'offers' => function ($query) use ($trainerId) {
                $query->where('trainer_id', $trainerId);
            },
            'offers.trainer',
            'trainingDays' => function ($query) use ($trainerId) {
                $query->where('trainer_id', $trainerId);
            },
        ])
            ->where(function (
                $query
            ) use (
                $trainerId,
                $canSeeLocationMatchedOpenRequests,
                $profile
            ) {
                $query->whereHas('offers', function ($offerQuery) use ($trainerId) {
                    $offerQuery->where('trainer_id', $trainerId);
                })
                    ->orWhereHas('trainingDays', function ($trainingQuery) use ($trainerId) {
                        $trainingQuery->where('trainer_id', $trainerId);
                    })
                    ->orWhere('trainer_id', $trainerId);
                if ($canSeeLocationMatchedOpenRequests) {
                    $query->orWhere(function ($openQuery) use ($profile) {
                        TrainerLocationMatcher::applyOpenRequestScope($openQuery, $profile);
                    });
                }
            });

        $q->where('status', UserRequest::STATUS_AWAITING_OFFERS);

        // Filter by date range
        if ($dateFrom) {
            $q->whereDate('start_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $q->whereDate('start_date', '<=', $dateTo);
        }

        $bookings = $q->latest()->paginate(20);

        return UserRequestResource::collection($bookings)->response();
    }

    /**
     * Get user subscriptions with status filtering (active, pending, completed)
     * For mobile app subscriptions screen with tabs
     */
    public function subscriptions(Request $request)
    {
        $statusCategory = $request->query('status');
        $userId = $request->user()->id;
        $q = UserRequest::with([
            'user',
            'country',
            'trainer',
            'trainer.trainerProfile',
            'plan',
            'plan.country',
            'plan.scheduleItems',
            'offers',
            'offers.trainer',
            'offers.trainer.trainerProfile',
            'cancellationRequest',
            'scheduleProgress',
        ])
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhere('trainer_id', $userId);
            });
        match ($statusCategory) {
            'active' => $q->where('status', UserRequest::STATUS_IN_TRAINING),
            'completed' => $q->where('status', UserRequest::STATUS_COMPLETED),
            'pending' => $q->whereIn('status', [
                UserRequest::STATUS_PENDING_PAYMENT,
                UserRequest::STATUS_AWAITING_OFFERS,
                UserRequest::STATUS_OFFER_SELECTED,
                UserRequest::STATUS_PAID,
                UserRequest::STATUS_CANCELLED,
            ]),
            default => null,
        };
        $subscriptions = $q->latest()->paginate(20);

        return SubscriptionResource::collection($subscriptions)->response();
    }

    public function complete(Request $request, string $id)
    {
        $req = UserRequest::findOrFail($id);
        $this->authorize('complete', $req);
        $this->service->complete($req, $request->user());

        return response()->json(['data' => $req->fresh()]);
    }
}
