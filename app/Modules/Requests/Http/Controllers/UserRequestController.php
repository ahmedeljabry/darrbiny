<?php

declare(strict_types=1);

namespace App\Modules\Requests\Http\Controllers;

use App\Models\Payout;
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
        $mine = $request->boolean('mine');
        $trainerId = $request->query('trainer_id');
        $trainerName = $request->query('trainer_name');

        $q = UserRequest::with(['user', 'plan', 'plan.country', 'plan.city', 'offers.trainer']);

        if ($mine) {
            $q->where('user_id', $request->user()->id);
        }

        if ($trainerId) {
            $q->where(function ($query) use ($trainerId) {
                $query->whereHas('offers', function ($offerQuery) use ($trainerId) {
                    $offerQuery->where('trainer_id', $trainerId);
                })
                ->orWhereHas('trainingDays', function ($trainingQuery) use ($trainerId) {
                    $trainingQuery->where('trainer_id', $trainerId);
                });
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
            'plan',
            'plan.country',
            'plan.city',
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
        $relationships = ['user', 'plan', 'plan.country', 'plan.city'];
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

        $q = UserRequest::with([
            'user',
            'plan',
            'plan.country',
            'plan.city',
            'offers' => function ($query) use ($trainerId) {
                $query->where('trainer_id', $trainerId);
            },
            'offers.trainer',
            'trainingDays' => function ($query) use ($trainerId) {
                $query->where('trainer_id', $trainerId);
            }
        ])
        ->where(function ($query) use ($trainerId) {
            $query->whereHas('offers', function ($offerQuery) use ($trainerId) {
                $offerQuery->where('trainer_id', $trainerId);
            })
            ->orWhereHas('trainingDays', function ($trainingQuery) use ($trainerId) {
                $trainingQuery->where('trainer_id', $trainerId);
            });
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

        $q = UserRequest::with([
            'user',
            'plan',
            'plan.country',
            'plan.city',
            'plan.scheduleItems',
            'offers',
            'offers.trainer',
            'offers.trainer.trainerProfile',
            'cancellationRequest',
            'scheduleProgress',
        ])
        ->where('user_id', $request->user()->id)->orWhere('trainer_id', $request->user()->id);

        if ($statusCategory === 'active') {
            $q->where('status', UserRequest::STATUS_IN_TRAINING);
        } elseif ($statusCategory === 'completed') {
            $q->where('status', UserRequest::STATUS_COMPLETED);
        } elseif ($statusCategory === 'pending') {
            $q->whereIn('status', [
                UserRequest::STATUS_PENDING_PAYMENT,
                UserRequest::STATUS_AWAITING_OFFERS,
                UserRequest::STATUS_OFFER_SELECTED,
                UserRequest::STATUS_PAID,
                UserRequest::STATUS_CANCELLED,
            ]);
        }
        $subscriptions = $q->latest()->paginate(20);

        return SubscriptionResource::collection($subscriptions)->response();
    }

    public function complete(Request $request, string $id)
    {
        $req = UserRequest::findOrFail($id);
        $this->authorize('complete', $req);
        $approvedHours = (int) TrainingDay::where('user_request_id', $req->id)
            ->where('status', TrainingDay::STATUS_APPROVED)
            ->sum('hours_done');
        abort_if($approvedHours < $req->plan->hours_count, 422, 'Not enough hours');
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
}

