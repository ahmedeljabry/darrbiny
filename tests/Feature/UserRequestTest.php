<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Plan;
use App\Models\User;
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
}
