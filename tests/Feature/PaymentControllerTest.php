<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\City;
use App\Models\Country;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_plan_payment_respects_plan_partial_type(): void
    {
        Queue::fake();

        $country = Country::create([
            'name' => 'Test Country',
            'iso2' => 'TC',
            'currency' => 'USD',
        ]);
        $city = City::create([
            'name' => 'Test City',
            'country_id' => $country->id,
        ]);
        $plan = Plan::create([
            'title' => 'Plan A',
            'description' => 'Test plan',
            'price_min' => 150,
            'duration_days' => '3',
            'hours_count' => 12,
            'country_id' => $country->id,
            'city_id' => $city->id,
            'is_active' => true,
        ]);

        $user = User::factory()->create(['phone_with_cc' => '+10000003001']);
        $token = $user->createToken('test')->plainTextToken;

        $userRequest = UserRequest::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'status' => UserRequest::STATUS_OFFER_SELECTED,
            'currency' => 'USD',
            'app_fee_reserved_minor' => 0,
            'total_paid_minor' => 0,
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);

        $this->withToken($token)
            ->postJson('/api/v1/payments/plan', [
                'user_request_id' => $userRequest->id,
                'payment_method' => 'wallet',
                'type' => Payment::TYPE_PLAN_PARTIAL,
                'price' => 12345,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', Payment::TYPE_PLAN_PARTIAL);

        $this->assertDatabaseHas('payments', [
            'user_request_id' => $userRequest->id,
            'user_id' => $user->id,
            'type' => Payment::TYPE_PLAN_PARTIAL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 12345,
        ]);

        $this->assertDatabaseHas('user_requests', [
            'id' => $userRequest->id,
            'status' => UserRequest::STATUS_AWAITING_OFFERS,
            'total_paid_minor' => 12345,
        ]);
    }
}
