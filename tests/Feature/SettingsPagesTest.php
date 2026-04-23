<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_pages_endpoint_returns_contact_page_content(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        Setting::updateOrCreate(['key' => 'pages.contact'], ['value' => 'Email support@example.com']);

        $response = $this->getJson('/api/v1/settings/pages');

        $response->assertOk();
        $response->assertJsonPath('data.pages.contact', 'Email support@example.com');
        $response->assertJsonPath('data.pages.contact_us', 'Email support@example.com');
        $response->assertJsonPath('data.pages.contact-us', 'Email support@example.com');
    }

    public function test_admin_can_save_multiple_report_exchange_rates(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create([
            'phone_with_cc' => '+10000002000',
            'email' => 'settings-rates@example.com',
        ]);
        $admin->assignRole('ADMIN');

        $this->actingAs($admin)
            ->post(route('admin.settings.update'), [
                'report_exchange_rates' => [
                    ['currency' => 'EGP', 'rate' => '0.080000'],
                    ['currency' => 'JOD', 'rate' => '5.290000'],
                ],
            ])
            ->assertRedirect();

        $rates = json_decode(
            (string) Setting::query()->where('key', 'reports.exchange_rates_to_sar')->value('value'),
            true
        );

        $this->assertEquals(0.08, $rates['EGP'] ?? null);
        $this->assertEquals(5.29, $rates['JOD'] ?? null);

        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('5.290000', false);
    }
}
