<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserRequest;
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

    public function test_captain_registration_is_approved_immediately_without_admin_notification(): void
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
            ->assertJsonMissingPath('data.requires_admin_approval')
            ->assertJsonPath('data.user.phone_with_cc', '+201555570001')
            ->assertJsonPath('data.access_token', fn ($token) => is_string($token) && $token !== '')
            ->assertJsonPath('data.refresh_token', fn ($token) => is_string($token) && $token !== '');

        $this->postJson('/api/v1/auth/login', [
            'phone_with_cc' => '+201555570001',
            'password' => 'password123',
        ])->assertOk()
            ->assertJsonPath('data.user.phone_with_cc', '+201555570001');

        $trainer = User::where('phone_with_cc', '+201555570001')->firstOrFail();
        $this->assertFalse((bool) $trainer->trainerProfile?->pending_approval);
        $this->assertNotNull($trainer->trainerProfile?->verified_at);
        $this->assertNull($trainer->banned_until);
        $this->assertNull($trainer->banned_reason);

        Notification::assertNotSentTo(
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

    public function test_admin_users_index_shows_and_filters_country_and_city(): void
    {
        $admin = User::factory()->create([
            'phone_with_cc' => '+10000007005',
            'email' => 'location-filter-admin@example.com',
        ]);
        $admin->assignRole('ADMIN');

        $egypt = Country::create([
            'name' => 'Egypt',
            'iso2' => 'EG',
            'currency' => 'EGP',
        ]);
        $jordan = Country::create([
            'name' => 'Jordan',
            'iso2' => 'JO',
            'currency' => 'JOD',
        ]);

        $plan = Plan::create([
            'title' => 'User Location Plan',
            'description' => 'Plan for admin user location filter test',
            'price_min' => 100,
            'duration_days' => '3',
            'hours_count' => 6,
            'country_id' => $egypt->id,
            'is_active' => true,
        ]);

        $trainer = User::factory()->create([
            'name' => 'Cairo Trainer',
            'phone_with_cc' => '+201555570005',
            'user_type' => 'captain',
        ]);
        $trainer->assignRole('TRAINER');
        $trainer->trainerProfile()->create([
            'country_id' => $egypt->id,
            'area_level_1' => 'Cairo Governorate',
            'area_level_2' => 'Cairo',
            'verified_at' => now(),
        ]);

        $student = User::factory()->create([
            'name' => 'Amman Student',
            'phone_with_cc' => '+962790000001',
            'country_id' => $jordan->id,
        ]);
        $student->assignRole('USER');

        UserRequest::create([
            'user_id' => $student->id,
            'plan_id' => $plan->id,
            'country_id' => $jordan->id,
            'area_level_1' => 'Amman Governorate',
            'area_level_2' => 'Amman',
            'start_date' => now()->addDay()->toDateString(),
            'start_time' => '09:00:00',
            'status' => UserRequest::STATUS_PENDING_PAYMENT,
            'currency' => 'JOD',
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['country_id' => $egypt->id, 'city' => 'Cairo']))
            ->assertOk()
            ->assertSee('Cairo Trainer')
            ->assertSee('Egypt')
            ->assertSee('Cairo')
            ->assertDontSee('Amman Student');

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['city' => 'Amman']))
            ->assertOk()
            ->assertSee('Amman Student')
            ->assertSee('Jordan')
            ->assertSee('Amman')
            ->assertDontSee('Cairo Trainer');
    }
}
