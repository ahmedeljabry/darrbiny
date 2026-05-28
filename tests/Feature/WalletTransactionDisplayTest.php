<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserRequest;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletTransactionDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_wallet_transactions_show_order_number_for_payment_marker(): void
    {
        [$user, $userRequest] = $this->createUserAndRequest(5071);

        $payment = Payment::create([
            'user_id' => $user->id,
            'user_request_id' => $userRequest->id,
            'amount_minor' => 1000,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_FULL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 100,
            'trainer_net_minor' => 900,
        ]);

        WalletTransaction::create([
            'user_id' => $user->id,
            'amount' => 1000,
            'type' => WalletTransaction::TYPE_PAYMENT,
            'status' => WalletTransaction::STATUS_APPROVED,
            'notes' => "Payment: {$payment->id}",
        ]);

        $response = $this->withToken($user->createToken('wallet')->plainTextToken)
            ->getJson('/api/v1/wallet/transactions')
            ->assertOk()
            ->assertJsonPath('data.0.notes', 'دفع طلب رقم #' . $userRequest->formatted_order_number);

        $this->assertStringNotContainsString($payment->id, (string) $response->json('data.0.notes'));
    }

    public function test_wallet_transactions_show_order_number_for_legacy_cancellation_refund_notes(): void
    {
        [$user, $userRequest] = $this->createUserAndRequest(5072);

        WalletTransaction::create([
            'user_id' => $user->id,
            'amount' => 1000,
            'type' => WalletTransaction::TYPE_REFUND,
            'status' => WalletTransaction::STATUS_APPROVED,
            'notes' => "إلغاء دورة #{$userRequest->id} - سبب الإلغاء",
        ]);

        $response = $this->withToken($user->createToken('wallet')->plainTextToken)
            ->getJson('/api/v1/wallet/transactions')
            ->assertOk()
            ->assertJsonPath('data.0.notes', 'إلغاء دورة #' . $userRequest->formatted_order_number . ' - سبب الإلغاء');

        $this->assertStringNotContainsString($userRequest->id, (string) $response->json('data.0.notes'));
    }

    private function createUserAndRequest(int $orderNumber): array
    {
        $country = Country::create([
            'name' => 'Saudi Arabia',
            'iso2' => 'SA',
            'currency' => 'SAR',
        ]);

        $plan = Plan::create([
            'title' => 'Wallet Display Plan',
            'description' => 'Plan used for wallet display tests',
            'price_min' => 100,
            'duration_days' => '3',
            'hours_count' => 12,
            'country_id' => $country->id,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'phone_with_cc' => '+966500009101',
        ]);

        $userRequest = UserRequest::create([
            'order_number' => $orderNumber,
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'country_id' => $country->id,
            'start_date' => now()->addDay()->toDateString(),
            'status' => UserRequest::STATUS_IN_TRAINING,
            'currency' => 'SAR',
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);

        return [$user, $userRequest];
    }
}
