<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\NotifyEligibleTrainers;
use App\Models\Country;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FreeRetryRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_plan_and_same_trainer_after_cancellation_creates_free_retry(): void
    {
        Bus::fake();

        $plan = $this->createPlan();
        $user = User::factory()->create(['phone_with_cc' => '+10000007001']);
        $trainer = User::factory()->create(['phone_with_cc' => '+10000007002']);

        $cancelled = $this->createCancelledRequest($user->id, $trainer->id, $plan->id);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/user-requests', [
            'plan_id' => $plan->id,
            'trainer_id' => $trainer->id,
            'country_id' => $plan->country_id,
            'area_level_1' => 'Test Region',
            'area_level_2' => 'Test City',
            'area_level_3' => 'Test District',
            'locality' => 'Test Locality',
            'start_date' => now()->addDay()->toDateString(),
            'start_time' => '09:00',
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', UserRequest::STATUS_IN_TRAINING)
            ->assertJsonPath('data.is_free_retry', true)
            ->assertJsonPath('data.retry_source_request_id', $cancelled->id);

        $newRequestId = $response->json('data.id');

        $this->assertDatabaseHas('user_requests', [
            'id' => $newRequestId,
            'status' => UserRequest::STATUS_IN_TRAINING,
            'retry_source_request_id' => $cancelled->id,
            'app_fee_reserved_minor' => 0,
            'total_paid_minor' => 0,
        ]);

        Bus::assertNotDispatched(NotifyEligibleTrainers::class);
    }

    public function test_free_retry_credit_is_consumed_once_then_falls_back_to_paid_flow(): void
    {
        Bus::fake();

        $plan = $this->createPlan();
        $user = User::factory()->create(['phone_with_cc' => '+10000008001']);
        $trainer = User::factory()->create(['phone_with_cc' => '+10000008002']);

        $this->createCancelledRequest($user->id, $trainer->id, $plan->id);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/user-requests', [
            'plan_id' => $plan->id,
            'trainer_id' => $trainer->id,
            'country_id' => $plan->country_id,
            'area_level_1' => 'Test Region',
            'area_level_2' => 'Test City',
            'area_level_3' => null,
            'locality' => null,
            'start_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'has_user_car' => true,
            'wants_trainer_car' => false,
            'needs_pickup' => false,
        ])->assertCreated()
            ->assertJsonPath('data.status', UserRequest::STATUS_IN_TRAINING)
            ->assertJsonPath('data.is_free_retry', true);

        $second = $this->postJson('/api/v1/user-requests', [
            'plan_id' => $plan->id,
            'trainer_id' => $trainer->id,
            'country_id' => $plan->country_id,
            'area_level_1' => 'Test Region',
            'area_level_2' => 'Test City',
            'area_level_3' => 'Another District',
            'locality' => 'Another Locality',
            'start_date' => now()->addDays(2)->toDateString(),
            'start_time' => '11:00',
            'has_user_car' => true,
            'wants_trainer_car' => false,
            'needs_pickup' => true,
        ]);

        $second->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', UserRequest::STATUS_PENDING_PAYMENT)
            ->assertJsonPath('data.is_free_retry', false)
            ->assertJsonPath('data.retry_source_request_id', null);

        Bus::assertDispatched(NotifyEligibleTrainers::class);
    }

    private function createPlan(): Plan
    {
        $country = Country::create([
            'name' => 'Test Country',
            'iso2' => 'TC',
            'currency' => 'USD',
        ]);

        return Plan::create([
            'title' => 'Retry Plan',
            'description' => 'Plan for retry tests',
            'price_min' => 100,
            'duration_days' => '3',
            'hours_count' => 6,
            'country_id' => $country->id,
            'is_active' => true,
        ]);
    }

    private function createCancelledRequest(string $userId, string $trainerId, string $planId): UserRequest
    {
        return UserRequest::create([
            'user_id' => $userId,
            'trainer_id' => $trainerId,
            'plan_id' => $planId,
            'start_date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'description' => 'Cancelled request',
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
            'status' => UserRequest::STATUS_CANCELLED,
            'currency' => 'USD',
            'app_fee_reserved_minor' => 0,
            'total_paid_minor' => 0,
        ]);
    }
}
