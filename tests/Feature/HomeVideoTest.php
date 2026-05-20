<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserType;
use App\Models\Setting;
use App\Models\User;
use App\Support\StorageUrl;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HomeVideoTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_endpoint_returns_both_video_variants(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('public');

        Setting::updateOrCreate(['key' => 'video.app.path'], ['value' => 'videos/user.mp4']);
        Setting::updateOrCreate(['key' => 'video.captain.path'], ['value' => 'videos/captain.mp4']);

        $response = $this->getJson('/api/v1/home');

        $response->assertOk();

        $userUrl = StorageUrl::make('videos/user.mp4');
        $captainUrl = StorageUrl::make('videos/captain.mp4');

        $response->assertJsonPath('data.video.user_url', $userUrl);
        $response->assertJsonPath('data.video.captain_url', $captainUrl);
        $response->assertJsonPath('data.video.url', $userUrl);
    }

    public function test_home_endpoint_uses_captain_type_when_trainer_role_is_missing(): void
    {
        $trainer = User::factory()->create([
            'name' => 'Captain Nora',
            'phone_with_cc' => '+966500000991',
            'user_type' => UserType::CAPTAIN,
        ]);
        $trainer->trainerProfile()->create([
            'pending_approval' => false,
            'rating_avg' => 4.8,
            'rating_count' => 12,
        ]);

        $response = $this->getJson('/api/v1/home');

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $trainer->id,
            'name' => 'Captain Nora',
        ]);
    }
}
