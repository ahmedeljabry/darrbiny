<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\TrainerOffer;
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
        $plan = Plan::create([
            'title' => 'Plan A',
            'description' => 'Test plan',
            'price_min' => 150,
            'duration_days' => '3',
            'hours_count' => 12,
            'country_id' => $country->id,
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
            'app_fee_minor' => 0,
            'trainer_net_minor' => 12345,
        ]);

        $this->assertDatabaseHas('user_requests', [
            'id' => $userRequest->id,
            'status' => UserRequest::STATUS_AWAITING_OFFERS,
            'total_paid_minor' => 12345,
        ]);
    }

    public function test_plan_full_payment_applies_app_fee_percent_from_settings(): void
    {
        Queue::fake();

        Setting::create([
            'key' => 'fees.app_fee_percent',
            'value' => '15',
        ]);

        $country = Country::create([
            'name' => 'Test Country 2',
            'iso2' => 'T2',
            'currency' => 'USD',
        ]);
        $plan = Plan::create([
            'title' => 'Plan B',
            'description' => 'Test plan B',
            'price_min' => 200,
            'duration_days' => '5',
            'hours_count' => 20,
            'country_id' => $country->id,
            'is_active' => true,
        ]);

        $user = User::factory()->create(['phone_with_cc' => '+10000003002']);
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

        $trainer = User::factory()->create(['phone_with_cc' => '+10000003003']);

        TrainerOffer::create([
            'user_request_id' => $userRequest->id,
            'trainer_id' => $trainer->id,
            'price_minor' => 10000,
            'message' => 'Accepted trainer offer',
            'status' => TrainerOffer::STATUS_ACCEPTED,
        ]);

        $this->withToken($token)
            ->postJson('/api/v1/payments/plan', [
                'user_request_id' => $userRequest->id,
                'payment_method' => 'wallet',
                'type' => Payment::TYPE_PLAN_FULL,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', Payment::TYPE_PLAN_FULL)
            ->assertJsonPath('data.app_fee', 1500)
            ->assertJsonPath('data.trainer_net', 8500);

        $this->assertDatabaseHas('payments', [
            'user_request_id' => $userRequest->id,
            'user_id' => $user->id,
            'type' => Payment::TYPE_PLAN_FULL,
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 10000,
            'app_fee_minor' => 1500,
            'trainer_net_minor' => 8500,
        ]);

        $this->assertDatabaseHas('user_requests', [
            'id' => $userRequest->id,
            'status' => UserRequest::STATUS_IN_TRAINING,
            'app_fee_reserved_minor' => 1500,
            'total_paid_minor' => 10000,
        ]);
    }
}
