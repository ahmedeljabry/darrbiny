<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Setting;
use App\Models\User;
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

    public function test_admin_settings_page_has_integration_keys_tab_and_saves_connection_settings(): void
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
            ->assertSee('مفاتيح الربط')
            ->assertSee('name="hypersend_whatsapp_token"', false)
            ->assertSee('name="hypersend_whatsapp_instance_id"', false)
            ->assertSee('name="sms_provider"', false)
            ->assertSee('name="sms_api_key"', false)
            ->assertSee('name="tap_public_key"', false)
            ->assertSee('name="tabby_public_key"', false)
            ->assertSee('name="tabby_merchant_code"', false)
            ->assertSee('name="tabby_base_url"', false)
            ->assertSee('name="tabby_enabled"', false)
            ->assertSee('name="tamara_public_key"', false)
            ->assertSee('name="tamara_base_url"', false)
            ->assertSee('name="tamara_enabled"', false)
            ->assertDontSee('بوابة الدفع: TAP');

        $this->actingAs($admin)
            ->post(route('admin.settings.update'), [
                'hypersend_whatsapp_token' => 'hs_test_token',
                'hypersend_whatsapp_instance_id' => 'instance-123',
                'sms_provider' => 'HyperSMS',
                'sms_api_key' => 'sms-token',
                'sms_sender_id' => 'DARRBINY',
                'sms_base_url' => 'https://sms.example.com',
                'tap_public_key' => 'tap-public',
                'tap_secret_key' => 'tap-secret',
                'tap_webhook_secret' => 'tap-webhook',
                'tabby_public_key' => 'tabby-public',
                'tabby_secret_key' => 'tabby-secret',
                'tabby_webhook_secret' => 'tabby-webhook',
                'tabby_merchant_code' => 'darrbiny',
                'tabby_base_url' => 'https://api.tabby.sa',
                'tabby_enabled' => '0',
                'tamara_public_key' => 'tamara-public',
                'tamara_secret_key' => 'tamara-secret',
                'tamara_webhook_secret' => 'tamara-webhook',
                'tamara_base_url' => 'https://api.tamara.co',
                'tamara_enabled' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('settings', [
            'key' => 'integrations.hypersend.whatsapp.token',
            'value' => 'hs_test_token',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'integrations.hypersend.whatsapp.instance_id',
            'value' => 'instance-123',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'integrations.sms.provider',
            'value' => 'HyperSMS',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'integrations.sms.api_key',
            'value' => 'sms-token',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'integrations.sms.sender_id',
            'value' => 'DARRBINY',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'integrations.sms.base_url',
            'value' => 'https://sms.example.com',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'payment.tap.public_key',
            'value' => 'tap-public',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'payment.tap.secret_key',
            'value' => 'tap-secret',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'payment.tap.webhook_secret',
            'value' => 'tap-webhook',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'payment.tabby.public_key',
            'value' => 'tabby-public',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'payment.tabby.secret_key',
            'value' => 'tabby-secret',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'payment.tabby.webhook_secret',
            'value' => 'tabby-webhook',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'payment.tabby.merchant_code',
            'value' => 'darrbiny',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'payment.tabby.base_url',
            'value' => 'https://api.tabby.sa',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'payment.tabby.enabled',
            'value' => '0',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'payment.tamara.public_key',
            'value' => 'tamara-public',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'payment.tamara.secret_key',
            'value' => 'tamara-secret',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'payment.tamara.webhook_secret',
            'value' => 'tamara-webhook',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'payment.tamara.base_url',
            'value' => 'https://api.tamara.co',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'payment.tamara.enabled',
            'value' => '1',
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

        $paymentMethods = $response->json('data.fees.payment_methods');

        $this->assertSame('tap', $paymentMethods[0]['key'] ?? null);
        $this->assertSame('tabby', $paymentMethods[1]['key'] ?? null);
        $this->assertSame('tamara', $paymentMethods[2]['key'] ?? null);
        $this->assertTrue($paymentMethods[1]['enabled'] ?? false);
        $this->assertTrue($paymentMethods[2]['enabled'] ?? false);
        $this->assertArrayNotHasKey('public_key', $paymentMethods[1] ?? []);
        $this->assertArrayNotHasKey('secret_key', $paymentMethods[1] ?? []);
    }

    public function test_mobile_fees_endpoint_returns_payment_method_visibility_without_gateway_secrets(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        Setting::updateOrCreate(['key' => 'payment.tabby.enabled'], ['value' => '0']);
        Setting::updateOrCreate(['key' => 'payment.tamara.enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'payment.tabby.secret_key'], ['value' => 'tabby-secret']);
        Setting::updateOrCreate(['key' => 'payment.tamara.secret_key'], ['value' => 'tamara-secret']);

        $response = $this->getJson('/api/v1/settings/fees')->assertOk();
        $paymentMethods = $response->json('data.fees.payment_methods');

        $this->assertSame([
            ['key' => 'tap', 'label' => 'تاب', 'enabled' => true],
            ['key' => 'tabby', 'label' => 'تابي', 'enabled' => false],
            ['key' => 'tamara', 'label' => 'تمارا', 'enabled' => true],
        ], $paymentMethods);

        foreach ($paymentMethods as $method) {
            $this->assertArrayNotHasKey('public_key', $method);
            $this->assertArrayNotHasKey('secret_key', $method);
            $this->assertArrayNotHasKey('webhook_secret', $method);
        }
    }

    public function test_mobile_fees_endpoint_disables_bnpl_methods_for_unsupported_country_currency(): void
    {
        Setting::updateOrCreate(['key' => 'payment.tabby.enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'payment.tamara.enabled'], ['value' => '1']);

        $egypt = Country::create([
            'name' => 'Egypt',
            'iso2' => 'EG',
            'currency' => 'EGP',
        ]);
        $saudiArabia = Country::create([
            'name' => 'Saudi Arabia',
            'iso2' => 'SA',
            'currency' => 'SAR',
        ]);

        $egyptMethods = collect($this->getJson('/api/v1/settings/fees?country_id='.$egypt->id)
            ->assertOk()
            ->json('data.fees.payment_methods'))
            ->keyBy('key');

        $this->assertTrue($egyptMethods['tap']['enabled'] ?? false);
        $this->assertFalse($egyptMethods['tabby']['enabled'] ?? true);
        $this->assertFalse($egyptMethods['tamara']['enabled'] ?? true);

        $saudiMethods = collect($this->getJson('/api/v1/settings/fees?country_id='.$saudiArabia->id)
            ->assertOk()
            ->json('data.fees.payment_methods'))
            ->keyBy('key');

        $this->assertTrue($saudiMethods['tabby']['enabled'] ?? false);
        $this->assertTrue($saudiMethods['tamara']['enabled'] ?? false);
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
