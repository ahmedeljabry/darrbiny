<?php

declare(strict_types=1);

namespace Tests\Feature;

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
        $plan = Plan::first();

        $payload = [
            'plan_id' => $plan->id,
            'start_date' => now()->addDay()->toDateString(),
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ];
        $res = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/user-requests', $payload);
        $res->assertStatus(201)->assertJsonPath('success', true);
    }
}

