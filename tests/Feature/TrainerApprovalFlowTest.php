<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\TrainerProfileApprovalNotification;
use App\Notifications\TrainerRegistrationPendingApprovalNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TrainerApprovalFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_captain_registration_requires_admin_approval_and_notifies_admin(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'phone_with_cc' => '+10000007001',
            'email' => 'approval-admin@example.com',
        ]);
        $admin->assignRole('ADMIN');

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Pending Trainer',
            'phone_with_cc' => '+201555570001',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'type' => 'captain',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.requires_admin_approval', true)
            ->assertJsonPath('data.approval_status', 'pending')
            ->assertJsonMissingPath('data.access_token');

        $this->postJson('/api/v1/auth/login', [
            'phone_with_cc' => '+201555570001',
            'password' => 'password123',
        ])->assertStatus(403)
            ->assertJsonPath('errors.phone_with_cc.0', 'الحساب بانتظار موافقة الإدارة على التنشيط.');

        $trainer = User::where('phone_with_cc', '+201555570001')->firstOrFail();
        $this->assertTrue((bool) $trainer->trainerProfile?->pending_approval);
        $this->assertNotNull($trainer->banned_until);
        $this->assertSame('مطلوب تنشيط من الإدارة', $trainer->banned_reason);

        Notification::assertSentTo(
            $admin,
            TrainerRegistrationPendingApprovalNotification::class
        );
    }

    public function test_admin_approval_notifies_trainer_and_applies_pending_changes(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'phone_with_cc' => '+10000007002',
            'email' => 'approver@example.com',
        ]);
        $admin->assignRole('ADMIN');

        $trainer = User::factory()->create([
            'phone_with_cc' => '+201555570002',
            'banned_until' => now()->addDays(10),
            'banned_reason' => 'Pending profile review',
        ]);
        $trainer->assignRole('TRAINER');
        $trainer->trainerProfile()->create([
            'car_type' => 'Old Car',
            'pending_approval' => true,
            'pending_changes' => [
                'car_type' => 'Updated Car',
                'has_driving_license' => true,
            ],
            'pending_approval_at' => now()->subHour(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.trainer-profile.approve', $trainer->id))
            ->assertRedirect();

        $trainer->refresh();
        $profile = $trainer->trainerProfile()->firstOrFail();

        $this->assertSame('Updated Car', $profile->car_type);
        $this->assertTrue((bool) $profile->has_driving_license);
        $this->assertFalse((bool) $profile->pending_approval);
        $this->assertNull($profile->pending_changes);
        $this->assertNull($profile->pending_approval_at);
        $this->assertNotNull($profile->verified_at);
        $this->assertNull($trainer->banned_until);
        $this->assertNull($trainer->banned_reason);

        Notification::assertSentTo(
            $trainer,
            TrainerProfileApprovalNotification::class,
            fn (TrainerProfileApprovalNotification $notification): bool => $notification->approved === true
        );
    }

    public function test_admin_show_page_displays_pending_captain_changes(): void
    {
        $admin = User::factory()->create([
            'phone_with_cc' => '+10000007003',
            'email' => 'viewer@example.com',
        ]);
        $admin->assignRole('ADMIN');

        $trainer = User::factory()->create([
            'phone_with_cc' => '+201555570003',
        ]);
        $trainer->assignRole('TRAINER');
        $trainer->trainerProfile()->create([
            'car_type' => 'Car Before',
            'pending_approval' => true,
            'pending_changes' => [
                'car_type' => 'Car After',
            ],
            'pending_approval_at' => now()->subMinutes(30),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $trainer->id))
            ->assertOk()
            ->assertSee('Car After')
            ->assertSee('تفاصيل التعديلات المعلقة');
    }

    public function test_admin_users_index_marks_pending_trainer_as_required_activation(): void
    {
        $admin = User::factory()->create([
            'phone_with_cc' => '+10000007004',
            'email' => 'index-admin@example.com',
        ]);
        $admin->assignRole('ADMIN');

        $trainer = User::factory()->create([
            'name' => 'Trainer Pending',
            'phone_with_cc' => '+201555570004',
            'banned_until' => now()->addDays(5),
        ]);
        $trainer->assignRole('TRAINER');
        $trainer->trainerProfile()->create([
            'pending_approval' => true,
            'pending_approval_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['role' => 'trainer']))
            ->assertOk()
            ->assertSee('+201555570004')
            ->assertSee('tabler-alert-circle me-1"></i>مطلوب تنشيط', false);
    }
}
