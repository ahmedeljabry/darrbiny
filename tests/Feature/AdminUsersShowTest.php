<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Plan;
use App\Models\Upload;
use App\Models\User;
use App\Models\UserRequest;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUsersShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_show_page_displays_latest_student_description(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create([
            'phone_with_cc' => '+10000006001',
        ]);
        $admin->assignRole('ADMIN');
        $admin->givePermissionTo('manage_users');

        $country = Country::create([
            'name' => 'Saudi Arabia',
            'iso2' => 'SA',
            'currency' => 'SAR',
        ]);

        $plan = Plan::create([
            'title' => 'User Show Plan',
            'description' => 'Plan for user show page',
            'price_min' => 100,
            'duration_days' => '5',
            'hours_count' => 10,
            'session_count' => 5,
            'country_id' => $country->id,
            'is_active' => true,
        ]);

        $student = User::factory()->create([
            'phone_with_cc' => '+10000006002',
            'banned_reason' => 'سبب تجريبي',
        ]);
        $student->assignRole('USER');

        UserRequest::create([
            'user_id' => $student->id,
            'plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'description' => 'طالبة تحتاج تدريب مسائي فقط',
            'status' => UserRequest::STATUS_PENDING_PAYMENT,
            'currency' => 'SAR',
            'has_user_car' => false,
            'wants_trainer_car' => true,
            'needs_pickup' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $student->id))
            ->assertOk()
            ->assertSee('وصف الطالبة')
            ->assertSee('طالبة تحتاج تدريب مسائي فقط');
    }

    public function test_user_show_page_displays_profile_picture_when_available(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create([
            'phone_with_cc' => '+10000006011',
        ]);
        $admin->assignRole('ADMIN');
        $admin->givePermissionTo('manage_users');

        $upload = Upload::create([
            'disk' => 'public',
            'path' => 'uploads/profile-review.jpg',
            'mime' => 'image/jpeg',
            'size' => 1024,
        ]);

        $student = User::factory()->create([
            'phone_with_cc' => '+10000006012',
            'profile_picture_id' => $upload->id,
        ]);
        $student->assignRole('USER');

        $this->actingAs($admin)
            ->get(route('admin.users.show', $student->id))
            ->assertOk()
            ->assertSee('الصورة الشخصية')
            ->assertSee('uploads/profile-review.jpg');
    }
}
