<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Notifications\SupportTicketReplyNotification;
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

    public function test_authenticated_ticket_creation_links_ticket_to_user(): void
    {
        $user = User::factory()->create([
            'phone_with_cc' => '+10000003005',
            'email' => 'create-ticket@example.com',
        ]);
        $user->assignRole('USER');

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/support-tickets', [
            'name' => 'Ticket Owner',
            'phone_with_cc' => $user->phone_with_cc,
            'email' => $user->email,
            'subject' => 'Need support',
            'details' => 'Ticket details',
        ])
            ->assertStatus(201)
            ->assertJsonPath('success', true);

        $ticketId = $response->json('data.id');

        $this->assertDatabaseHas('support_tickets', [
            'id' => $ticketId,
            'user_id' => $user->id,
            'email' => $user->email,
            'phone_with_cc' => $user->phone_with_cc,
        ]);
    }

    public function test_admin_reply_links_legacy_ticket_to_matching_user_and_creates_notification(): void
    {
        $user = User::factory()->create([
            'phone_with_cc' => '+10000003006',
            'email' => 'legacy-ticket-owner@example.com',
        ]);
        $user->assignRole('USER');

        $admin = User::factory()->create([
            'phone_with_cc' => '+10000003007',
            'email' => 'legacy-ticket-admin@example.com',
        ]);
        $admin->assignRole('ADMIN');

        $ticket = SupportTicket::create([
            'user_id' => null,
            'name' => 'Legacy Ticket Owner',
            'phone_with_cc' => $user->phone_with_cc,
            'email' => $user->email,
            'subject' => 'Legacy support issue',
            'status' => 'open',
        ]);

        SupportTicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => null,
            'author_type' => 'user',
            'message' => 'Original legacy message',
        ]);

        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/support-tickets/' . $ticket->id . '/messages', [
            'message' => 'Admin follow up',
        ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.message.author_type', 'admin');

        $ticket->refresh();

        $this->assertSame($user->id, $ticket->user_id);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'type' => SupportTicketReplyNotification::class,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/notifications/badges')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.support_tickets.count', 1)
            ->assertJsonPath('data.support_tickets.has_unread', true);
    }
}
