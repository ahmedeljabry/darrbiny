<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUsersUnbanTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_bulk_unban_soft_deleted_user_and_get_arabic_status_message(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = $this->createAdminUser();
        $bannedUser = User::factory()->create([
            'phone_with_cc' => '+201000000002',
            'banned_until' => now()->addDay(),
            'banned_reason' => 'اختبار الحظر',
        ]);
        $bannedUser->delete();

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->post(route('admin.users.bulk-action'), [
                'user_ids' => [$bannedUser->id],
                'action' => 'unban',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('status', 'تم إلغاء حظر 1 مستخدم بنجاح');

        $bannedUser = User::query()->find($bannedUser->id);

        $this->assertNotNull($bannedUser);
        $this->assertFalse($bannedUser->isBanned());
        $this->assertNull($bannedUser->banned_until);
        $this->assertNull($bannedUser->banned_reason);
    }

    public function test_admin_can_unban_single_soft_deleted_user_and_get_arabic_status_message(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = $this->createAdminUser();
        $bannedUser = User::factory()->create([
            'phone_with_cc' => '+201000000004',
            'banned_until' => now()->addDay(),
            'banned_reason' => 'اختبار الحظر',
        ]);
        $bannedUser->delete();

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->post(route('admin.users.unban', $bannedUser->id))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('status', 'تم إلغاء حظر المستخدم بنجاح');

        $bannedUser = User::query()->find($bannedUser->id);

        $this->assertNotNull($bannedUser);
        $this->assertFalse($bannedUser->isBanned());
        $this->assertNull($bannedUser->banned_until);
        $this->assertNull($bannedUser->banned_reason);
    }

    private function createAdminUser(): User
    {
        $admin = User::factory()->create([
            'phone_with_cc' => '+201000000001',
        ]);
        $admin->assignRole('ADMIN');

        return $admin;
    }
}
