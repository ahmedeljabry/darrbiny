<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRequestOrderNumberApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_request_store_returns_order_number_fields(): void
    {
        [$country, $plan] = $this->createCountryAndPlan();
        $user = User::factory()->create([
            'phone_with_cc' => '+966500001001',
        ]);

        $response = $this->withToken($user->createToken('test')->plainTextToken)
            ->postJson('/api/v1/user-requests', [
                'plan_id' => $plan->id,
                'country_id' => $country->id,
                'area_level_1' => 'Riyadh Province',
                'area_level_2' => 'Riyadh',
                'area_level_3' => 'North',
                'locality' => 'Al Olaya',
                'start_date' => now()->addDay()->toDateString(),
                'start_time' => '09:00',
                'has_user_car' => false,
                'wants_trainer_car' => true,
                'needs_pickup' => false,
            ]);

        $createdRequest = UserRequest::query()->findOrFail($response->json('data.id'));

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.order_number', $createdRequest->order_number)
            ->assertJsonPath('data.formatted_order_number', $createdRequest->formatted_order_number);
    }

    public function test_user_request_index_and_show_return_order_number_fields(): void
    {
        [$country, $plan] = $this->createCountryAndPlan();
        $user = User::factory()->create([
            'phone_with_cc' => '+966500001002',
        ]);

        $userRequest = $this->createUserRequest($user, $plan, [
            'country_id' => $country->id,
            'order_number' => 6123,
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/user-requests')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'id' => $userRequest->id,
                'order_number' => $userRequest->order_number,
                'formatted_order_number' => $userRequest->formatted_order_number,
            ]);

        $this->withToken($token)
            ->getJson('/api/v1/user-requests/' . $userRequest->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.order_number', $userRequest->order_number)
            ->assertJsonPath('data.formatted_order_number', $userRequest->formatted_order_number);
    }

    public function test_subscriptions_endpoint_returns_order_number_fields(): void
    {
        [$country, $plan] = $this->createCountryAndPlan();
        $user = User::factory()->create([
            'phone_with_cc' => '+966500001003',
        ]);

        $subscription = $this->createUserRequest($user, $plan, [
            'country_id' => $country->id,
            'order_number' => 7345,
            'status' => UserRequest::STATUS_IN_TRAINING,
        ]);

        $this->withToken($user->createToken('test')->plainTextToken)
            ->getJson('/api/v1/subscriptions')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'id' => $subscription->id,
                'order_number' => $subscription->order_number,
                'formatted_order_number' => $subscription->formatted_order_number,
            ])
            ->assertJsonPath('data.0.course_id', $subscription->order_number)
            ->assertJsonPath('data.0.course_number', $subscription->formatted_order_number)
            ->assertJsonPath('data.0.course_details.course_id', '#' . $subscription->formatted_order_number)
            ->assertJsonPath('data.0.course_details.course_number', $subscription->formatted_order_number)
            ->assertJsonPath('data.0.course_details.order_number', $subscription->order_number)
            ->assertJsonPath('data.0.course_details.formatted_order_number', $subscription->formatted_order_number);
    }

    private function createCountryAndPlan(): array
    {
        $country = Country::create([
            'name' => 'Saudi Arabia',
            'iso2' => 'SA',
            'currency' => 'SAR',
        ]);

        $plan = Plan::create([
            'title' => 'Plan A',
            'description' => 'Plan for API order number tests',
            'price_min' => 150,
            'duration_days' => '3',
            'hours_count' => 12,
            'country_id' => $country->id,
            'is_active' => true,
        ]);

        return [$country, $plan];
    }

    private function createUserRequest(User $user, Plan $plan, array $overrides = []): UserRequest
    {
        return UserRequest::create(array_merge([
            'user_id' => $user->id,
            'trainer_id' => null,
            'plan_id' => $plan->id,
            'country_id' => $plan->country_id,
            'area_level_1' => 'Riyadh Province',
            'area_level_2' => 'Riyadh',
            'area_level_3' => 'North',
            'locality' => 'Al Olaya',
            'start_date' => now()->addDay()->toDateString(),
            'start_time' => '09:00:00',
            'status' => UserRequest::STATUS_AWAITING_OFFERS,
            'currency' => 'SAR',
            'app_fee_reserved_minor' => 0,
            'total_paid_minor' => 0,
            'has_user_car' => false,
            'wants_trainer_car' => false,
            'needs_pickup' => false,
        ], $overrides));
    }
}
