<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_otp(): void
    {
        $res = $this->postJson('/api/v1/auth/request-otp', ['phone_with_cc' => '+201111100000']);
        $res->assertStatus(200)->assertJsonPath('success', true);
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
}
