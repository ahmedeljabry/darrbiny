<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Referral;
use App\Models\User;
use App\Models\UserRequest;
use App\Modules\Payments\Services\PaymentService;
use App\Modules\Referrals\Services\ReferralService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReferralSeparationTest extends TestCase
{
    use RefreshDatabase;

    public function test_signup_referral_only_links_users_without_points(): void
    {
        $owner = User::factory()->create([
            'phone_with_cc' => '+10000000001',
            'points_balance' => 15,
        ]);
        $newUser = User::factory()->create([
            'phone_with_cc' => '+10000000002',
            'points_balance' => 0,
        ]);

        app(ReferralService::class)->processSignupReferral($newUser, (string) $owner->referral_code);

        $this->assertSame(15, (int) $owner->fresh()->points_balance);
        $this->assertSame($owner->id, $newUser->fresh()->referred_by);
        $this->assertDatabaseHas('referrals', [
            'owner_user_id' => $owner->id,
            'code' => $owner->referral_code,
            'total_points_earned' => 0,
        ]);
    }

    public function test_referral_endpoint_points_balance_is_referral_only(): void
    {
        $user = User::factory()->create([
            'phone_with_cc' => '+10000000003',
            'points_balance' => 99,
        ]);
        Referral::create([
            'owner_user_id' => $user->id,
            'code' => (string) $user->referral_code,
            'total_points_earned' => 7,
            'total_redemptions' => 0,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/me/referral')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.points_balance', 7)
            ->assertJsonPath('data.referral_points_balance', 7)
            ->assertJsonPath('data.wallet_points_balance', 99);
    }

    public function test_referrer_gets_one_point_after_first_successful_paid_subscription(): void
    {
        $owner = User::factory()->create([
            'phone_with_cc' => '+10000000011',
        ]);

        $referredUser = User::factory()->create([
            'phone_with_cc' => '+10000000012',
            'referred_by' => $owner->id,
        ]);

        $service = app(PaymentService::class);

        $firstRequest = $this->createUserRequest($referredUser->id);
        $service->payWithWallet($firstRequest, $referredUser, new Request([
            'type' => Payment::TYPE_PLAN_FULL,
            'status' => Payment::STATUS_SUCCEEDED,
            'price' => 1000,
            'payment_method' => 'wallet',
        ]));

        $secondRequest = $this->createUserRequest($referredUser->id);
        $service->payWithWallet($secondRequest, $referredUser->fresh(), new Request([
            'type' => Payment::TYPE_PLAN_FULL,
            'status' => Payment::STATUS_SUCCEEDED,
            'price' => 2000,
            'payment_method' => 'wallet',
        ]));

        $this->assertDatabaseHas('referrals', [
            'owner_user_id' => $owner->id,
            'code' => $owner->referral_code,
            'total_points_earned' => 1,
        ]);
    }

    private function createUserRequest(string $userId): UserRequest
    {
        return UserRequest::create([
            'user_id' => $userId,
            'plan_id' => (string) Str::uuid(),
            'start_date' => now()->toDateString(),
            'status' => UserRequest::STATUS_PENDING_PAYMENT,
            'currency' => 'USD',
            'app_fee_reserved_minor' => 0,
            'total_paid_minor' => 0,
        ]);
    }
}
