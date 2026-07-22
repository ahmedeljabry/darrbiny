<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\TrainerOffer;
use App\Models\User;
use App\Models\UserRequest;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_plan_payment_respects_plan_partial_type(): void
    {
        Queue::fake();

        $country = Country::create([
            'name' => 'Test Country',
            'iso2' => 'TC',
            'currency' => 'USD',
        ]);
        $plan = Plan::create([
            'title' => 'Plan A',
            'description' => 'Test plan',
            'price_min' => 150,
            'duration_days' => '3',
            'hours_count' => 12,
            'country_id' => $country->id,
            'is_active' => true,
        ]);

        $user = User::factory()->create(['phone_with_cc' => '+10000003001']);
        $token = $user->createToken('test')->plainTextToken;
        $user->update(['points_balance' => 200]);

        $userRequest = UserRequest::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'status' => UserRequest::STATUS_OFFER_SELECTED,
            'currency' => 'USD',
            'app_fee_reserved_minor' => 0,
            'total_paid_minor' => 0,
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);

        $this->withToken($token)
            ->postJson('/api/v1/payments/plan', [
                'user_request_id' => $userRequest->id,
                'payment_method' => 'wallet',
                'type' => Payment::TYPE_PLAN_PARTIAL,
                'price' => 12345,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', Payment::TYPE_PLAN_PARTIAL);

        $this->assertDatabaseHas('payments', [
            'user_request_id' => $userRequest->id,
            'user_id' => $user->id,
            'type' => Payment::TYPE_PLAN_PARTIAL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 12345,
            'app_fee_minor' => 0,
            'trainer_net_minor' => 12345,
        ]);

        $this->assertDatabaseHas('user_requests', [
            'id' => $userRequest->id,
            'status' => UserRequest::STATUS_AWAITING_OFFERS,
            'total_paid_minor' => 12345,
        ]);

        $this->assertEquals(76.55, $user->fresh()->points_balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'amount' => 12345,
            'type' => WalletTransaction::TYPE_PAYMENT,
            'status' => WalletTransaction::STATUS_APPROVED,
        ]);
    }

    public function test_plan_partial_payment_accepts_decimal_minor_price(): void
    {
        Queue::fake();

        $country = Country::create([
            'name' => 'Decimal Price Country',
            'iso2' => 'SA',
            'currency' => 'SAR',
        ]);
        $plan = Plan::create([
            'title' => 'Decimal Price Plan',
            'description' => 'Test decimal minor price',
            'price_min' => 150,
            'duration_days' => '3',
            'hours_count' => 12,
            'country_id' => $country->id,
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'phone_with_cc' => '+96650003003',
            'points_balance' => 200,
        ]);
        $userRequest = UserRequest::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'country_id' => $country->id,
            'start_date' => now()->toDateString(),
            'status' => UserRequest::STATUS_PENDING_PAYMENT,
            'currency' => 'SAR',
            'app_fee_reserved_minor' => 0,
            'total_paid_minor' => 0,
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);

        $this->withToken($user->createToken('student')->plainTextToken)
            ->postJson('/api/v1/payments/plan', [
                'user_request_id' => $userRequest->id,
                'payment_method' => Payment::METHOD_WALLET,
                'type' => Payment::TYPE_PLAN_PARTIAL,
                'price' => 12345.67,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.amount_minor', 12346);

        $this->assertDatabaseHas('payments', [
            'user_request_id' => $userRequest->id,
            'type' => Payment::TYPE_PLAN_PARTIAL,
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 12346,
        ]);
        $this->assertDatabaseHas('user_requests', [
            'id' => $userRequest->id,
            'status' => UserRequest::STATUS_AWAITING_OFFERS,
            'total_paid_minor' => 12346,
        ]);
    }

    public function test_plan_full_payment_can_pay_sent_offer_and_accepts_it_on_success(): void
    {
        Queue::fake();

        $country = Country::create([
            'name' => 'Offer Required Country',
            'iso2' => 'SA',
            'currency' => 'SAR',
        ]);
        $plan = Plan::create([
            'title' => 'Offer Required Plan',
            'description' => 'Plan requiring an accepted offer before full payment',
            'price_min' => 1200,
            'duration_days' => '3',
            'hours_count' => 12,
            'country_id' => $country->id,
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'phone_with_cc' => '+96650003001',
            'points_balance' => 2000,
        ]);
        $trainer = User::factory()->create(['phone_with_cc' => '+96650003002']);
        $userRequest = UserRequest::create([
            'user_id' => $user->id,
            'trainer_id' => null,
            'plan_id' => $plan->id,
            'country_id' => $country->id,
            'start_date' => now()->toDateString(),
            'status' => UserRequest::STATUS_AWAITING_OFFERS,
            'currency' => 'SAR',
            'app_fee_reserved_minor' => 0,
            'total_paid_minor' => 3500,
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);
        $offer = TrainerOffer::create([
            'user_request_id' => $userRequest->id,
            'trainer_id' => $trainer->id,
            'price_minor' => 120000,
            'message' => 'Pending offer',
            'status' => TrainerOffer::STATUS_SENT,
        ]);

        $this->withToken($user->createToken('student')->plainTextToken)
            ->postJson('/api/v1/payments/plan', [
                'user_request_id' => $userRequest->id,
                'payment_method' => Payment::METHOD_WALLET,
                'type' => Payment::TYPE_PLAN_FULL,
                'offer_id' => $offer->id,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', Payment::TYPE_PLAN_FULL)
            ->assertJsonPath('data.amount_minor', 120000)
            ->assertJsonPath('data.status', Payment::STATUS_SUCCEEDED);

        $this->assertDatabaseHas('payments', [
            'user_request_id' => $userRequest->id,
            'type' => Payment::TYPE_PLAN_FULL,
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 120000,
        ]);
        $this->assertDatabaseHas('trainer_offers', [
            'id' => $offer->id,
            'status' => TrainerOffer::STATUS_ACCEPTED,
        ]);
        $this->assertDatabaseHas('user_requests', [
            'id' => $userRequest->id,
            'trainer_id' => $trainer->id,
            'status' => UserRequest::STATUS_IN_TRAINING,
            'total_paid_minor' => 120000,
        ]);
    }

    public function test_plan_payment_accepts_tabby_and_tamara_gateway_methods(): void
    {
        Queue::fake();
        Http::fake([
            'https://tabby.test/api/v2/checkout' => Http::response([
                'status' => 'created',
                'payment' => ['id' => 'tabby-payment-123', 'status' => 'CREATED'],
                'configuration' => [
                    'available_products' => [
                        'installments' => [[
                            'web_url' => 'https://checkout.tabby.test/session/123',
                        ]],
                    ],
                ],
            ]),
            'https://tamara.test/checkout' => Http::response([
                'order_id' => 'tamara-order-123',
                'checkout_id' => 'tamara-checkout-123',
                'status' => 'new',
                'checkout_url' => 'https://checkout.tamara.test/session/123',
            ]),
        ]);

        Setting::updateOrCreate(['key' => 'payment.tabby.secret_key'], ['value' => 'tabby-secret']);
        Setting::updateOrCreate(['key' => 'payment.tabby.merchant_code'], ['value' => 'darrbiny']);
        Setting::updateOrCreate(['key' => 'payment.tabby.base_url'], ['value' => 'https://tabby.test']);
        Setting::updateOrCreate(['key' => 'payment.tamara.secret_key'], ['value' => 'tamara-secret']);
        Setting::updateOrCreate(['key' => 'payment.tamara.base_url'], ['value' => 'https://tamara.test']);

        $country = Country::create([
            'name' => 'Gateway Country',
            'iso2' => 'GC',
            'currency' => 'SAR',
            'vat_percent' => 15.0,
        ]);
        $plan = Plan::create([
            'title' => 'Gateway Plan',
            'description' => 'Gateway payment plan',
            'price_min' => 150,
            'duration_days' => '3',
            'hours_count' => 12,
            'country_id' => $country->id,
            'is_active' => true,
        ]);

        foreach ([Payment::METHOD_TABBY, Payment::METHOD_TAMARA] as $index => $paymentMethod) {
            $user = User::factory()->create(['phone_with_cc' => '+1000000310'.$index]);
            Sanctum::actingAs($user);

            $userRequest = UserRequest::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'start_date' => now()->toDateString(),
                'status' => UserRequest::STATUS_OFFER_SELECTED,
                'currency' => 'SAR',
                'app_fee_reserved_minor' => 0,
                'total_paid_minor' => 0,
                'has_user_car' => false,
                'wants_trainer_car' => true,
                'needs_pickup' => false,
            ]);

            $this->postJson('/api/v1/payments/plan', [
                'user_request_id' => $userRequest->id,
                'payment_method' => $paymentMethod,
                'type' => Payment::TYPE_PLAN_PARTIAL,
                'price' => 15000,
            ])
                ->assertCreated()
                ->assertJsonPath('success', true)
                ->assertJsonPath('data.payment_method', $paymentMethod)
                ->assertJsonPath('data.status', Payment::STATUS_PENDING)
                ->assertJsonPath('data.amount_minor', 15000)
                ->assertJsonPath('data.vat_minor', 2250)
                ->assertJsonPath('data.total_amount_minor', 17250)
                ->assertJsonPath(
                    'data.checkout_url',
                    $paymentMethod === Payment::METHOD_TABBY
                        ? 'https://checkout.tabby.test/session/123'
                        : 'https://checkout.tamara.test/session/123'
                );

            $this->assertDatabaseHas('payments', [
                'user_request_id' => $userRequest->id,
                'user_id' => $user->id,
                'type' => Payment::TYPE_PLAN_PARTIAL,
                'payment_method' => $paymentMethod,
                'status' => Payment::STATUS_PENDING,
                'amount_minor' => 15000,
                'gateway_reference' => $paymentMethod === Payment::METHOD_TABBY ? 'tabby-payment-123' : 'tamara-order-123',
            ]);

            $this->assertDatabaseHas('user_requests', [
                'id' => $userRequest->id,
                'status' => UserRequest::STATUS_OFFER_SELECTED,
                'total_paid_minor' => 0,
            ]);
        }

        Http::assertSent(fn ($request) => $request->url() === 'https://tabby.test/api/v2/checkout'
            && str_contains((string) data_get($request->data(), 'merchant_urls.success'), '/payments/return/tabby/success/')
            && ! str_contains((string) data_get($request->data(), 'merchant_urls.success'), '/api/v1/')
            && data_get($request->data(), 'payment.amount') === '172.50'
            && data_get($request->data(), 'payment.order.items.0.unit_price') === '172.50'
            && data_get($request->data(), 'payment.order.tax_amount') === '22.50'
            && data_get($request->data(), 'payment.order.items.0.tax_amount') === '22.50');

        Http::assertSent(fn ($request) => $request->url() === 'https://tamara.test/checkout'
            && str_contains((string) data_get($request->data(), 'merchant_url.success'), '/payments/return/tamara/success/')
            && ! str_contains((string) data_get($request->data(), 'merchant_url.success'), '/api/v1/')
            && data_get($request->data(), 'total_amount.amount') === '172.50'
            && data_get($request->data(), 'items.0.total_amount.amount') === '172.50'
            && data_get($request->data(), 'tax_amount.amount') === '22.50'
            && data_get($request->data(), 'items.0.tax_amount.amount') === '22.50');
    }

    public function test_bnpl_payment_methods_are_rejected_for_unsupported_country_currency(): void
    {
        Queue::fake();
        Http::fake();

        $country = Country::create([
            'name' => 'Egypt',
            'iso2' => 'EG',
            'currency' => 'EGP',
        ]);
        $plan = Plan::create([
            'title' => 'Unsupported Gateway Plan',
            'description' => 'Gateway payment plan',
            'price_min' => 150,
            'duration_days' => '3',
            'hours_count' => 12,
            'country_id' => $country->id,
            'is_active' => true,
        ]);

        foreach ([Payment::METHOD_TABBY, Payment::METHOD_TAMARA] as $index => $paymentMethod) {
            $user = User::factory()->create(['phone_with_cc' => '+1000000320'.$index]);
            Sanctum::actingAs($user);

            $userRequest = UserRequest::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'country_id' => $country->id,
                'start_date' => now()->toDateString(),
                'status' => UserRequest::STATUS_OFFER_SELECTED,
                'currency' => 'EGP',
                'app_fee_reserved_minor' => 0,
                'total_paid_minor' => 0,
                'has_user_car' => false,
                'wants_trainer_car' => true,
                'needs_pickup' => false,
            ]);

            $this->postJson('/api/v1/payments/plan', [
                'user_request_id' => $userRequest->id,
                'payment_method' => $paymentMethod,
                'type' => Payment::TYPE_PLAN_PARTIAL,
                'price' => 15000,
            ])
                ->assertStatus(422)
                ->assertJsonPath('errors.0.message', 'Payment method is not available for this country or currency');

            $this->assertDatabaseMissing('payments', [
                'user_request_id' => $userRequest->id,
                'payment_method' => $paymentMethod,
            ]);
        }

        Http::assertNothingSent();
    }

    public function test_tabby_webhook_marks_pending_gateway_payment_successful(): void
    {
        Queue::fake();

        Setting::updateOrCreate(['key' => 'payment.tabby.webhook_secret'], ['value' => 'tabby-webhook']);

        $country = Country::create([
            'name' => 'Tabby Webhook Country',
            'iso2' => 'SA',
            'currency' => 'SAR',
        ]);
        $plan = Plan::create([
            'title' => 'Webhook Plan',
            'description' => 'Gateway webhook plan',
            'price_min' => 150,
            'duration_days' => '3',
            'hours_count' => 12,
            'country_id' => $country->id,
            'is_active' => true,
        ]);

        $user = User::factory()->create(['phone_with_cc' => '+966500000001']);
        $userRequest = UserRequest::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'status' => UserRequest::STATUS_PENDING_PAYMENT,
            'currency' => 'SAR',
            'app_fee_reserved_minor' => 0,
            'total_paid_minor' => 0,
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);

        $payment = Payment::create([
            'user_id' => $user->id,
            'user_request_id' => $userRequest->id,
            'amount_minor' => 15000,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_PARTIAL,
            'payment_method' => Payment::METHOD_TABBY,
            'gateway_reference' => 'tabby-payment-webhook',
            'status' => Payment::STATUS_PENDING,
            'app_fee_minor' => 0,
            'trainer_net_minor' => 15000,
        ]);

        $this->postJson('/api/v1/payments/webhooks/tabby', [
            'payment' => [
                'id' => 'tabby-payment-webhook',
                'status' => 'CLOSED',
                'meta' => [
                    'payment_id' => $payment->id,
                ],
            ],
        ], [
            'X-Tabby-Signature' => 'tabby-webhook',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', Payment::STATUS_SUCCEEDED);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => Payment::STATUS_SUCCEEDED,
            'gateway_status' => 'closed',
        ]);

        $this->assertDatabaseHas('user_requests', [
            'id' => $userRequest->id,
            'status' => UserRequest::STATUS_AWAITING_OFFERS,
            'total_paid_minor' => 15000,
        ]);
    }

    public function test_tamara_approved_webhook_authorises_order_before_marking_payment_successful(): void
    {
        Queue::fake();
        Http::fake([
            'https://tamara.test/orders/tamara-order-webhook/authorise' => Http::response([
                'status' => 'authorised',
            ]),
        ]);

        Setting::updateOrCreate(['key' => 'payment.tamara.secret_key'], ['value' => 'tamara-secret']);
        Setting::updateOrCreate(['key' => 'payment.tamara.webhook_secret'], ['value' => 'tamara-webhook']);
        Setting::updateOrCreate(['key' => 'payment.tamara.base_url'], ['value' => 'https://tamara.test']);

        $country = Country::create([
            'name' => 'Tamara Webhook Country',
            'iso2' => 'SA',
            'currency' => 'SAR',
        ]);
        $plan = Plan::create([
            'title' => 'Tamara Webhook Plan',
            'description' => 'Gateway webhook plan',
            'price_min' => 150,
            'duration_days' => '3',
            'hours_count' => 12,
            'country_id' => $country->id,
            'is_active' => true,
        ]);

        $user = User::factory()->create(['phone_with_cc' => '+966500000002']);
        $userRequest = UserRequest::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'status' => UserRequest::STATUS_PENDING_PAYMENT,
            'currency' => 'SAR',
            'app_fee_reserved_minor' => 0,
            'total_paid_minor' => 0,
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);

        $payment = Payment::create([
            'user_id' => $user->id,
            'user_request_id' => $userRequest->id,
            'amount_minor' => 15000,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_PARTIAL,
            'payment_method' => Payment::METHOD_TAMARA,
            'gateway_reference' => 'tamara-order-webhook',
            'status' => Payment::STATUS_PENDING,
            'app_fee_minor' => 0,
            'trainer_net_minor' => 15000,
        ]);

        $this->postJson('/api/v1/payments/webhooks/tamara', [
            'order_id' => 'tamara-order-webhook',
            'order_reference_id' => $payment->id,
            'event_type' => 'order_approved',
        ], [
            'Authorization' => 'Bearer tamara-webhook',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', Payment::STATUS_SUCCEEDED)
            ->assertJsonPath('data.gateway_status', 'authorised');

        Http::assertSent(fn ($request) => $request->url() === 'https://tamara.test/orders/tamara-order-webhook/authorise');

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => Payment::STATUS_SUCCEEDED,
            'gateway_status' => 'authorised',
        ]);
    }

    public function test_gateway_return_page_renders_html_and_api_return_keeps_json(): void
    {
        Queue::fake();

        $country = Country::create([
            'name' => 'Return Country',
            'iso2' => 'SA',
            'currency' => 'SAR',
        ]);
        $plan = Plan::create([
            'title' => 'Return Plan',
            'description' => 'Gateway return plan',
            'price_min' => 150,
            'duration_days' => '3',
            'hours_count' => 12,
            'country_id' => $country->id,
            'is_active' => true,
        ]);
        $user = User::factory()->create(['phone_with_cc' => '+966500000003']);
        $userRequest = UserRequest::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'country_id' => $country->id,
            'start_date' => now()->toDateString(),
            'status' => UserRequest::STATUS_PENDING_PAYMENT,
            'currency' => 'SAR',
            'app_fee_reserved_minor' => 0,
            'total_paid_minor' => 0,
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);
        $payment = Payment::create([
            'user_id' => $user->id,
            'user_request_id' => $userRequest->id,
            'amount_minor' => 15000,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_PARTIAL,
            'payment_method' => Payment::METHOD_TABBY,
            'gateway_reference' => 'tabby-return-payment',
            'status' => Payment::STATUS_PENDING,
            'app_fee_minor' => 0,
            'trainer_net_minor' => 15000,
        ]);

        $this->get('/payments/return/tabby/success/'.$payment->id)
            ->assertOk()
            ->assertHeader('content-type', 'text/html; charset=UTF-8')
            ->assertSee('تم استلام نتيجة الدفع')
            ->assertSee('payment_return')
            ->assertSee($payment->id);

        $this->getJson('/api/v1/payments/return/tabby/success/'.$payment->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.payment_id', $payment->id)
            ->assertJsonPath('data.status', Payment::STATUS_PENDING);
    }

    public function test_tabby_success_return_marks_payment_successful_when_gateway_confirms_authorized(): void
    {
        Queue::fake();
        Http::fake([
            'https://tabby.test/api/v2/payments/tabby-return-authorized' => Http::response([
                'id' => 'tabby-return-authorized',
                'status' => 'AUTHORIZED',
            ]),
        ]);

        Setting::updateOrCreate(['key' => 'payment.tabby.secret_key'], ['value' => 'tabby-secret']);
        Setting::updateOrCreate(['key' => 'payment.tabby.base_url'], ['value' => 'https://tabby.test']);

        $country = Country::create([
            'name' => 'Tabby Return Country',
            'iso2' => 'SA',
            'currency' => 'SAR',
        ]);
        $plan = Plan::create([
            'title' => 'Tabby Return Plan',
            'description' => 'Gateway return plan',
            'price_min' => 150,
            'duration_days' => '3',
            'hours_count' => 12,
            'country_id' => $country->id,
            'is_active' => true,
        ]);
        $user = User::factory()->create(['phone_with_cc' => '+966500000004']);
        $trainer = User::factory()->create(['phone_with_cc' => '+966500000104']);
        $userRequest = UserRequest::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'country_id' => $country->id,
            'start_date' => now()->toDateString(),
            'status' => UserRequest::STATUS_AWAITING_OFFERS,
            'currency' => 'SAR',
            'app_fee_reserved_minor' => 0,
            'total_paid_minor' => 0,
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);
        $offer = TrainerOffer::create([
            'user_request_id' => $userRequest->id,
            'trainer_id' => $trainer->id,
            'price_minor' => 15000,
            'message' => 'Gateway return offer',
            'status' => TrainerOffer::STATUS_SENT,
        ]);
        $payment = Payment::create([
            'user_id' => $user->id,
            'user_request_id' => $userRequest->id,
            'amount_minor' => 15000,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_FULL,
            'payment_method' => Payment::METHOD_TABBY,
            'gateway_reference' => 'tabby-return-authorized',
            'status' => Payment::STATUS_PENDING,
            'app_fee_minor' => 0,
            'trainer_net_minor' => 15000,
            'gateway_payload' => [
                'selected_offer_id' => $offer->id,
            ],
        ]);

        $this->getJson('/api/v1/payments/return/tabby/success/'.$payment->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.payment_id', $payment->id)
            ->assertJsonPath('data.status', Payment::STATUS_SUCCEEDED)
            ->assertJsonPath('data.gateway_status', 'authorized');

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => Payment::STATUS_SUCCEEDED,
            'gateway_status' => 'authorized',
        ]);
        $this->assertDatabaseHas('user_requests', [
            'id' => $userRequest->id,
            'trainer_id' => $trainer->id,
            'status' => UserRequest::STATUS_IN_TRAINING,
            'total_paid_minor' => 15000,
        ]);
        $this->assertDatabaseHas('trainer_offers', [
            'id' => $offer->id,
            'status' => TrainerOffer::STATUS_ACCEPTED,
        ]);
    }

    public function test_tamara_success_return_authorises_approved_order_before_marking_payment_successful(): void
    {
        Queue::fake();
        Http::fake([
            'https://tamara.test/orders/tamara-return-approved' => Http::response([
                'order_id' => 'tamara-return-approved',
                'status' => 'approved',
            ]),
            'https://tamara.test/orders/tamara-return-approved/authorise' => Http::response([
                'status' => 'authorised',
            ]),
        ]);

        Setting::updateOrCreate(['key' => 'payment.tamara.secret_key'], ['value' => 'tamara-secret']);
        Setting::updateOrCreate(['key' => 'payment.tamara.base_url'], ['value' => 'https://tamara.test']);

        $country = Country::create([
            'name' => 'Tamara Return Country',
            'iso2' => 'SA',
            'currency' => 'SAR',
        ]);
        $plan = Plan::create([
            'title' => 'Tamara Return Plan',
            'description' => 'Gateway return plan',
            'price_min' => 150,
            'duration_days' => '3',
            'hours_count' => 12,
            'country_id' => $country->id,
            'is_active' => true,
        ]);
        $user = User::factory()->create(['phone_with_cc' => '+966500000005']);
        $trainer = User::factory()->create(['phone_with_cc' => '+966500000105']);
        $userRequest = UserRequest::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'country_id' => $country->id,
            'start_date' => now()->toDateString(),
            'status' => UserRequest::STATUS_AWAITING_OFFERS,
            'currency' => 'SAR',
            'app_fee_reserved_minor' => 0,
            'total_paid_minor' => 0,
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);
        $offer = TrainerOffer::create([
            'user_request_id' => $userRequest->id,
            'trainer_id' => $trainer->id,
            'price_minor' => 15000,
            'message' => 'Gateway return offer',
            'status' => TrainerOffer::STATUS_SENT,
        ]);
        $payment = Payment::create([
            'user_id' => $user->id,
            'user_request_id' => $userRequest->id,
            'amount_minor' => 15000,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_FULL,
            'payment_method' => Payment::METHOD_TAMARA,
            'gateway_reference' => 'tamara-return-approved',
            'status' => Payment::STATUS_PENDING,
            'app_fee_minor' => 0,
            'trainer_net_minor' => 15000,
            'gateway_payload' => [
                'selected_offer_id' => $offer->id,
            ],
        ]);

        $this->getJson('/api/v1/payments/return/tamara/success/'.$payment->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.payment_id', $payment->id)
            ->assertJsonPath('data.status', Payment::STATUS_SUCCEEDED)
            ->assertJsonPath('data.gateway_status', 'authorised');

        Http::assertSent(fn ($request) => $request->url() === 'https://tamara.test/orders/tamara-return-approved');
        Http::assertSent(fn ($request) => $request->url() === 'https://tamara.test/orders/tamara-return-approved/authorise');

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => Payment::STATUS_SUCCEEDED,
            'gateway_status' => 'authorised',
        ]);
        $this->assertDatabaseHas('user_requests', [
            'id' => $userRequest->id,
            'trainer_id' => $trainer->id,
            'status' => UserRequest::STATUS_IN_TRAINING,
            'total_paid_minor' => 15000,
        ]);
        $this->assertDatabaseHas('trainer_offers', [
            'id' => $offer->id,
            'status' => TrainerOffer::STATUS_ACCEPTED,
        ]);
    }

    public function test_plan_payment_rejects_hidden_app_payment_method(): void
    {
        Queue::fake();

        Setting::create([
            'key' => 'payment.tabby.enabled',
            'value' => '0',
        ]);

        $country = Country::create([
            'name' => 'Hidden Gateway Country',
            'iso2' => 'HG',
            'currency' => 'SAR',
        ]);
        $plan = Plan::create([
            'title' => 'Hidden Gateway Plan',
            'description' => 'Hidden gateway payment plan',
            'price_min' => 150,
            'duration_days' => '3',
            'hours_count' => 12,
            'country_id' => $country->id,
            'is_active' => true,
        ]);

        $user = User::factory()->create(['phone_with_cc' => '+10000003109']);
        Sanctum::actingAs($user);

        $userRequest = UserRequest::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'status' => UserRequest::STATUS_OFFER_SELECTED,
            'currency' => 'SAR',
            'app_fee_reserved_minor' => 0,
            'total_paid_minor' => 0,
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);

        $this->postJson('/api/v1/payments/plan', [
            'user_request_id' => $userRequest->id,
            'payment_method' => Payment::METHOD_TABBY,
            'type' => Payment::TYPE_PLAN_PARTIAL,
            'price' => 15000,
        ])->assertStatus(422);

        $this->assertDatabaseMissing('payments', [
            'user_request_id' => $userRequest->id,
            'payment_method' => Payment::METHOD_TABBY,
        ]);
    }

    public function test_plan_full_payment_applies_app_fee_percent_from_settings(): void
    {
        Queue::fake();

        Setting::create([
            'key' => 'fees.app_fee_percent',
            'value' => '15',
        ]);

        $country = Country::create([
            'name' => 'Test Country 2',
            'iso2' => 'T2',
            'currency' => 'USD',
        ]);
        $plan = Plan::create([
            'title' => 'Plan B',
            'description' => 'Test plan B',
            'price_min' => 200,
            'duration_days' => '5',
            'hours_count' => 20,
            'country_id' => $country->id,
            'is_active' => true,
        ]);

        $user = User::factory()->create(['phone_with_cc' => '+10000003002']);
        $token = $user->createToken('test')->plainTextToken;
        $user->update(['points_balance' => 200]);

        $userRequest = UserRequest::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'status' => UserRequest::STATUS_OFFER_SELECTED,
            'currency' => 'USD',
            'app_fee_reserved_minor' => 0,
            'total_paid_minor' => 0,
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);

        $trainer = User::factory()->create(['phone_with_cc' => '+10000003003']);

        TrainerOffer::create([
            'user_request_id' => $userRequest->id,
            'trainer_id' => $trainer->id,
            'price_minor' => 10000,
            'message' => 'Accepted trainer offer',
            'status' => TrainerOffer::STATUS_ACCEPTED,
        ]);

        $this->withToken($token)
            ->postJson('/api/v1/payments/plan', [
                'user_request_id' => $userRequest->id,
                'payment_method' => 'wallet',
                'type' => Payment::TYPE_PLAN_FULL,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', Payment::TYPE_PLAN_FULL)
            ->assertJsonPath('data.app_fee', 1500)
            ->assertJsonPath('data.trainer_net', 8500);

        $this->assertDatabaseHas('payments', [
            'user_request_id' => $userRequest->id,
            'user_id' => $user->id,
            'type' => Payment::TYPE_PLAN_FULL,
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 10000,
            'app_fee_minor' => 1500,
            'trainer_net_minor' => 8500,
        ]);

        $this->assertDatabaseHas('user_requests', [
            'id' => $userRequest->id,
            'status' => UserRequest::STATUS_IN_TRAINING,
            'app_fee_reserved_minor' => 1500,
            'total_paid_minor' => 10000,
        ]);

        $this->assertSame(100, (int) $user->fresh()->points_balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'amount' => 10000,
            'type' => WalletTransaction::TYPE_PAYMENT,
            'status' => WalletTransaction::STATUS_APPROVED,
        ]);
    }

    public function test_wallet_summary_matches_stored_balance_after_wallet_plan_payment(): void
    {
        Queue::fake();

        $country = Country::create([
            'name' => 'Summary Country',
            'iso2' => 'SC',
            'currency' => 'USD',
        ]);
        $plan = Plan::create([
            'title' => 'Plan Summary',
            'description' => 'Wallet summary plan',
            'price_min' => 100,
            'duration_days' => '4',
            'hours_count' => 8,
            'country_id' => $country->id,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'phone_with_cc' => '+10000003004',
            'points_balance' => 150,
        ]);

        WalletTransaction::create([
            'user_id' => $user->id,
            'amount' => 15000,
            'type' => WalletTransaction::TYPE_ADJUSTMENT,
            'status' => WalletTransaction::STATUS_APPROVED,
            'notes' => 'Initial wallet funding',
        ]);

        $token = $user->createToken('wallet-summary')->plainTextToken;

        $userRequest = UserRequest::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'status' => UserRequest::STATUS_OFFER_SELECTED,
            'currency' => 'USD',
            'app_fee_reserved_minor' => 0,
            'total_paid_minor' => 0,
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);

        $trainer = User::factory()->create(['phone_with_cc' => '+10000003005']);

        TrainerOffer::create([
            'user_request_id' => $userRequest->id,
            'trainer_id' => $trainer->id,
            'price_minor' => 10000,
            'message' => 'Accepted trainer offer',
            'status' => TrainerOffer::STATUS_ACCEPTED,
        ]);

        $this->withToken($token)
            ->postJson('/api/v1/payments/plan', [
                'user_request_id' => $userRequest->id,
                'payment_method' => 'wallet',
                'type' => Payment::TYPE_PLAN_FULL,
            ])
            ->assertCreated();

        $this->withToken($token)
            ->getJson('/api/v1/wallet')
            ->assertOk()
            ->assertJsonPath('data.balance', 50)
            ->assertJsonPath('data.calculated_balance', 50)
            ->assertJsonPath('data.balance_verified', true);
    }
}
