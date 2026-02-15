<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Referral;
use App\Models\User;
use App\Modules\Referrals\Services\ReferralService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReferralSeparationTest extends TestCase
{
    use RefreshDatabase;

    public function test_signup_referral_does_not_change_wallet_balance(): void
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
            'total_points_earned' => 1,
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
}

