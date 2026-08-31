<?php

namespace Tests\Feature\Admin;

use App\Models\AdminNotice;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNoticeHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_notices_are_always_available_with_open_history_and_all_views(): void
    {
        $admin = User::factory()->create();
        $client = Client::query()->create([
            'client_type' => 'individual',
            'first_name' => 'John',
            'last_name' => 'Smith',
            'email' => 'john@example.com',
            'country_code' => 'US',
            'created_by_user_id' => $admin->id,
            'updated_by_user_id' => $admin->id,
        ]);

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.notices.index'), false);

        $open = AdminNotice::query()->create([
            'type' => 'secure_message_reply',
            'client_id' => $client->id,
            'title' => 'Open client message',
            'message' => 'John Smith sent a secure message.',
        ]);
        $dismissed = AdminNotice::query()->create([
            'type' => 'client_contact_change',
            'client_id' => $client->id,
            'title' => 'Dismissed account change',
            'message' => 'John Smith requested an account change.',
            'dismissed_by_user_id' => $admin->id,
            'dismissed_at' => now(),
        ]);

        $this->get(route('admin.notices.index'))
            ->assertOk()
            ->assertSee('Open client message')
            ->assertSee('Dismiss')
            ->assertDontSee('Dismissed account change');

        $this->get(route('admin.notices.index', ['view' => 'history']))
            ->assertOk()
            ->assertSee('Dismissed account change')
            ->assertSee('Account / portal')
            ->assertSee('by '.$admin->name)
            ->assertSee(route('admin.clients.show', $client), false)
            ->assertDontSee('Open client message');

        $this->get(route('admin.notices.index', ['view' => 'all']))
            ->assertOk()
            ->assertSee($open->title)
            ->assertSee($dismissed->title)
            ->assertSee('Open')
            ->assertSee('Dismissed');
    }
}
