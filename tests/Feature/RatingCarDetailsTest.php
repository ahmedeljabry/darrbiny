<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Rating;
use App\Models\TrainerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RatingCarDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_ratings_endpoint_returns_car_details_from_model_and_year_fields(): void
    {
        $viewer = User::factory()->create(['phone_with_cc' => '+10000001001']);
        $trainer = User::factory()->create(['phone_with_cc' => '+10000001002']);
        $rater = User::factory()->create(['phone_with_cc' => '+10000001003']);

        TrainerProfile::create([
            'user_id' => $trainer->id,
            'car_type' => 'Toyota',
            'car_model' => 'Camry',
            'car_year' => 2024,
            'car_model_year' => null,
            'car_available' => true,
        ]);

        Rating::create([
            'user_id' => $rater->id,
            'trainer_id' => $trainer->id,
            'user_request_id' => (string) Str::uuid(),
            'stars' => 5,
            'comment' => 'Great trainer',
        ]);

        $token = $viewer->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/ratings?trainer_id=' . $trainer->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.trainer.car.type', 'Toyota')
            ->assertJsonPath('data.0.trainer.car.model', 'Camry')
            ->assertJsonPath('data.0.trainer.car.year', 2024)
            ->assertJsonPath('data.0.trainer.car.model_year', '2024')
            ->assertJsonPath('data.0.trainer.car.name', 'Toyota Camry 2024');
    }
}

