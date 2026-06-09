<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Plan;
use App\Models\User;
use App\Modules\Requests\Services\RequestService;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\GeoSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_user_request(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(GeoSeeder::class);
        $this->seed(DemoDataSeeder::class);

        $user = User::first();
        $user->assignRole('USER');
        $token = $user->createToken('t')->plainTextToken;

        $country = Country::firstOrCreate(
            ['iso2' => 'EG'],
            ['name' => 'Egypt', 'currency' => 'EGP']
        );
        $plan = Plan::create([
            'title' => 'Demo Plan',
            'description' => 'Demo plan for request test',
            'price_min' => 100,
            'duration_days' => '3',
            'hours_count' => 6,
            'country_id' => $country->id,
            'is_active' => true,
        ]);
        $this->createEligibleTrainer($country->id, 'Cairo Governorate', 'Cairo');

        $payload = [
            'plan_id' => $plan->id,
            'country_id' => $country->id,
            'area_level_1' => 'Cairo Governorate',
            'area_level_2' => 'Cairo',
            'area_level_3' => null,
            'locality' => 'Nasr City',
            'start_date' => now()->addDay()->toDateString(),
            'start_time' => '09:00',
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ];
        $res = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/user-requests', $payload);
        $res->assertStatus(201)->assertJsonPath('success', true);
    }

    public function test_create_user_request_preserves_start_time_exactly_as_sent(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $country = Country::create([
            'name' => 'Jordan',
            'iso2' => 'JO',
            'currency' => 'JOD',
        ]);
        $plan = Plan::create([
            'title' => 'Mobile Time Plan',
            'description' => 'Plan used to verify mobile start time is preserved',
            'price_min' => 100,
            'duration_days' => '3',
            'hours_count' => 6,
            'country_id' => $country->id,
            'is_active' => true,
        ]);
        $this->createEligibleTrainer($country->id, 'Amman Governorate', 'Amman');

        $user = User::factory()->create([
            'phone_with_cc' => '+10000001005',
        ]);
        $user->assignRole('USER');

        $startTime = '2026-06-10T09:30:00.000Z';

        $response = $this->withHeader('Authorization', 'Bearer '.$user->createToken('t')->plainTextToken)
            ->postJson('/api/v1/user-requests', [
                'plan_id' => $plan->id,
                'country_id' => $country->id,
                'area_level_1' => 'Amman Governorate',
                'area_level_2' => 'Amman',
                'area_level_3' => null,
                'locality' => 'Sweifieh',
                'start_date' => now()->addDay()->toDateString(),
                'start_time' => $startTime,
                'has_user_car' => false,
                'wants_trainer_car' => true,
                'needs_pickup' => false,
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.start_time', $startTime);

        $this->assertDatabaseHas('user_requests', [
            'id' => $response->json('data.id'),
            'start_time' => $startTime,
        ]);
    }

    public function test_create_user_request_fails_when_no_trainer_is_available_in_same_location(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $country = Country::create([
            'name' => 'Jordan',
            'iso2' => 'JO',
            'currency' => 'JOD',
        ]);
        $plan = Plan::create([
            'title' => 'Unavailable Area Plan',
            'description' => 'Plan without trainers in same area',
            'price_min' => 100,
            'duration_days' => '3',
            'hours_count' => 6,
            'country_id' => $country->id,
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'phone_with_cc' => '+10000001001',
        ]);
        $user->assignRole('USER');

        $this->withHeader('Authorization', 'Bearer '.$user->createToken('t')->plainTextToken)
            ->postJson('/api/v1/user-requests', [
                'plan_id' => $plan->id,
                'country_id' => $country->id,
                'area_level_1' => 'Amman Governorate',
                'area_level_2' => 'Amman',
                'area_level_3' => null,
                'locality' => 'Sweifieh',
                'start_date' => now()->addDay()->toDateString(),
                'start_time' => '09:00',
                'has_user_car' => false,
                'wants_trainer_car' => true,
                'needs_pickup' => false,
            ])
            ->assertStatus(422)
            ->assertJsonPath('errors.0.message', RequestService::NO_TRAINERS_AVAILABLE_MESSAGE);
    }

    public function test_trainer_availability_endpoint_returns_available_for_matching_place(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $country = Country::create([
            'name' => 'Jordan',
            'iso2' => 'JO',
            'currency' => 'JOD',
        ]);
        $this->createEligibleTrainer($country->id, 'Amman Governorate', 'Amman');
        $user = User::factory()->create([
            'phone_with_cc' => '+10000001002',
        ]);
        $user->assignRole('USER');

        $this->withHeader('Authorization', 'Bearer '.$user->createToken('t')->plainTextToken)
            ->postJson('/api/v1/user-requests/trainer-availability', [
                'country_id' => $country->id,
                'area_level_1' => 'Amman Governorate',
                'area_level_2' => 'Amman',
                'area_level_3' => null,
                'locality' => 'Sweifieh',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.available', true)
            ->assertJsonPath('data.message', null);
    }

    public function test_trainer_availability_endpoint_returns_message_when_no_trainer_matches_place(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $country = Country::create([
            'name' => 'Jordan',
            'iso2' => 'JO',
            'currency' => 'JOD',
        ]);
        $this->createEligibleTrainer($country->id, 'Irbid Governorate', 'Irbid');
        $user = User::factory()->create([
            'phone_with_cc' => '+10000001003',
        ]);
        $user->assignRole('USER');

        $this->withHeader('Authorization', 'Bearer '.$user->createToken('t')->plainTextToken)
            ->postJson('/api/v1/user-requests/trainer-availability', [
                'country_id' => $country->id,
                'area_level_1' => 'Amman Governorate',
                'area_level_2' => 'Amman',
                'area_level_3' => null,
                'locality' => 'Sweifieh',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.available', false)
            ->assertJsonPath('data.message', RequestService::NO_TRAINERS_AVAILABLE_MESSAGE);
    }

    public function test_trainer_availability_endpoint_does_not_count_banned_trainer(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $country = Country::create([
            'name' => 'Jordan',
            'iso2' => 'JO',
            'currency' => 'JOD',
        ]);
        $trainer = $this->createEligibleTrainer($country->id, 'Amman Governorate', 'Amman');
        $trainer->forceFill(['banned_until' => now()->addDay()])->save();
        $user = User::factory()->create([
            'phone_with_cc' => '+10000001004',
        ]);
        $user->assignRole('USER');

        $this->withHeader('Authorization', 'Bearer '.$user->createToken('t')->plainTextToken)
            ->postJson('/api/v1/user-requests/trainer-availability', [
                'country_id' => $country->id,
                'area_level_1' => 'Amman Governorate',
                'area_level_2' => 'Amman',
                'area_level_3' => null,
                'locality' => 'Sweifieh',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.available', false)
            ->assertJsonPath('data.message', RequestService::NO_TRAINERS_AVAILABLE_MESSAGE);
    }

    public function test_request_currency_uses_plan_country_currency_before_user_currency(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $country = Country::create([
            'name' => 'Jordan',
            'iso2' => 'JO',
            'currency' => 'JOD',
        ]);
        $plan = Plan::create([
            'title' => 'Jordan Plan',
            'description' => 'Plan priced in Jordanian dinar',
            'price_min' => 100,
            'duration_days' => '3',
            'hours_count' => 6,
            'country_id' => $country->id,
            'is_active' => true,
        ]);
        $this->createEligibleTrainer($country->id, 'Amman Governorate', 'Amman');
        $user = User::factory()->create([
            'phone_with_cc' => '+10000001000',
            'currency' => 'USD',
        ]);
        $user->assignRole('USER');

        $response = $this->withHeader('Authorization', 'Bearer '.$user->createToken('t')->plainTextToken)
            ->postJson('/api/v1/user-requests', [
                'plan_id' => $plan->id,
                'country_id' => $country->id,
                'area_level_1' => 'Amman Governorate',
                'area_level_2' => 'Amman',
                'area_level_3' => null,
                'locality' => 'Sweifieh',
                'start_date' => now()->addDay()->toDateString(),
                'start_time' => '09:00',
                'has_user_car' => false,
                'wants_trainer_car' => true,
                'needs_pickup' => false,
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.currency', 'JOD');

        $this->assertDatabaseHas('user_requests', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'currency' => 'JOD',
        ]);
    }

    private function createEligibleTrainer(string $countryId, string $areaLevelOne, string $areaLevelTwo): User
    {
        $trainer = User::factory()->create([
            'phone_with_cc' => fake()->unique()->numerify('+1999000####'),
            'user_type' => 'captain',
        ]);
        $trainer->assignRole('TRAINER');
        $trainer->trainerProfile()->create([
            'country_id' => $countryId,
            'area_level_1' => $areaLevelOne,
            'area_level_2' => $areaLevelTwo,
            'area_level_3' => null,
            'locality' => null,
            'verified_at' => now(),
        ]);

        return $trainer;
    }
}
