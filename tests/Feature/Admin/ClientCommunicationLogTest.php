<?php

namespace Tests\Feature\Admin;

use App\Models\{Client, User, PaymentPlan, PlanClosing, ClosingEvent, SecureMessageThread, SecureMessage};
use App\Services\ClientCommunicationLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClientCommunicationLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_records_are_client_scoped_and_render_with_links(): void
    {
        $admin = User::factory()->create();
        $client = Client::create(['client_type'=>'individual','first_name'=>'Log','last_name'=>'Client','email'=>'log@example.com','country_code'=>'US','created_by_user_id'=>$admin->id,'updated_by_user_id'=>$admin->id]);
        $other = Client::create(['client_type'=>'individual','first_name'=>'Other','last_name'=>'Client','email'=>'other@example.com','country_code'=>'US','created_by_user_id'=>$admin->id,'updated_by_user_id'=>$admin->id]);
        $plan = PaymentPlan::create(['plan_number'=>'LOG-1','title'=>'Log plan','original_purchase_balance'=>10000,'customary_monthly_payment'=>1000,'monthly_due_day'=>1,'first_due_date'=>'2026-10-01','plan_start_date'=>'2026-10-01','status'=>'active','created_by_user_id'=>$admin->id,'updated_by_user_id'=>$admin->id]);
        $closing = PlanClosing::create(['payment_plan_id'=>$plan->id]);
        ClosingEvent::create(['plan_closing_id'=>$closing->id,'action'=>'Client notification','context'=>[
            ['client_id'=>$client->id,'email'=>'sent','sms'=>'not eligible'],
            ['client_id'=>$other->id,'email'=>'other-client-only','sms'=>'failed'],
        ]]);
        ClosingEvent::create(['plan_closing_id'=>$closing->id,'action'=>'Client notification','context'=>[['client_id'=>$other->id,'email'=>'failed']]]);
        $thread = SecureMessageThread::create(['client_id'=>$client->id,'plan_closing_id'=>$closing->id,'subject'=>'Signing question']);
        SecureMessage::create(['secure_message_thread_id'=>$thread->id,'sender_type'=>'client','sender_client_id'=>$client->id,'body'=>'My signing question']);
        $otherThread = SecureMessageThread::create(['client_id'=>$other->id,'subject'=>'Private other message']);
        SecureMessage::create(['secure_message_thread_id'=>$otherThread->id,'sender_type'=>'client','sender_client_id'=>$other->id,'body'=>'Do not show this']);
        DB::table('email_deliveries')->insert(['recipient_client_id'=>$client->id,'payment_plan_id'=>$plan->id,'sent_by_user_id'=>$admin->id,'template_slug'=>'test-email','recipient_email'=>'old-address@example.com','subject_snapshot'=>'Saved email','body_snapshot'=>'<p>Saved body</p>','status'=>'sent','created_at'=>now(),'updated_at'=>now()]);
        DB::table('sms_deliveries')->insert(['recipient_client_id'=>$client->id,'message_type'=>'closing','recipient_phone'=>'+15555550100','message_snapshot'=>'Saved closing SMS','idempotency_key'=>'client-log-test','status'=>'sent','created_at'=>now(),'updated_at'=>now()]);
        $invoice = \App\Models\Invoice::create(['payment_plan_id'=>$plan->id,'invoice_number'=>'LOG-INVOICE','issue_date'=>today(),'due_date'=>today(),'status'=>'issued','created_by_user_id'=>$admin->id]);
        DB::table('invoice_reminders')->insert(['recipient_client_id'=>$client->id,'payment_plan_id'=>$plan->id,'invoice_id'=>$invoice->id,'recipient_email'=>'old-address@example.com','balance_snapshot'=>100,'status'=>'sent','sent_by_user_id'=>$admin->id,'created_at'=>now(),'updated_at'=>now()]);

        $entries = app(ClientCommunicationLog::class)->forClient($client);
        $this->assertSame(5, $entries->total());
        $secure = $entries->getCollection()->firstWhere('source','secure_messages');
        $this->assertSame($plan->id, $secure->plan->id);
        $this->assertNull($entries->getCollection()->firstWhere('source','sms_deliveries')->plan);
        $outcome = $entries->getCollection()->firstWhere('source','closing_events');
        $this->assertStringContainsString('Email: sent', $outcome->body);
        $this->assertStringNotContainsString('other-client-only', $outcome->body);

        $this->actingAs($admin)->get(route('admin.clients.show',['client'=>$client,'tab'=>'communications']))
            ->assertOk()->assertSee('Saved closing SMS')->assertSee('My signing question')
            ->assertSee('LOG-1')->assertSee('Client-level')->assertSee('old-address@example.com')
            ->assertSee(route('admin.messages.show',$thread),false)
            ->assertSee(route('admin.invoices.show',$invoice),false)
            ->assertDontSee('Do not show this')->assertDontSee('other-client-only');
        $this->get(route('admin.clients.show',$client))->assertOk()->assertSee('Contact information');
        $this->get(route('admin.clients.show',['client'=>$other,'tab'=>'communications']))->assertOk()->assertDontSee('Saved closing SMS');
    }

    public function test_empty_log_and_pagination(): void
    {
        $admin=User::factory()->create();
        $client=Client::create(['client_type'=>'individual','first_name'=>'Paging','last_name'=>'Client','country_code'=>'US','created_by_user_id'=>$admin->id,'updated_by_user_id'=>$admin->id]);
        $this->assertSame(0,app(ClientCommunicationLog::class)->forClient($client)->total());
        $thread=SecureMessageThread::create(['client_id'=>$client->id,'subject'=>'Pagination']);
        for ($i=0;$i<26;$i++) SecureMessage::create(['secure_message_thread_id'=>$thread->id,'sender_type'=>'admin','sender_user_id'=>$admin->id,'body'=>'Message '.$i]);
        $first=app(ClientCommunicationLog::class)->forClient($client);
        $this->assertSame(26,$first->total());
        $this->assertCount(25,$first->items());
        $this->assertSame('Message 25',$first->items()[0]->body);
        $this->actingAs($admin)->get(route('admin.clients.show',['client'=>$client,'tab'=>'communications','communications_page'=>2]))
            ->assertOk()->assertSee('Message 0')->assertDontSee('Message 25');
    }
}
