<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SupportTicketChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_user_and_admin_can_chat_inside_ticket_thread(): void
    {
        $user = User::factory()->create([
            'phone_with_cc' => '+10000003001',
            'email' => 'user-ticket@example.com',
        ]);
        $user->assignRole('USER');

        $admin = User::factory()->create([
            'phone_with_cc' => '+10000003002',
            'email' => 'admin-ticket@example.com',
        ]);
        $admin->assignRole('ADMIN');

        $ticket = SupportTicket::create([
            'user_id' => $user->id,
            'name' => 'Ticket User',
            'phone_with_cc' => $user->phone_with_cc,
            'email' => $user->email,
            'subject' => 'Need help',
            'status' => 'closed',
        ]);

        SupportTicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'author_type' => 'user',
            'message' => 'Initial message',
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/support-tickets/' . $ticket->id . '/messages', [
            'message' => 'User follow up',
        ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.ticket_status', 'open')
            ->assertJsonPath('data.message.author_type', 'user');

        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/support-tickets/' . $ticket->id . '/messages', [
            'message' => 'Admin reply',
        ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.ticket_status', 'open')
            ->assertJsonPath('data.message.author_type', 'admin');

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/support-tickets/' . $ticket->id . '/messages')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.ticket.status', 'open')
            ->assertJsonPath('data.messages.1.message', 'User follow up')
            ->assertJsonPath('data.messages.2.message', 'Admin reply');
    }

    public function test_non_owner_cannot_view_or_send_ticket_messages(): void
    {
        $owner = User::factory()->create([
            'phone_with_cc' => '+10000003003',
            'email' => 'owner-ticket@example.com',
        ]);
        $owner->assignRole('USER');

        $otherUser = User::factory()->create([
            'phone_with_cc' => '+10000003004',
            'email' => 'other-ticket@example.com',
        ]);
        $otherUser->assignRole('USER');

        $ticket = SupportTicket::create([
            'user_id' => $owner->id,
            'name' => 'Owner',
            'phone_with_cc' => $owner->phone_with_cc,
            'email' => $owner->email,
            'subject' => 'Private ticket',
            'status' => 'open',
        ]);

        Sanctum::actingAs($otherUser);

        $this->getJson('/api/v1/support-tickets/' . $ticket->id . '/messages')
            ->assertStatus(403);

        $this->postJson('/api/v1/support-tickets/' . $ticket->id . '/messages', [
            'message' => 'Should fail',
        ])
            ->assertStatus(403);
    }
}
