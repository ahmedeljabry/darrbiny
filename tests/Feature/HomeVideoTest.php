<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Setting;
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

        $userUrl = Storage::disk('public')->url('videos/user.mp4');
        $captainUrl = Storage::disk('public')->url('videos/captain.mp4');

        $response->assertJsonPath('data.video.user_url', $userUrl);
        $response->assertJsonPath('data.video.captain_url', $captainUrl);
        $response->assertJsonPath('data.video.url', $userUrl);
    }
}
