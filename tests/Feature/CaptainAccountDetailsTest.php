<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserType;
use App\Models\Country;
use App\Models\User;
use Database\Seeders\GeoSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CaptainAccountDetailsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(GeoSeeder::class);
    }

    public function test_trainer_can_fetch_account_details(): void
    {
        $trainer = User::factory()->create([
            'phone_with_cc' => '+201555500001',
        ]);
        $trainer->assignRole('TRAINER');
        $token = $trainer->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/captain/account-details')
            ->assertOk()
            ->assertJsonPath('data.has_driving_license', false)
            ->assertJsonPath('data.pending_approval', false)
            ->assertJsonPath('data.approval_status', 'approved')
            ->assertJsonPath('data.guidelines.title', 'تنبيه هام');
    }

    public function test_captain_user_type_can_fetch_account_details_without_trainer_role_assignment(): void
    {
        Role::where('name', 'TRAINER')->delete();

        $trainer = User::factory()->create([
            'phone_with_cc' => '+201555500011',
            'user_type' => UserType::CAPTAIN,
        ]);
        $token = $trainer->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/captain/account-details')
            ->assertOk()
            ->assertJsonPath('data.pending_approval', false)
            ->assertJsonPath('data.approval_status', 'approved');
    }

    public function test_trainer_can_update_account_details(): void
    {
        $trainer = User::factory()->create([
            'phone_with_cc' => '+201555500002',
        ]);
        $trainer->assignRole('TRAINER');
        $token = $trainer->createToken('test')->plainTextToken;

        $country = Country::query()->first();

        $payload = [
            'bio' => 'مدربة معتمدة منذ عام 2017 ولدي خبرة في التدريب العملي والنظري.',
            'car_type' => 'تويوتا كامري',
            'car_model_year' => '2022',
            'has_driving_license' => true,
            'country_id' => $country->id,
            'area_level_1' => 'Riyadh Province',
            'area_level_2' => 'Riyadh',
            'area_level_3' => 'North',
            'locality' => 'Al Olaya',
            'car_available' => true,
            'pickup_available' => false,
        ];

        $this->withToken($token)
            ->postJson('/api/v1/captain/account-details', $payload)
            ->assertOk()
            ->assertJsonPath('data.car_type', 'تويوتا كامري')
            ->assertJsonPath('data.pending_approval', true)
            ->assertJsonPath('data.approval_status', 'pending')
            ->assertJsonPath('data.is_complete', true);

        $profile = $trainer->fresh()->trainerProfile;
        $this->assertNotNull($profile);
        $this->assertTrue((bool) $profile->pending_approval);
        $this->assertSame('تويوتا كامري', data_get($profile->pending_changes, 'car_type'));
        $this->assertSame('2022', data_get($profile->pending_changes, 'car_model_year'));
        $this->assertTrue((bool) data_get($profile->pending_changes, 'has_driving_license'));
    }

    public function test_trainer_location_update_is_applied_without_admin_approval(): void
    {
        $trainer = User::factory()->create([
            'phone_with_cc' => '+201555500004',
        ]);
        $trainer->assignRole('TRAINER');
        $token = $trainer->createToken('test')->plainTextToken;

        $oldCountry = Country::query()->firstOrFail();
        $newCountry = Country::query()->where('id', '!=', $oldCountry->id)->firstOrFail();

        $trainer->trainerProfile()->create([
            'country_id' => $oldCountry->id,
            'area_level_1' => 'Old Region',
            'has_driving_license' => false,
        ]);

        $this->withToken($token)
            ->postJson('/api/v1/captain/account-details', [
                'country_id' => $newCountry->id,
                'area_level_1' => 'New Region',
                'area_level_2' => 'New City',
                'has_driving_license' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.country_id', $newCountry->id)
            ->assertJsonPath('data.area_level_1', 'New Region')
            ->assertJsonPath('data.area_level_2', 'New City');

        $this->assertDatabaseHas('trainer_profiles', [
            'user_id' => $trainer->id,
            'country_id' => $newCountry->id,
            'area_level_1' => 'New Region',
            'area_level_2' => 'New City',
            'pending_approval' => false,
        ]);

        $this->assertNull($trainer->fresh()->banned_until);
    }

    public function test_non_trainer_cannot_access_account_details(): void
    {
        $user = User::factory()->create([
            'phone_with_cc' => '+201555500003',
        ]);
        $user->assignRole('USER');
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/captain/account-details')
            ->assertStatus(403);
    }

    public function test_pending_account_details_are_returned_from_show_endpoint(): void
    {
        $trainer = User::factory()->create([
            'phone_with_cc' => '+201555500005',
        ]);
        $trainer->assignRole('TRAINER');
        $token = $trainer->createToken('test')->plainTextToken;

        $country = Country::query()->firstOrFail();

        $trainer->trainerProfile()->create([
            'bio' => 'bio-old',
            'car_type' => 'car-old',
            'country_id' => $country->id,
            'area_level_1' => 'Riyadh Province',
            'has_driving_license' => false,
            'pending_approval' => true,
            'pending_changes' => [
                'bio' => 'bio-pending',
                'car_type' => 'car-pending',
                'has_driving_license' => true,
            ],
            'pending_approval_at' => now(),
        ]);

        $this->withToken($token)
            ->getJson('/api/v1/captain/account-details')
            ->assertOk()
            ->assertJsonPath('data.pending_approval', true)
            ->assertJsonPath('data.approval_status', 'pending')
            ->assertJsonPath('data.bio', 'bio-pending')
            ->assertJsonPath('data.car_type', 'car-pending')
            ->assertJsonPath('data.has_driving_license', true)
            ->assertJsonPath('data.pending_changes.bio', 'bio-pending')
            ->assertJsonPath('data.pending_changes.car_type', 'car-pending');
    }
}
