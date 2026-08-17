<?php

namespace Tests\Feature\Admin;

use App\Models\Client;
use App\Models\PortalAccount;
use App\Models\SecureMessageRevision;
use App\Models\SecureMessageThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecureMessageEditingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_edit_admin_message_with_history_hidden_from_client(): void
    {
        [$admin, $account, $thread] = $this->records();
        $message = $thread->messages()->create(['sender_type'=>'admin','sender_user_id'=>$admin->id,'body'=>'Original text']);

        $this->actingAs($admin)->put(route('admin.messages.update', [$thread, $message]), ['body'=>'Corrected text'])
            ->assertRedirect()->assertSessionHas('success');

        $this->assertSame('Corrected text', $message->fresh()->body);
        $this->assertDatabaseHas('secure_message_revisions', ['secure_message_id'=>$message->id,'body'=>'Original text','edited_by_user_id'=>$admin->id]);
        $this->get(route('admin.messages.show', $thread))->assertOk()->assertSee('>Edit<', false)->assertSee('Edited')->assertDontSee('Original text');
        $this->actingAs($account, 'client')->get(route('portal.messages.show', $thread))
            ->assertOk()->assertSee('Corrected text')->assertDontSee('>Edit<', false)->assertDontSee('Edited')->assertDontSee('Original text');
    }

    public function test_admin_can_toggle_follow_up_from_message_list(): void
    {
        [$admin, , $thread] = $this->records();
        $this->actingAs($admin)->get(route('admin.messages.index'))->assertOk()->assertSee('Mark for follow-up');
        $this->post(route('admin.messages.star', $thread))->assertRedirect();
        $this->assertNotNull($thread->fresh()->starred_at);
        $this->get(route('admin.messages.index'))->assertOk()->assertSee('Remove follow-up');
        $this->post(route('admin.messages.star', $thread))->assertRedirect();
        $this->assertNull($thread->fresh()->starred_at);
    }

    public function test_admin_cannot_edit_client_message(): void
    {
        [$admin, , $thread] = $this->records();
        $message = $thread->messages()->create(['sender_type'=>'client','sender_client_id'=>$thread->client_id,'body'=>'Client text']);

        $this->actingAs($admin)->put(route('admin.messages.update', [$thread, $message]), ['body'=>'Changed'])->assertNotFound();
        $this->assertSame('Client text', $message->fresh()->body);
        $this->assertSame(0, SecureMessageRevision::query()->count());
    }

    private function records(): array
    {
        $admin = User::factory()->create();
        $client = Client::query()->create(['client_type'=>'individual','first_name'=>'Edit','last_name'=>'Client','email'=>'edit@example.com','country_code'=>'US','created_by_user_id'=>$admin->id,'updated_by_user_id'=>$admin->id]);
        $account = PortalAccount::query()->create(['client_id'=>$client->id,'email'=>$client->email,'password'=>'password','enabled'=>true]);
        $thread = SecureMessageThread::query()->create(['client_id'=>$client->id,'subject'=>'Editable message','category'=>'general','latest_message_at'=>now()]);
        return [$admin, $account, $thread];
    }
}
