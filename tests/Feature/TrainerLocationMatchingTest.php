<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserRequest;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainerLocationMatchingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_trainer_bookings_include_open_requests_for_overlapping_locations_ignoring_locality(): void
    {
        [$country, $plan] = $this->createCountryAndPlan();
        $trainer = $this->createTrainerWithProfile($country->id);
        $token = $trainer->createToken('trainer')->plainTextToken;

        $matchingRequest = $this->createUserRequest($plan, [
            'status' => UserRequest::STATUS_AWAITING_OFFERS,
            'country_id' => $country->id,
            'area_level_1' => 'Riyadh Province',
            'area_level_2' => 'Riyadh',
            'area_level_3' => 'North',
            'locality' => 'Different Locality',
        ]);

        $differentAreaThreeRequest = $this->createUserRequest($plan, [
            'status' => UserRequest::STATUS_AWAITING_OFFERS,
            'country_id' => $country->id,
            'area_level_1' => 'Riyadh Province',
            'area_level_2' => 'Riyadh',
            'area_level_3' => 'South',
            'locality' => 'Trainer Locality',
        ]);

        $missingAreaThreeRequest = $this->createUserRequest($plan, [
            'status' => UserRequest::STATUS_PENDING_PAYMENT,
            'country_id' => $country->id,
            'area_level_1' => 'Riyadh Province',
            'area_level_2' => 'Riyadh',
            'area_level_3' => null,
            'locality' => 'Trainer Locality',
        ]);

        $normalizedLocationRequest = $this->createUserRequest($plan, [
            'status' => UserRequest::STATUS_AWAITING_OFFERS,
            'country_id' => $country->id,
            'area_level_1' => '  riyadh province  ',
            'area_level_2' => 'riyadh',
            'area_level_3' => ' north ',
            'locality' => 'Spaced Locality',
        ]);

        $assignedRequest = $this->createUserRequest($plan, [
            'trainer_id' => $trainer->id,
            'status' => UserRequest::STATUS_IN_TRAINING,
            'country_id' => $country->id,
            'area_level_1' => 'Another Region',
            'area_level_2' => 'Another City',
            'area_level_3' => 'Another District',
            'locality' => 'Another Locality',
        ]);

        $this->withToken($token)
            ->getJson('/api/v1/trainers/' . $trainer->id . '/bookings')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $matchingRequest->id,
                'order_number' => $matchingRequest->order_number,
                'formatted_order_number' => $matchingRequest->formatted_order_number,
            ])
            ->assertJsonFragment([
                'id' => $assignedRequest->id,
                'order_number' => $assignedRequest->order_number,
                'formatted_order_number' => $assignedRequest->formatted_order_number,
            ])
            ->assertJsonFragment(['id' => $matchingRequest->id])
            ->assertJsonFragment(['id' => $missingAreaThreeRequest->id])
            ->assertJsonFragment(['id' => $normalizedLocationRequest->id])
            ->assertJsonFragment(['id' => $assignedRequest->id])
            ->assertJsonMissing(['id' => $differentAreaThreeRequest->id]);
    }

    public function test_trainer_bookings_pending_filter_returns_awaiting_offer_requests(): void
    {
        [$country, $plan] = $this->createCountryAndPlan();
        $trainer = $this->createTrainerWithProfile($country->id);
        $token = $trainer->createToken('trainer')->plainTextToken;

        $awaitingOffersRequest = $this->createUserRequest($plan, [
            'status' => UserRequest::STATUS_AWAITING_OFFERS,
            'country_id' => $country->id,
        ]);

        $pendingPaymentRequest = $this->createUserRequest($plan, [
            'status' => UserRequest::STATUS_PENDING_PAYMENT,
            'country_id' => $country->id,
        ]);

        $activeRequest = $this->createUserRequest($plan, [
            'trainer_id' => $trainer->id,
            'status' => UserRequest::STATUS_IN_TRAINING,
            'country_id' => $country->id,
        ]);

        $this->withToken($token)
            ->getJson('/api/v1/trainers/' . $trainer->id . '/bookings?status=pending')
            ->assertOk()
            ->assertJsonFragment(['id' => $awaitingOffersRequest->id])
            ->assertJsonFragment(['id' => $pendingPaymentRequest->id])
            ->assertJsonMissing(['id' => $activeRequest->id]);

        $this->withToken($token)
            ->getJson('/api/v1/trainers/' . $trainer->id . '/bookings?status=awaiting_offers')
            ->assertOk()
            ->assertJsonFragment(['id' => $awaitingOffersRequest->id])
            ->assertJsonMissing(['id' => $pendingPaymentRequest->id])
            ->assertJsonMissing(['id' => $activeRequest->id]);
    }

    public function test_trainer_can_create_offer_when_only_locality_differs(): void
    {
        [$country, $plan] = $this->createCountryAndPlan();
        $trainer = $this->createTrainerWithProfile($country->id);
        $token = $trainer->createToken('trainer')->plainTextToken;

        $request = $this->createUserRequest($plan, [
            'status' => UserRequest::STATUS_AWAITING_OFFERS,
            'country_id' => $country->id,
            'area_level_1' => 'Riyadh Province',
            'area_level_2' => 'Riyadh',
            'area_level_3' => 'North',
            'locality' => 'Different Locality',
        ]);

        $this->withToken($token)
            ->postJson('/api/v1/trainer/offers', [
                'user_request_id' => $request->id,
                'price_minor' => 25000,
                'message' => 'عرض مناسب',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.user_request_id', $request->id)
            ->assertJsonPath('data.trainer_id', $trainer->id);

        $this->assertDatabaseHas('trainer_offers', [
            'user_request_id' => $request->id,
            'trainer_id' => $trainer->id,
            'price_minor' => 25000,
        ]);
    }

    public function test_trainer_can_create_offer_when_optional_district_is_missing_on_request(): void
    {
        [$country, $plan] = $this->createCountryAndPlan();
        $trainer = $this->createTrainerWithProfile($country->id);
        $token = $trainer->createToken('trainer')->plainTextToken;

        $request = $this->createUserRequest($plan, [
            'status' => UserRequest::STATUS_AWAITING_OFFERS,
            'country_id' => $country->id,
            'area_level_1' => 'Riyadh Province',
            'area_level_2' => 'Riyadh',
            'area_level_3' => null,
            'locality' => 'Trainer Locality',
        ]);

        $this->withToken($token)
            ->postJson('/api/v1/trainer/offers', [
                'user_request_id' => $request->id,
                'price_minor' => 25000,
                'message' => 'عرض مناسب',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.user_request_id', $request->id)
            ->assertJsonPath('data.trainer_id', $trainer->id);
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
            'description' => 'Plan for trainer location matching tests',
            'price_min' => 150,
            'duration_days' => '3',
            'hours_count' => 12,
            'country_id' => $country->id,
            'is_active' => true,
        ]);

        return [$country, $plan];
    }

    private function createTrainerWithProfile(string $countryId): User
    {
        $trainer = User::factory()->create([
            'phone_with_cc' => fake()->unique()->numerify('+966511000###'),
        ]);
        $trainer->assignRole('TRAINER');
        $trainer->trainerProfile()->create([
            'country_id' => $countryId,
            'area_level_1' => 'Riyadh Province',
            'area_level_2' => 'Riyadh',
            'area_level_3' => 'North',
            'locality' => 'Trainer Locality',
        ]);

        return $trainer;
    }

    private function createUserRequest(Plan $plan, array $overrides = []): UserRequest
    {
        $user = User::factory()->create([
            'phone_with_cc' => fake()->unique()->numerify('+966500000###'),
        ]);

        return UserRequest::create(array_merge([
            'user_id' => $user->id,
            'trainer_id' => null,
            'plan_id' => $plan->id,
            'country_id' => $plan->country_id,
            'area_level_1' => 'Riyadh Province',
            'area_level_2' => 'Riyadh',
            'area_level_3' => 'North',
            'locality' => 'Trainer Locality',
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
