<?php

declare(strict_types=1);

namespace App\Modules\Requests\Http\Controllers;

use App\Models\Payout;
use App\Models\TrainerProfile;
use App\Models\TrainingDay;
use App\Models\UserRequest;
use App\Modules\Requests\Http\Requests\StoreUserRequest;
use App\Modules\Requests\Http\Resources\SubscriptionResource;
use App\Modules\Requests\Http\Resources\UserRequestResource;
use App\Modules\Requests\Services\RequestService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class UserRequestController extends BaseController
{
    use AuthorizesRequests;

    public function __construct(private readonly RequestService $service) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $mine = $request->boolean('mine');
        if (!$user->hasRole('ADMIN')) {
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
            'trainingDays'
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

    /**
     * Get bookings for a specific trainer
     * Shows all bookings where trainer has made offers or is training
     */
    public function trainerBookings(Request $request, string $trainerId)
    {
        $status = $request->query('status');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $viewer = $request->user();
        $canSeeOpenRequests = $viewer->hasRole('ADMIN') || strcasecmp((string) $viewer->id, $trainerId) === 0;
        $profile = $canSeeOpenRequests
            ? TrainerProfile::where('user_id', $trainerId)->first()
            : null;
        $effectiveCountryId = $this->resolveTrainerLocation($profile, 'country_id');
        $effectiveAreaLevelOne = $this->resolveTrainerLocation($profile, 'area_level_1');
        $effectiveAreaLevelTwo = $this->resolveTrainerLocation($profile, 'area_level_2');
        $effectiveAreaLevelThree = $this->resolveTrainerLocation($profile, 'area_level_3');
        $effectiveLocality = $this->resolveTrainerLocation($profile, 'locality');

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
            }
        ])
            ->where(function (
                $query
            ) use (
                $trainerId,
                $canSeeOpenRequests,
                $effectiveCountryId,
                $effectiveAreaLevelOne,
                $effectiveAreaLevelTwo,
                $effectiveAreaLevelThree,
                $effectiveLocality
            ) {
                $query->whereHas('offers', function ($offerQuery) use ($trainerId) {
                    $offerQuery->where('trainer_id', $trainerId);
                })
                    ->orWhereHas('trainingDays', function ($trainingQuery) use ($trainerId) {
                        $trainingQuery->where('trainer_id', $trainerId);
                    })
                    ->orWhere('trainer_id', $trainerId);
                if ($canSeeOpenRequests) {
                    $query->orWhere(function (
                        $openQuery
                    ) use (
                        $effectiveCountryId,
                        $effectiveAreaLevelOne,
                        $effectiveAreaLevelTwo,
                        $effectiveAreaLevelThree,
                        $effectiveLocality
                    ) {
                        $openQuery->whereNull('trainer_id')
                            ->whereIn('status', [
                                UserRequest::STATUS_PENDING_PAYMENT,
                                UserRequest::STATUS_AWAITING_OFFERS,
                            ]);

                        if ($effectiveCountryId) {
                            $openQuery->where(function ($countryQuery) use ($effectiveCountryId) {
                                $countryQuery->whereNull('country_id')
                                    ->orWhere('country_id', $effectiveCountryId);
                            });
                        }
                        if ($effectiveAreaLevelOne) {
                            $openQuery->where(function ($areaOneQuery) use ($effectiveAreaLevelOne) {
                                $areaOneQuery->whereNull('area_level_1')
                                    ->orWhere('area_level_1', $effectiveAreaLevelOne);
                            });
                        }
                        if ($effectiveAreaLevelTwo) {
                            $openQuery->where(function ($areaTwoQuery) use ($effectiveAreaLevelTwo) {
                                $areaTwoQuery->whereNull('area_level_2')
                                    ->orWhere('area_level_2', $effectiveAreaLevelTwo);
                            });
                        }
                        if ($effectiveAreaLevelThree) {
                            $openQuery->where(function ($areaThreeQuery) use ($effectiveAreaLevelThree) {
                                $areaThreeQuery->whereNull('area_level_3')
                                    ->orWhere('area_level_3', $effectiveAreaLevelThree);
                            });
                        }
                        if ($effectiveLocality) {
                            $openQuery->where(function ($localityQuery) use ($effectiveLocality) {
                                $localityQuery->whereNull('locality')
                                    ->orWhere('locality', $effectiveLocality);
                            });
                        }
                    });
                }
            });

        // Filter by status
        if ($status) {
            $q->where('status', $status);
        }

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
        $this->service->complete($req);
        Payout::create([
            'trainer_id' => $request->user()->id,
            'user_request_id' => $req->id,
            'amount_minor' => $req->total_paid_minor - $req->app_fee_reserved_minor,
            'currency' => $req->currency,
            'status' => Payout::STATUS_PENDING_REVIEW,
        ]);
        return response()->json(['data' => $req]);
    }

    private function resolveTrainerLocation(?TrainerProfile $profile, string $field): ?string
    {
        if (!$profile) {
            return null;
        }

        $value = $profile->getAttribute($field);
        if (
            $profile->pending_approval
            && is_array($profile->pending_changes)
            && array_key_exists($field, $profile->pending_changes)
        ) {
            $value = $profile->pending_changes[$field];
        }

        return $value;
    }
}
