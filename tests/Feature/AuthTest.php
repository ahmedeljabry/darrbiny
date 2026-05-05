<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Country;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_derives_jordanian_dinar_from_jordan_phone(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Jordan User',
            'phone_with_cc' => '+962790000001',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'type' => 'user',
        ])
            ->assertCreated()
            ->assertJsonPath('data.user.currency', 'JOD');

        $this->assertDatabaseHas('users', [
            'phone_with_cc' => '+962790000001',
            'currency' => 'JOD',
        ]);
    }

    public function test_login_can_store_nullable_fcm_token_when_present(): void
    {
        $user = User::factory()->create([
            'phone_with_cc' => '+201111199999',
            'password' => Hash::make('password123'),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'phone_with_cc' => '+201111199999',
            'password' => 'password123',
            'fcm_token' => 'signin-fcm-token',
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('user_device_tokens', [
            'user_id' => $user->id,
            'token' => 'signin-fcm-token',
        ]);
    }

    public function test_login_accepts_missing_fcm_token(): void
    {
        User::factory()->create([
            'phone_with_cc' => '+201111188888',
            'password' => Hash::make('password123'),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'phone_with_cc' => '+201111188888',
            'password' => 'password123',
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('user_device_tokens', 0);
    }

    public function test_change_password_without_auth_by_mobile(): void
    {
        $user = User::factory()->create([
            'phone_with_cc' => '+201111100000',
            'password' => Hash::make('oldpassword123'),
        ]);

        $res = $this->postJson('/api/v1/auth/change-password', [
            'mobile' => '+201111100000',
            'newpassword' => 'newpassword123',
            'confirm_password' => 'newpassword123',
        ]);

        $res->assertStatus(200)->assertJsonPath('success', true);
        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }

    public function test_authenticated_user_can_update_and_fetch_bank_account_name(): void
    {
        $country = Country::create([
            'name' => 'Saudi Arabia',
            'iso2' => 'SA',
            'currency' => 'SAR',
        ]);

        $user = User::factory()->create([
            'phone_with_cc' => '+201111100001',
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/auth/bank-account', [
            'bank_account' => '1234567890',
            'bank_account_name' => 'Ahmed Ali',
            'iban' => 'SA0380000000608010167519',
            'bank_name' => 'Test Bank',
            'bank_country_id' => $country->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.bank_account', '1234567890')
            ->assertJsonPath('data.bank_account_name', 'Ahmed Ali')
            ->assertJsonPath('data.bank_name', 'Test Bank')
            ->assertJsonPath('data.bank_country_id', $country->id);

        $this->getJson('/api/v1/auth/bank-account')
            ->assertOk()
            ->assertJsonPath('data.bank_account', '1234567890')
            ->assertJsonPath('data.bank_account_name', 'Ahmed Ali')
            ->assertJsonPath('data.bank_name', 'Test Bank')
            ->assertJsonPath('data.bank_country.id', $country->id)
            ->assertJsonPath('data.bank_country.name', 'Saudi Arabia');
    }

    public function test_profile_endpoint_exposes_can_change_pic_and_disables_it_after_first_picture_update(): void
    {
        config(['filesystems.default' => 'public']);
        Storage::fake('public');

        $user = User::factory()->create([
            'phone_with_cc' => '+201111100002',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.canChangePic', true);

        $this->post('/api/v1/auth/profile', [
            'profile_picture' => UploadedFile::fake()->image('avatar.jpg'),
        ])
            ->assertOk()
            ->assertJsonPath('data.canChangePic', false);

        $this->assertFalse((bool) $user->fresh()->can_change_picture);

        $this->post('/api/v1/auth/profile', [
            'profile_picture' => UploadedFile::fake()->image('avatar-2.jpg'),
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.message', 'لا يمكنك تغيير صورة الملف الشخصي مرة أخرى');
    }
}
