<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Country;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

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
}
