<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\WalletTransaction;
use App\Notifications\WalletWithdrawalProcessedNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WalletWithdrawalRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_user_cannot_request_withdrawal_without_bank_details(): void
    {
        $user = User::factory()->create([
            'phone_with_cc' => '+10000005001',
            'points_balance' => 120,
            'bank_account' => null,
            'iban' => null,
            'bank_name' => null,
            'bank_country_id' => null,
        ]);
        $user->assignRole('USER');

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/wallet/withdraw-request', [
            'amount' => 50,
            'notes' => 'طلب سحب للتجربة',
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertSame(120, (int) $user->fresh()->points_balance);
    }

    public function test_user_can_request_wallet_withdrawal_with_complete_bank_details(): void
    {
        $user = User::factory()->create([
            'phone_with_cc' => '+10000005009',
            'points_balance' => 120,
            'bank_account' => '1234567890',
            'iban' => 'SA0380000000608010167519',
            'bank_name' => 'Test Bank',
            'bank_country_id' => (string) \Illuminate\Support\Str::uuid(),
        ]);
        $user->assignRole('USER');

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/wallet/withdraw-request', [
            'amount' => 50,
            'notes' => 'طلب سحب للتجربة',
        ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', WalletTransaction::TYPE_WITHDRAW_REQUEST)
            ->assertJsonPath('data.status', WalletTransaction::STATUS_PENDING);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'amount' => 5000,
            'type' => WalletTransaction::TYPE_WITHDRAW_REQUEST,
            'status' => WalletTransaction::STATUS_PENDING,
        ]);

        $this->assertSame(120, (int) $user->fresh()->points_balance);
    }

    public function test_trainer_cannot_request_withdrawal_without_bank_details(): void
    {
        $trainer = User::factory()->create([
            'phone_with_cc' => '+10000005007',
            'points_balance' => 150,
            'user_type' => 'captain',
            'bank_account' => null,
            'iban' => null,
            'bank_name' => null,
            'bank_country_id' => null,
        ]);
        $trainer->assignRole('TRAINER');

        Sanctum::actingAs($trainer);

        $this->postJson('/api/v1/wallet/withdraw-request', [
            'amount' => 40,
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_trainer_can_request_withdrawal_with_complete_bank_details(): void
    {
        $trainer = User::factory()->create([
            'phone_with_cc' => '+10000005008',
            'points_balance' => 150,
            'user_type' => 'captain',
            'bank_account' => '1234567890',
            'iban' => 'SA0380000000608010167519',
            'bank_name' => 'Test Bank',
            'bank_country_id' => (string) \Illuminate\Support\Str::uuid(),
        ]);
        $trainer->assignRole('TRAINER');

        Sanctum::actingAs($trainer);

        $this->postJson('/api/v1/wallet/withdraw-request', [
            'amount' => 40,
        ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', WalletTransaction::TYPE_WITHDRAW_REQUEST)
            ->assertJsonPath('data.status', WalletTransaction::STATUS_PENDING);
    }

    public function test_user_cannot_request_withdrawal_more_than_available_balance_after_pending_requests(): void
    {
        $user = User::factory()->create([
            'phone_with_cc' => '+10000005002',
            'points_balance' => 100,
        ]);
        $user->assignRole('USER');

        WalletTransaction::create([
            'user_id' => $user->id,
            'amount' => 8000,
            'type' => WalletTransaction::TYPE_WITHDRAW_REQUEST,
            'status' => WalletTransaction::STATUS_PENDING,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/wallet/withdraw-request', [
            'amount' => 30,
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_admin_can_execute_withdrawal_request_and_deduct_wallet_balance(): void
    {
        $user = User::factory()->create([
            'phone_with_cc' => '+10000005003',
            'points_balance' => 100,
        ]);
        $user->assignRole('USER');

        $admin = User::factory()->create([
            'phone_with_cc' => '+10000005004',
        ]);
        $admin->assignRole('ADMIN');
        $admin->givePermissionTo('manage_wallets');

        $withdrawalRequest = WalletTransaction::create([
            'user_id' => $user->id,
            'amount' => 4000,
            'type' => WalletTransaction::TYPE_WITHDRAW_REQUEST,
            'status' => WalletTransaction::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.withdrawal-requests.approve', $withdrawalRequest->id))
            ->assertRedirect();

        $this->assertDatabaseHas('wallet_transactions', [
            'id' => $withdrawalRequest->id,
            'status' => WalletTransaction::STATUS_APPROVED,
            'processed_by' => $admin->id,
        ]);

        $this->assertSame(60, (int) $user->fresh()->points_balance);
        $this->assertDatabaseHas('notifications', [
            'type' => WalletWithdrawalProcessedNotification::class,
            'notifiable_id' => $user->id,
            'notifiable_type' => User::class,
        ]);
    }

    public function test_admin_can_reject_withdrawal_request_without_deducting_wallet_balance(): void
    {
        $user = User::factory()->create([
            'phone_with_cc' => '+10000005005',
            'points_balance' => 100,
        ]);
        $user->assignRole('USER');

        $admin = User::factory()->create([
            'phone_with_cc' => '+10000005006',
        ]);
        $admin->assignRole('ADMIN');
        $admin->givePermissionTo('manage_wallets');

        $withdrawalRequest = WalletTransaction::create([
            'user_id' => $user->id,
            'amount' => 4000,
            'type' => WalletTransaction::TYPE_WITHDRAW_REQUEST,
            'status' => WalletTransaction::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.withdrawal-requests.reject', $withdrawalRequest->id), [
                'rejection_reason' => 'بيانات غير مكتملة',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('wallet_transactions', [
            'id' => $withdrawalRequest->id,
            'status' => WalletTransaction::STATUS_REJECTED,
            'processed_by' => $admin->id,
            'rejection_reason' => 'بيانات غير مكتملة',
        ]);

        $this->assertSame(100, (int) $user->fresh()->points_balance);
        $this->assertDatabaseHas('notifications', [
            'type' => WalletWithdrawalProcessedNotification::class,
            'notifiable_id' => $user->id,
            'notifiable_type' => User::class,
        ]);

        $notification = DB::table('notifications')
            ->where('type', WalletWithdrawalProcessedNotification::class)
            ->where('notifiable_id', $user->id)
            ->latest('created_at')
            ->first();

        $this->assertNotNull($notification);
        $payload = json_decode((string) $notification->data, true);
        $this->assertSame(WalletTransaction::STATUS_REJECTED, $payload['status'] ?? null);
    }

    public function test_withdrawal_requests_index_shows_bank_fields_and_supports_excel_export(): void
    {
        $user = User::factory()->create([
            'name' => 'Withdrawal User',
            'phone_with_cc' => '+10000005010',
            'points_balance' => 250,
            'bank_name' => 'Test Bank',
            'bank_account' => '1234567890',
            'iban' => 'SA0380000000608010167519',
            'bank_country_id' => (string) \Illuminate\Support\Str::uuid(),
        ]);
        $user->assignRole('USER');

        $admin = User::factory()->create([
            'phone_with_cc' => '+10000005011',
        ]);
        $admin->assignRole('ADMIN');
        $admin->givePermissionTo('manage_wallets');

        WalletTransaction::create([
            'user_id' => $user->id,
            'amount' => 7500,
            'type' => WalletTransaction::TYPE_WITHDRAW_REQUEST,
            'status' => WalletTransaction::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.withdrawal-requests.index'))
            ->assertOk()
            ->assertSee('اسم البنك')
            ->assertSee('رقم الحساب')
            ->assertSee('IBAN')
            ->assertSee('Test Bank')
            ->assertSee('1234567890')
            ->assertSee('SA0380000000608010167519');

        $response = $this->actingAs($admin)
            ->get(route('admin.withdrawal-requests.index', ['export' => 'excel']));

        $response->assertOk();
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));
    }
}
