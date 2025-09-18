<?php

declare(strict_types=1);

namespace App\Modules\Offers\Services;

use App\Models\TrainerOffer;
use App\Models\TrainerProfile;
use App\Models\UserRequest;
use App\Modules\Requests\Services\RequestService;
use Illuminate\Support\Facades\DB;

class OfferService
{
    public function __construct(private readonly RequestService $requests) {}

    public function create(string $trainerId, array $data): TrainerOffer
    {
        $req = UserRequest::findOrFail($data['user_request_id']);
        $profile = TrainerProfile::where('user_id', $trainerId)->firstOrFail();

        // Eligibility: match location with request's plan
        if ($profile->city_id && $req->plan->city_id && $profile->city_id !== $req->plan->city_id) {
            abort(422, 'Trainer not eligible for this city');
        }
        if ($profile->country_id && $req->plan->country_id && $profile->country_id !== $req->plan->country_id) {
            abort(422, 'Trainer not eligible for this country');
        }

        return DB::transaction(function () use ($trainerId, $data) {
            $offer = TrainerOffer::create([
                'user_request_id' => $data['user_request_id'],
                'trainer_id' => $trainerId,
                'price_minor' => (int) $data['price_minor'],
                'message' => $data['message'] ?? null,
                'status' => TrainerOffer::STATUS_SENT,
            ]);
            return $offer;
        });
    }

    public function accept(UserRequest $req, TrainerOffer $offer): void
    {
        abort_unless($req->id === $offer->user_request_id, 422, 'Offer does not belong to request');
        $this->requests->selectOffer($req, $offer);
    }
}

