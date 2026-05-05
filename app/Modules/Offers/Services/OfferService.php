<?php

declare(strict_types=1);

namespace App\Modules\Offers\Services;

use App\Models\TrainerOffer;
use App\Models\TrainerProfile;
use App\Models\UserRequest;
use App\Modules\Requests\Services\RequestService;
use App\Notifications\TrainerOfferAcceptedNotification;
use App\Notifications\TrainerOfferSentNotification;
use Illuminate\Support\Facades\DB;

class OfferService
{
    public function __construct(private readonly RequestService $requests) {}

    public function create(string $trainerId, array $data): TrainerOffer
    {
        $req = UserRequest::findOrFail($data['user_request_id']);
        $profile = TrainerProfile::where('user_id', $trainerId)->firstOrFail();

        if ($profile->country_id && $profile->country_id !== $req->country_id) {
            abort(422, 'Trainer not eligible for this country');
        }
        if ($profile->area_level_1 && $profile->area_level_1 !== $req->area_level_1) {
            abort(422, 'Trainer not eligible for this location');
        }
        if ($profile->area_level_2 && $profile->area_level_2 !== $req->area_level_2) {
            abort(422, 'Trainer not eligible for this location');
        }
        if ($profile->area_level_3 && $profile->area_level_3 !== $req->area_level_3) {
            abort(422, 'Trainer not eligible for this location');
        }

        $offer = DB::transaction(function () use ($trainerId, $data) {
            $offer = TrainerOffer::create([
                'user_request_id' => $data['user_request_id'],
                'trainer_id' => $trainerId,
                'price_minor' => (int) $data['price_minor'],
                'message' => $data['message'] ?? null,
                'status' => TrainerOffer::STATUS_SENT,
            ]);

            return $offer;
        });

        $notificationOffer = $offer->fresh(['trainer', 'userRequest']);
        if ($notificationOffer) {
            $req->user?->notify(new TrainerOfferSentNotification($notificationOffer));
        }

        return $offer;
    }

    public function accept(UserRequest $req, TrainerOffer $offer): void
    {
        abort_unless($req->id === $offer->user_request_id, 422, 'Offer does not belong to request');
        $wasAccepted = $offer->status === TrainerOffer::STATUS_ACCEPTED;

        $this->requests->selectOffer($req, $offer);

        if (! $wasAccepted) {
            $acceptedOffer = $offer->fresh(['trainer', 'userRequest.user']);
            if ($acceptedOffer) {
                $acceptedOffer->trainer?->notify(new TrainerOfferAcceptedNotification($acceptedOffer));
            }
        }
    }

    public function update(TrainerOffer $offer, array $data): TrainerOffer
    {
        abort_unless($offer->status === TrainerOffer::STATUS_SENT, 422, 'Offer cannot be updated');

        if (array_key_exists('price_minor', $data)) {
            $offer->price_minor = (int) $data['price_minor'];
        }
        if (array_key_exists('message', $data)) {
            $offer->message = $data['message'];
        }

        $offer->save();

        return $offer->fresh();
    }
}
