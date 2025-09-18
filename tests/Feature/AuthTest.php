<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_otp(): void
    {
        $res = $this->postJson('/api/v1/auth/request-otp', ['phone_with_cc' => '+201111100000']);
        $res->assertStatus(200)->assertJsonPath('success', true);
    }
}

