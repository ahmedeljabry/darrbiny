<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CancellationRequest;
use App\Models\Country;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Models\UserRequest;
use App\Models\WalletTransaction;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserPurgeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_force_delete_releases_phone_and_preserves_financial_history(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create([
            'phone_with_cc' => '+10000006100',
        ]);
        $admin->assignRole('ADMIN');
        $admin->givePermissionTo('manage_users');

        $country = Country::create([
            'name' => 'Saudi Arabia',
            'iso2' => 'SA',
            'currency' => 'SAR',
        ]);

        $plan = Plan::create([
            'title' => 'Purge Preservation Plan',
            'description' => 'Plan for purge preservation test',
            'price_min' => 100,
            'duration_days' => '5',
            'hours_count' => 10,
            'session_count' => 5,
            'country_id' => $country->id,
            'is_active' => true,
        ]);

        $student = User::factory()->create([
            'phone_with_cc' => '+10000006101',
            'email' => 'purge-student@example.com',
        ]);
        $student->assignRole('USER');

        $booking = UserRequest::create([
            'user_id' => $student->id,
            'plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'status' => UserRequest::STATUS_IN_TRAINING,
            'currency' => 'SAR',
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);

        $payment = Payment::create([
            'user_id' => $student->id,
            'user_request_id' => $booking->id,
            'amount_minor' => 10_000,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_FULL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 1_000,
            'trainer_net_minor' => 9_000,
        ]);

        $walletTransaction = WalletTransaction::create([
            'user_id' => $student->id,
            'amount' => 2_500,
            'type' => WalletTransaction::TYPE_WITHDRAW_REQUEST,
            'status' => WalletTransaction::STATUS_APPROVED,
            'processed_by' => $admin->id,
            'processed_at' => now(),
        ]);

        $cancellation = CancellationRequest::create([
            'user_request_id' => $booking->id,
            'user_id' => $student->id,
            'reason' => 'Historical cancellation',
            'status' => CancellationRequest::STATUS_APPROVED,
            'refund_amount_minor' => 1_500,
            'processed_by' => $admin->id,
            'processed_at' => now(),
        ]);

        $reward = Reward::create([
            'title' => 'Fuel Card',
            'required_points' => 100,
            'active' => true,
        ]);
        $redemption = RewardRedemption::create([
            'user_id' => $student->id,
            'reward_id' => $reward->id,
            'points_spent' => 100,
            'status' => 'pending',
        ]);

        $ticket = SupportTicket::create([
            'user_id' => $student->id,
            'name' => $student->name,
            'phone_with_cc' => $student->phone_with_cc,
            'email' => $student->email,
            'subject' => 'Support cleanup',
            'status' => 'open',
        ]);
        $message = SupportTicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $student->id,
            'author_type' => 'user',
            'message' => 'Please remove this support ticket on purge',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.users.force-destroy', $student->id))
            ->assertRedirect(route('admin.users.index'));

        $this->assertSoftDeleted('users', ['id' => $student->id]);
        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'name' => 'مستخدم محذوف',
            'email' => null,
        ]);
        $purgedStudent = User::withTrashed()->findOrFail($student->id);
        $this->assertNotNull($purgedStudent->banned_until);
        $this->assertTrue($purgedStudent->banned_until->lessThanOrEqualTo(
            \Illuminate\Support\Carbon::parse('2037-12-31 23:59:59')
        ));
        $this->assertDatabaseMissing('users', [
            'phone_with_cc' => '+10000006101',
        ]);

        $this->assertDatabaseHas('user_requests', ['id' => $booking->id, 'user_id' => $student->id]);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'user_id' => $student->id]);
        $this->assertDatabaseHas('wallet_transactions', ['id' => $walletTransaction->id, 'user_id' => $student->id]);
        $this->assertDatabaseHas('cancellation_requests', ['id' => $cancellation->id, 'user_id' => $student->id]);
        $this->assertDatabaseHas('reward_redemptions', ['id' => $redemption->id, 'user_id' => $student->id]);

        $this->assertDatabaseMissing('support_ticket_messages', ['id' => $message->id]);
        $this->assertDatabaseMissing('support_tickets', ['id' => $ticket->id]);
    }

    public function test_reset_all_wipes_operational_data_and_keeps_admin_accounts(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create([
            'phone_with_cc' => '+10000006110',
        ]);
        $admin->assignRole('ADMIN');
        $admin->givePermissionTo('manage_users');

        $country = Country::create([
            'name' => 'Saudi Arabia',
            'iso2' => 'SA',
            'currency' => 'SAR',
        ]);

        $plan = Plan::create([
            'title' => 'Full Reset Plan',
            'description' => 'Plan for full reset test',
            'price_min' => 100,
            'duration_days' => '5',
            'hours_count' => 10,
            'session_count' => 5,
            'country_id' => $country->id,
            'is_active' => true,
        ]);

        $student = User::factory()->create([
            'phone_with_cc' => '+10000006111',
        ]);
        $student->assignRole('USER');

        $booking = UserRequest::create([
            'user_id' => $student->id,
            'plan_id' => $plan->id,
            'country_id' => $country->id,
            'start_date' => now()->toDateString(),
            'status' => UserRequest::STATUS_IN_TRAINING,
            'currency' => 'SAR',
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);

        Payment::create([
            'user_id' => $student->id,
            'user_request_id' => $booking->id,
            'amount_minor' => 10_000,
            'currency' => 'SAR',
            'type' => Payment::TYPE_PLAN_FULL,
            'payment_method' => 'wallet',
            'status' => Payment::STATUS_SUCCEEDED,
            'app_fee_minor' => 1_000,
            'trainer_net_minor' => 9_000,
        ]);

        WalletTransaction::create([
            'user_id' => $student->id,
            'amount' => 2_500,
            'currency' => 'SAR',
            'type' => WalletTransaction::TYPE_WITHDRAW_REQUEST,
            'status' => WalletTransaction::STATUS_PENDING,
        ]);

        SupportTicket::create([
            'user_id' => $student->id,
            'name' => $student->name,
            'phone_with_cc' => $student->phone_with_cc,
            'subject' => 'Reset cleanup',
            'status' => 'open',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.reset-all'), ['confirm_reset' => '1'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $this->assertDatabaseMissing('users', ['id' => $student->id]);
        $this->assertDatabaseCount('user_requests', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('wallet_transactions', 0);
        $this->assertDatabaseCount('support_tickets', 0);
    }
}
