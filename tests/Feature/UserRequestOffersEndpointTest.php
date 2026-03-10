<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Plan;
use App\Models\TrainerOffer;
use App\Models\User;
use App\Models\UserRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRequestOffersEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_offers_endpoint_returns_car_preference_flags(): void
    {
        $country = Country::create([
            'name' => 'Test Country',
            'iso2' => 'TC',
            'currency' => 'USD',
        ]);

        $plan = Plan::create([
            'title' => 'Plan A',
            'description' => 'Test plan',
            'price_min' => 150,
            'duration_days' => '3',
            'hours_count' => 12,
            'country_id' => $country->id,
            'is_active' => true,
        ]);

        $requestUser = User::factory()->create(['phone_with_cc' => '+10000002001']);
        $trainer = User::factory()->create(['phone_with_cc' => '+10000002002']);

        $userRequest = UserRequest::create([
            'user_id' => $requestUser->id,
            'trainer_id' => null,
            'plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'status' => UserRequest::STATUS_AWAITING_OFFERS,
            'currency' => 'USD',
            'app_fee_reserved_minor' => 0,
            'total_paid_minor' => 0,
            'has_user_car' => true,
            'wants_trainer_car' => false,
            'needs_pickup' => true,
        ]);

        TrainerOffer::create([
            'user_request_id' => $userRequest->id,
            'trainer_id' => $trainer->id,
            'price_minor' => 25000,
            'message' => 'Offer message',
            'status' => TrainerOffer::STATUS_SENT,
        ]);

        $token = $requestUser->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/user-requests/offers?user_request_id=' . $userRequest->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.request.has_user_car', true)
            ->assertJsonPath('data.0.request.wants_trainer_car', false);
    }
}
