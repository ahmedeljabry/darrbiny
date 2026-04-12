<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminRolePermissionAccessTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('protectedAdminRoutes')]
    public function test_admin_without_required_permission_cannot_open_protected_admin_route(string $routeName, string $permission): void
    {
        $admin = $this->createAdminUserWithoutPermissions();

        $this->actingAs($admin)
            ->get(route($routeName))
            ->assertForbidden();
    }

    #[DataProvider('protectedAdminRoutes')]
    public function test_admin_with_required_permission_can_open_protected_admin_route(string $routeName, string $permission): void
    {
        $admin = $this->createAdminUserWithoutPermissions();
        Permission::findOrCreate($permission);
        $admin->givePermissionTo($permission);

        $this->actingAs($admin)
            ->get(route($routeName))
            ->assertOk();
    }

    public function test_permissions_page_displays_arabic_labels_for_core_permissions(): void
    {
        $admin = $this->createAdminUserWithoutPermissions();
        Permission::findOrCreate('manage_permissions');
        Permission::findOrCreate('manage_users');
        $admin->givePermissionTo('manage_permissions');

        $this->actingAs($admin)
            ->get(route('admin.permissions.index'))
            ->assertOk()
            ->assertSee('إدارة المستخدمين')
            ->assertDontSee('manage_users');
    }

    public function test_roles_page_displays_arabic_labels_for_permissions(): void
    {
        $admin = $this->createAdminUserWithoutPermissions();
        Permission::findOrCreate('manage_roles');
        Permission::findOrCreate('manage_permissions');
        $admin->givePermissionTo('manage_roles');
        $adminRole = Role::findOrCreate('ADMIN');
        $adminRole->givePermissionTo('manage_permissions');

        $response = $this->actingAs($admin)
            ->get(route('admin.roles.index'))
            ->assertOk();

        $response->assertSee('إدارة الصلاحيات');
    }

    private function createAdminUserWithoutPermissions(): User
    {
        $adminRole = Role::findOrCreate('ADMIN');
        Role::findOrCreate('TRAINER');
        Role::findOrCreate('USER');
        $adminRole->syncPermissions([]);

        $admin = User::factory()->create([
            'phone_with_cc' => '+1000000' . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
        ]);
        $admin->assignRole('ADMIN');

        return $admin;
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function protectedAdminRoutes(): array
    {
        return [
            'dashboard' => ['admin.dashboard', 'view_admin'],
            'bookings_index' => ['admin.bookings.index', 'manage_plans'],
            'users_index' => ['admin.users.index', 'manage_users'],
            'payments_index' => ['admin.payments.index', 'manage_payments'],
            'app_wallet_account_index' => ['admin.app-wallet-account.index', 'manage_payments'],
            'app_expenses_index' => ['admin.app-expenses.index', 'manage_payments'],
            'reports_index' => ['admin.reports.index', 'manage_reports'],
            'payouts_report' => ['admin.reports.completed-payouts', 'manage_payouts'],
            'geo_index' => ['admin.geo.index', 'manage_geo'],
            'ratings_index' => ['admin.ratings.index', 'manage_ratings'],
            'wallets_index' => ['admin.wallets.index', 'manage_wallets'],
            'withdrawal_requests_index' => ['admin.withdrawal-requests.index', 'manage_wallets'],
            'notifications_index' => ['admin.notifications.index', 'manage_notifications'],
            'prizes_index' => ['admin.prizes.index', 'manage_rewards'],
            'rewards_points' => ['admin.rewards.points', 'manage_rewards'],
            'rewards_redemptions_report' => ['admin.rewards.redemptions-report', 'manage_rewards'],
            'roles_index' => ['admin.roles.index', 'manage_roles'],
            'permissions_index' => ['admin.permissions.index', 'manage_permissions'],
            'settings_index' => ['admin.settings.index', 'manage_settings'],
        ];
    }
}
