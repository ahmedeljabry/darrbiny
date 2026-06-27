<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Models\Country;
use App\Support\PaymentGatewayFees;
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
            ->assertRedirect()
            ->assertSessionHasNoErrors();

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

    public function test_admin_settings_page_has_tabs_and_saves_hypersend_whatsapp_settings(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create([
            'phone_with_cc' => '+10000002001',
            'email' => 'settings-hypersend@example.com',
        ]);
        $admin->assignRole('ADMIN');

        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('data-bs-toggle="tab"', false)
            ->assertSee('name="hypersend_whatsapp_token"', false)
            ->assertSee('name="hypersend_whatsapp_instance_id"', false)
            ->assertDontSee('بوابة الدفع: TAP');

        $this->actingAs($admin)
            ->post(route('admin.settings.update'), [
                'hypersend_whatsapp_token' => 'hs_test_token',
                'hypersend_whatsapp_instance_id' => 'instance-123',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('settings', [
            'key' => 'integrations.hypersend.whatsapp.token',
            'value' => 'hs_test_token',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'integrations.hypersend.whatsapp.instance_id',
            'value' => 'instance-123',
        ]);
    }

    public function test_admin_can_save_payment_gateway_fees_without_exposing_them_to_mobile_fees_endpoint(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $country = Country::create([
            'name' => 'Saudi Arabia',
            'iso2' => 'SA',
            'currency' => 'SAR',
        ]);

        $admin = User::factory()->create([
            'phone_with_cc' => '+10000002003',
            'email' => 'settings-gateways@example.com',
        ]);
        $admin->assignRole('ADMIN');

        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('رسوم وعمولات بوابات الدفع')
            ->assertSee('name="payment_gateway_fees[0][fixed_fee_minor]"', false)
            ->assertSee('تابي')
            ->assertSee('تمارا');

        $this->actingAs($admin)
            ->post(route('admin.settings.update'), [
                'payment_gateway_fees' => [
                    [
                        'gateway' => 'tap',
                        'fixed_fee_minor' => 150,
                        'commission_percent' => 7,
                        'country_id' => $country->id,
                    ],
                    [
                        'gateway' => 'tabby',
                        'fixed_fee_minor' => 150,
                        'commission_percent' => 7,
                        'country_id' => $country->id,
                    ],
                    [
                        'gateway' => 'tamara',
                        'fixed_fee_minor' => 150,
                        'commission_percent' => 7,
                        'country_id' => $country->id,
                    ],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $storedGatewayFees = json_decode(
            (string) Setting::query()->where('key', PaymentGatewayFees::SETTINGS_KEY)->value('value'),
            true
        );

        $this->assertSame('tap', $storedGatewayFees[0]['gateway'] ?? null);
        $this->assertSame(150, $storedGatewayFees[0]['fixed_fee_minor'] ?? null);
        $this->assertSame(7.0, $storedGatewayFees[0]['commission_percent'] ?? null);
        $this->assertSame($country->id, $storedGatewayFees[0]['country_id'] ?? null);
        $this->assertSame('tabby', $storedGatewayFees[1]['gateway'] ?? null);
        $this->assertSame('tamara', $storedGatewayFees[2]['gateway'] ?? null);

        $response = $this->getJson('/api/v1/settings/fees')->assertOk();

        $this->assertArrayNotHasKey('payment_gateways', $response->json('data.fees'));
        $this->assertArrayNotHasKey('gateway_fees', $response->json('data.fees'));
    }

    public function test_admin_can_save_app_usage_pages_and_api_returns_them(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create([
            'phone_with_cc' => '+10000002002',
            'email' => 'settings-app-usage@example.com',
        ]);
        $admin->assignRole('ADMIN');

        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('شرح استخدام التطبيق للمدربة')
            ->assertSee('شرح استخدام التطبيق للطالبة');

        $this->actingAs($admin)
            ->post(route('admin.settings.update'), [
                'page_app_usage_trainer_faqs' => [
                    ['question' => 'كيف تبدأ المدربة؟', 'answer' => 'تقبلي الطلب ثم تابعي مراحل التدريب.'],
                ],
                'page_app_usage_student_faqs' => [
                    ['question' => 'كيف تبدأ الطالبة؟', 'answer' => 'اختاري الباقة وأرسلي الطلب للمدربات المتاحات.'],
                ],
            ])
            ->assertRedirect();

        $trainerPage = json_decode((string) Setting::query()->where('key', 'pages.app_usage_trainer')->value('value'), true);
        $studentPage = json_decode((string) Setting::query()->where('key', 'pages.app_usage_student')->value('value'), true);

        $this->assertSame('كيف تبدأ المدربة؟', $trainerPage[0]['question'] ?? null);
        $this->assertSame('تقبلي الطلب ثم تابعي مراحل التدريب.', $trainerPage[0]['answer'] ?? null);
        $this->assertSame('كيف تبدأ الطالبة؟', $studentPage[0]['question'] ?? null);
        $this->assertSame('اختاري الباقة وأرسلي الطلب للمدربات المتاحات.', $studentPage[0]['answer'] ?? null);

        $this->getJson('/api/v1/settings/pages')
            ->assertOk()
            ->assertJsonPath('data.pages.app_usage_trainer.0.question', 'كيف تبدأ المدربة؟')
            ->assertJsonPath('data.pages.app_usage_student.0.question', 'كيف تبدأ الطالبة؟')
            ->assertJsonPath('data.pages.trainer_usage_guide.0.answer', 'تقبلي الطلب ثم تابعي مراحل التدريب.')
            ->assertJsonPath('data.pages.student_usage_guide.0.answer', 'اختاري الباقة وأرسلي الطلب للمدربات المتاحات.');
    }
}
