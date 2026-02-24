<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Referral;
use App\Models\User;
use App\Models\UserRequest;
use App\Models\Country;
use App\Models\City;
use App\Models\Plan;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Modules\Payments\Services\PaymentService;
use App\Modules\Referrals\Services\ReferralService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
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

    public function test_referral_endpoint_backfills_points_from_successful_subscription_payments(): void
    {
        $owner = User::factory()->create([
            'phone_with_cc' => '+10000000004',
        ]);
        $referredUser = User::factory()->create([
            'phone_with_cc' => '+10000000005',
            'referred_by' => $owner->id,
        ]);

        Payment::create([
            'user_id' => $referredUser->id,
            'user_request_id' => (string) \Illuminate\Support\Str::uuid(),
            'amount_minor' => 1000,
            'currency' => 'USD',
            'type' => Payment::TYPE_PLAN_FULL,
            'payment_method' => 'tap',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 1000,
            'trainer_net_minor' => 1000,
        ]);

        Payment::create([
            'user_id' => $referredUser->id,
            'user_request_id' => (string) \Illuminate\Support\Str::uuid(),
            'amount_minor' => 1200,
            'currency' => 'USD',
            'type' => Payment::TYPE_PLAN_FULL,
            'payment_method' => 'tap',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 1200,
            'trainer_net_minor' => 1200,
        ]);

        Sanctum::actingAs($owner);

        $this->getJson('/api/v1/me/referral')
            ->assertOk()
            ->assertJsonPath('data.points_balance', 1)
            ->assertJsonPath('data.total_points_earned', 1);

        $this->assertDatabaseHas('referrals', [
            'owner_user_id' => $owner->id,
            'total_points_earned' => 1,
        ]);
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
        $plan = $this->createPlan();

        $service = app(PaymentService::class);

        $firstRequest = $this->createUserRequest($referredUser->id, $plan->id);
        $service->payWithWallet($firstRequest, $referredUser, new Request([
            'type' => Payment::TYPE_PLAN_FULL,
            'status' => Payment::STATUS_SUCCEEDED,
            'price' => 1000,
            'payment_method' => 'wallet',
        ]));

        $secondRequest = $this->createUserRequest($referredUser->id, $plan->id);
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

    public function test_prize_request_uses_referral_points_balance_not_wallet_balance(): void
    {
        $user = User::factory()->create([
            'phone_with_cc' => '+10000000021',
            'points_balance' => 0,
        ]);

        Referral::create([
            'owner_user_id' => $user->id,
            'code' => (string) $user->referral_code,
            'total_points_earned' => 10,
            'total_redemptions' => 0,
        ]);

        $reward = Reward::create([
            'title' => 'Phone Holder',
            'required_points' => 5,
            'active' => true,
            'order' => 1,
        ]);

        Role::findOrCreate('admin', 'web');
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/prizes/request', [
            'reward_id' => $reward->id,
            'points_spent' => 5,
        ])->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('reward_redemptions', [
            'user_id' => $user->id,
            'reward_id' => $reward->id,
            'points_spent' => 5,
            'status' => 'pending',
        ]);
    }

    public function test_prize_request_fails_when_pending_redemptions_consume_referral_points(): void
    {
        $user = User::factory()->create([
            'phone_with_cc' => '+10000000022',
            'points_balance' => 999,
        ]);

        Referral::create([
            'owner_user_id' => $user->id,
            'code' => (string) $user->referral_code,
            'total_points_earned' => 10,
            'total_redemptions' => 0,
        ]);

        $reward = Reward::create([
            'title' => 'Gift Card',
            'required_points' => 1,
            'active' => true,
            'order' => 2,
        ]);

        RewardRedemption::create([
            'user_id' => $user->id,
            'reward_id' => $reward->id,
            'points_spent' => 10,
            'status' => 'pending',
        ]);

        Role::findOrCreate('admin', 'web');
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/prizes/request', [
            'reward_id' => $reward->id,
            'points_spent' => 2,
        ])->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.0.message', 'ليس لديك نقاط كافية');
    }

    public function test_prize_request_ignores_client_points_and_uses_reward_required_points(): void
    {
        $user = User::factory()->create([
            'phone_with_cc' => '+10000000023',
            'points_balance' => 0,
        ]);

        Referral::create([
            'owner_user_id' => $user->id,
            'code' => (string) $user->referral_code,
            'total_points_earned' => 5,
            'total_redemptions' => 0,
        ]);

        $reward = Reward::create([
            'title' => 'Mirror Hanger',
            'required_points' => 5,
            'active' => true,
            'order' => 3,
        ]);

        Role::findOrCreate('admin', 'web');
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/prizes/request', [
            'reward_id' => $reward->id,
            // Intentional mismatch to verify server-side source of truth.
            'points_spent' => 500,
        ])->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.points_spent', 5);
    }

    private function createUserRequest(string $userId, string $planId): UserRequest
    {
        return UserRequest::create([
            'user_id' => $userId,
            'plan_id' => $planId,
            'start_date' => now()->toDateString(),
            'status' => UserRequest::STATUS_PENDING_PAYMENT,
            'currency' => 'USD',
            'app_fee_reserved_minor' => 0,
            'total_paid_minor' => 0,
        ]);
    }

    private function createPlan(): Plan
    {
        $country = Country::create([
            'name' => 'United States',
            'iso2' => 'US',
            'currency' => 'USD',
        ]);

        $city = City::create([
            'name' => 'New York',
            'country_id' => $country->id,
        ]);

        return Plan::create([
            'title' => 'Referral Plan',
            'description' => 'Plan used for referral payment test',
            'price_min' => 100,
            'duration_days' => '10',
            'country_id' => $country->id,
            'city_id' => $city->id,
            'is_active' => true,
        ]);
    }
}
