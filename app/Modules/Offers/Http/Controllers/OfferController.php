<?php

declare(strict_types=1);

namespace App\Modules\Offers\Http\Controllers;

use App\Models\TrainerOffer;
use App\Models\UserRequest;
use App\Modules\Offers\Http\Requests\StoreOfferRequest;
use App\Modules\Offers\Http\Requests\UpdateOfferRequest;
use App\Modules\Offers\Http\Resources\UserRequestOfferResource;
use App\Modules\Offers\Services\OfferService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class OfferController extends BaseController
{
    public function __construct(private readonly OfferService $service) {}

    public function listForUserRequests(Request $request)
    {
        $user = $request->user();
        $requestId = $request->query('user_request_id') ?? $request->query('request_id');
        $status = $request->query('status');
        $perPage = (int) $request->query('per_page', 20);

        $q = TrainerOffer::query()
            ->select(['id', 'user_request_id', 'trainer_id', 'price_minor', 'message', 'status', 'created_at'])
            ->with([
                'trainer:id,name,profile_picture_id',
                'trainer.profilePicture',
                'trainer.trainerProfile:id,user_id,bio,car_available,car_type,car_model_year,rating_avg,rating_count',
                'userRequest:id,user_id,plan_id,start_date,start_time,needs_pickup,description,currency',
                'userRequest.plan:id,title,duration_days,hours_count,country_id,city_id',
                'userRequest.plan.country:id,name',
                'userRequest.plan.city:id,name',
            ])
            ->when(!$user->hasRole('ADMIN'), function ($qq) use ($user) {
                $qq->whereHas('userRequest', function ($reqQuery) use ($user) {
                    $reqQuery->where('user_id', $user->id);
                });
            })
            ->when($requestId, fn ($qq) => $qq->where('user_request_id', $requestId))
            ->when($status, fn ($qq) => $qq->where('status', $status));

        $offers = $q->latest()->paginate($perPage)->withQueryString();

        return UserRequestOfferResource::collection($offers)->response();
    }

    public function listForRequest(string $id)
    {
        $req = UserRequest::find($id);
        if (!$req) {
            abort(404, 'Course not found');
        }
        $this->authorize('view', $req);
        $offers = TrainerOffer::where('user_request_id', $id)->latest()->get();
        return response()->json(['data' => $offers]);
    }

    public function store(StoreOfferRequest $request)
    {
        $offer = $this->service->create($request->user()->id, $request->validated());
        return response()->json(['data' => $offer], 201);
    }

    public function update(UpdateOfferRequest $request, string $id)
    {
        $offer = TrainerOffer::find($id);
        if (!$offer) {
            abort(404, 'Offer not found');
        }
        $user = $request->user();
        abort_unless($offer->trainer_id === $user->id, 403, 'You do not own this offer');

        $offer = $this->service->update($offer, $request->validated());
        return response()->json(['data' => $offer]);
    }

    public function accept(Request $request, string $id)
    {
        $offer = TrainerOffer::find($id);
        if (!$offer) {
            abort(404, 'Offer not found');
        }
        $req = UserRequest::find($offer->user_request_id);
        if (!$req) {
            abort(404, 'Course not found');
        }
        $this->service->accept($req, $offer);
        return response()->json(['data' => $req->fresh()]);
    }
}
