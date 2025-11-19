<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\City;
use App\Models\Country;
use App\Models\User;
use Database\Seeders\GeoSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaptainAccountDetailsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(GeoSeeder::class);
    }

    public function test_trainer_can_fetch_account_details(): void
    {
        $trainer = User::factory()->create([
            'phone_with_cc' => '+201555500001',
        ]);
        $trainer->assignRole('TRAINER');
        $token = $trainer->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/captain/account-details')
            ->assertOk()
            ->assertJsonPath('data.has_driving_license', false)
            ->assertJsonPath('data.guidelines.title', 'تنبيه هام');
    }

    public function test_trainer_can_update_account_details(): void
    {
        $trainer = User::factory()->create([
            'phone_with_cc' => '+201555500002',
        ]);
        $trainer->assignRole('TRAINER');
        $token = $trainer->createToken('test')->plainTextToken;

        $country = Country::query()->first();
        $city = City::query()->where('country_id', $country->id)->first();

        $payload = [
            'bio' => 'مدربة معتمدة منذ عام 2017 ولدي خبرة في التدريب العملي والنظري.',
            'car_type' => 'تويوتا كامري',
            'car_model_year' => '2022',
            'has_driving_license' => true,
            'country_id' => $country->id,
            'city_id' => $city->id,
            'car_available' => true,
            'pickup_available' => false,
        ];

        $this->withToken($token)
            ->postJson('/api/v1/captain/account-details', $payload)
            ->assertOk()
            ->assertJsonPath('data.car_type', 'تويوتا كامري')
            ->assertJsonPath('data.city.id', $city->id)
            ->assertJsonPath('data.is_complete', true);

        $this->assertDatabaseHas('trainer_profiles', [
            'user_id' => $trainer->id,
            'car_type' => 'تويوتا كامري',
            'car_model_year' => '2022',
            'has_driving_license' => true,
        ]);
    }

    public function test_non_trainer_cannot_access_account_details(): void
    {
        $user = User::factory()->create([
            'phone_with_cc' => '+201555500003',
        ]);
        $user->assignRole('USER');
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/captain/account-details')
            ->assertStatus(403);
    }
}
