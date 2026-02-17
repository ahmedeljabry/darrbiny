<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Setting;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeBannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_endpoint_returns_student_and_trainer_banner_texts(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        Setting::updateOrCreate(['key' => 'home.banner.student_text'], ['value' => 'Student banner text']);
        Setting::updateOrCreate(['key' => 'home.banner.trainer_text'], ['value' => 'Trainer banner text']);

        $response = $this->getJson('/api/v1/home');

        $response->assertOk();
        $response->assertJsonPath('data.banner.student_text', 'Student banner text');
        $response->assertJsonPath('data.banner.trainer_text', 'Trainer banner text');
    }
}

