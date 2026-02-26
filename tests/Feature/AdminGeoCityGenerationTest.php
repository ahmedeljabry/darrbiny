<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminGeoCityGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_generate_cities_with_gpt_endpoint(): void
    {
        config()->set('services.openai.api_key', 'test-openai-key');
        config()->set('services.openai.base_url', 'https://api.openai.com/v1');
        config()->set('services.openai.model', 'gpt-4o-mini');

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => '{"cities":["Riyadh","Jeddah","Dammam","Riyadh"]}',
                    ],
                ]],
            ], 200),
        ]);

        $admin = User::factory()->create([
            'phone_with_cc' => '+10000009001',
            'email' => 'admin-geo-gpt@example.com',
        ]);
        $admin->assignRole('ADMIN');

        $this->actingAs($admin)
            ->postJson(route('admin.geo.cities.generate'), [
                'name' => 'Saudi Arabia',
                'iso2' => 'sa',
                'currency' => 'sar',
            ])
            ->assertOk()
            ->assertJsonPath('data.count', 3)
            ->assertJsonPath('data.cities.0', 'Riyadh')
            ->assertJsonPath('data.cities.1', 'Jeddah')
            ->assertJsonPath('data.cities.2', 'Dammam');
    }

    public function test_generate_cities_returns_error_when_openai_key_missing(): void
    {
        config()->set('services.openai.api_key', '');

        $admin = User::factory()->create([
            'phone_with_cc' => '+10000009002',
            'email' => 'admin-geo-gpt-missing-key@example.com',
        ]);
        $admin->assignRole('ADMIN');

        $this->actingAs($admin)
            ->postJson(route('admin.geo.cities.generate'), [
                'name' => 'Saudi Arabia',
                'iso2' => 'SA',
                'currency' => 'SAR',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'تعذر توليد المدن حالياً. تحقق من إعدادات OpenAI ثم حاول مرة أخرى.');
    }
}

