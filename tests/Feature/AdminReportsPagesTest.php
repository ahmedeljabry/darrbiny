<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportsPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_open_all_report_pages(): void
    {
        $admin = User::factory()->create([
            'phone_with_cc' => '+10000004001',
            'email' => 'admin-reports@example.com',
        ]);
        $admin->assignRole('ADMIN');

        $this->actingAs($admin);

        $routes = [
            'admin.reports.index',
            'admin.reports.sales',
            'admin.reports.payments',
            'admin.reports.subscriptions',
            'admin.reports.plan-sales',
            'admin.reports.app-fees',
            'admin.reports.vat',
            'admin.reports.completed-payouts',
            'admin.reports.active-courses',
            'admin.reports.awaiting-offers',
            'admin.reports.rejected-progress',
            'admin.reports.wallet-balances',
            'admin.reports.points-balances',
            'admin.reports.reward-redemptions',
            'admin.reports.wallet-payments',
        ];

        foreach ($routes as $routeName) {
            $this->get(route($routeName))->assertOk();
        }
    }

    public function test_custom_report_pages_show_only_supported_export_actions(): void
    {
        $admin = User::factory()->create([
            'phone_with_cc' => '+10000004002',
            'email' => 'admin-reports-actions@example.com',
        ]);
        $admin->assignRole('ADMIN');
        $this->actingAs($admin);

        $this->get(route('admin.reports.active-courses'))
            ->assertOk()
            ->assertSee('تصدير Excel');

        $this->get(route('admin.reports.awaiting-offers'))
            ->assertOk()
            ->assertDontSee('تصدير Excel');
    }

    public function test_daily_reports_expose_name_phone_and_date_range_filters(): void
    {
        $admin = User::factory()->create([
            'phone_with_cc' => '+10000004003',
            'email' => 'admin-reports-date@example.com',
        ]);
        $admin->assignRole('ADMIN');
        $this->actingAs($admin);

        $this->get(route('admin.reports.completed-payouts'))
            ->assertOk()
            ->assertSee('name="name"', false)
            ->assertSee('name="phone"', false)
            ->assertSee('name="date_from"', false)
            ->assertSee('name="date_to"', false);

        $this->get(route('admin.reports.rejected-progress'))
            ->assertOk()
            ->assertSee('name="name"', false)
            ->assertSee('name="phone"', false)
            ->assertSee('name="date_from"', false)
            ->assertSee('name="date_to"', false);
    }
}
