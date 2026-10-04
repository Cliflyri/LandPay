<?php
namespace Tests\Feature\Portal;
use App\Models\{AdminNotice,Client,Improvement,PaymentPlan,PaymentPlanClient,PortalAccount,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
class ImprovementTest extends TestCase {
    use RefreshDatabase;
    private function account($client) {
        return PortalAccount::create(['client_id'=>$client->id,'email'=>$client->email,'password'=>'password','enabled'=>true]);
    }
    public function test_notification_photos_receipts_and_cards(): void {
        Storage::fake('local');
        [$admin,$client,$plan]=$this->records('IMPROVE');
        $account=$this->account($client);
        $this->actingAs($account,'client')->get(route('portal.improvements.index'))->assertOk()->assertSee('type="hidden" name="payment_plan_id"',false);
        $this->post(route('portal.improvements.store'),['payment_plan_id'=>$plan->id,'title'=>'Entrance gate','body'=>'Planning a gate.'])->assertSessionHasNoErrors()->assertRedirect();
        $improvement=Improvement::sole(); $first=$improvement->updates()->sole();
        $this->get(route('portal.improvements.show',$improvement))->assertOk()->assertSee('Admin notified');
        $this->get(route('portal.dashboard'))->assertOk()->assertSeeInOrder(['Your Plan','Your Improvements','Entrance gate']);
        $this->actingAs($admin,'web')->get(route('admin.improvements.show',$improvement))->assertOk()->assertSee('Acknowledge receipt');
        $this->assertNull($first->fresh()->received_at);
        $this->get(route('admin.plans.show',$plan))->assertOk()->assertSeeInOrder(['Clients','Improvements','Entrance gate']);
        $this->get(route('admin.dashboard'))->assertOk()->assertSee(route('admin.improvements.show',$improvement),false);
        $this->post(route('admin.improvements.acknowledge',[$improvement,$first]))->assertSessionHas('success');
        $stamp=$first->fresh()->received_at->toDateTimeString();
        $this->travel(1)->minutes();
        $this->post(route('admin.improvements.acknowledge',[$improvement,$first]))->assertSessionHas('success');
        $this->assertSame($stamp,$first->fresh()->received_at->toDateTimeString());
        $this->actingAs($account,'client')->get(route('portal.improvements.show',$improvement))->assertOk()->assertSee('Admin received');
        $this->post(route('portal.improvements.update',$improvement),['photos'=>[UploadedFile::fake()->image('gate.jpg'),UploadedFile::fake()->image('posts.png')]])->assertSessionHasNoErrors()->assertSessionHas('success');
        $latest=$improvement->updates()->latest('id')->first();
        $this->assertNull($latest->received_at);
        $this->assertCount(2,$latest->photos);
        $this->assertSame(2,AdminNotice::where('type','improvement_updated')->count());
        $this->assertSame(1,AdminNotice::where('type','improvement_updated')->whereNull('dismissed_at')->count());
        Storage::disk('local')->assertExists($latest->photos[0]['path']);
        $this->get(route('portal.improvements.photo',[$improvement,$latest,0]))->assertOk();
        $this->get(route('portal.improvements.show',$improvement))->assertOk()->assertSee('Admin received')->assertSee('Admin notified')->assertSee('gate.jpg');
        $this->actingAs($admin,'web')->get(route('admin.notices.index',['view'=>'all']))->assertOk()->assertSee('Improvement');
        $this->travelBack();
    }
    public function test_membership_and_photo_authorization_and_validation(): void {
        Storage::fake('local');
        [$admin,$client,$plan]=$this->records('OWNER');
        [, $other,$otherPlan]=$this->records('OTHER',$admin);
        $account=$this->account($client); $otherAccount=$this->account($other);
        $this->actingAs($account,'client')->post(route('portal.improvements.store'),['payment_plan_id'=>$otherPlan->id,'title'=>'No','body'=>'No'])->assertSessionHasErrors('payment_plan_id');
        $this->post(route('portal.improvements.store'),['payment_plan_id'=>$plan->id,'title'=>'Gate','body'=>'Plan','photos'=>[UploadedFile::fake()->create('bad.svg',1,'image/svg+xml')]])->assertSessionHasErrors('photos.0');
        $this->post(route('portal.improvements.store'),['payment_plan_id'=>$plan->id,'title'=>'Gate','body'=>'Plan','photos'=>[UploadedFile::fake()->image('gate.jpg')]])->assertSessionHasNoErrors();
        $improvement=Improvement::sole(); $update=$improvement->updates()->sole();
        $this->post(route('portal.improvements.update',$improvement),[])->assertSessionHasErrors('body');
        $this->get(route('portal.improvements.photo',[$improvement,$update,9]))->assertNotFound();
        $this->actingAs($otherAccount,'client')->get(route('portal.improvements.show',$improvement))->assertNotFound();
        $this->get(route('portal.improvements.photo',[$improvement,$update,0]))->assertNotFound();
        $this->post(route('portal.improvements.update',$improvement),['body'=>'Intrusion'])->assertNotFound();
        PaymentPlanClient::where('client_id',$client->id)->update(['effective_to'=>today()]);
        $this->actingAs($account,'client')->get(route('portal.improvements.show',$improvement))->assertNotFound();
        $this->get(route('portal.improvements.photo',[$improvement,$update,0]))->assertNotFound();
        $this->post(route('admin.improvements.acknowledge',[$improvement,$update]))->assertRedirect(route('admin.login'));
    }
    public function test_multiple_plans_and_closed_plan_behavior(): void {
        [$admin,$client,$plan]=$this->records('MULTI');
        [,, $second]=$this->records('SECOND',$admin);
        PaymentPlanClient::create(['payment_plan_id'=>$second->id,'client_id'=>$client->id,'role'=>'co_client','effective_from'=>today(),'created_by_user_id'=>$admin->id]);
        $this->actingAs($this->account($client),'client')->get(route('portal.improvements.index'))->assertOk()->assertSee('Select a plan')->assertSee($plan->plan_number)->assertSee($second->plan_number);
        $this->post(route('portal.improvements.store'),['payment_plan_id'=>$second->id,'title'=>'Fence','body'=>'Fence plan'])->assertSessionHasNoErrors();
        $improvement=Improvement::sole(); $second->update(['status'=>'closed']);
        $this->get(route('portal.improvements.show',$improvement))->assertOk()->assertDontSee('Send update to admin');
        $this->post(route('portal.improvements.update',$improvement),['body'=>'Change'])->assertForbidden();
        $this->post(route('portal.improvements.store'),['payment_plan_id'=>$second->id,'title'=>'New','body'=>'New'])->assertSessionHasErrors('payment_plan_id');
    }

    public function test_notes_share_one_thread_redirect_and_preserve_receipts(): void {
        \Illuminate\Support\Facades\Mail::fake();
        [$admin,$client,$plan]=$this->records('NOTES');
        $account=$this->account($client);
        $this->actingAs($account,'client')->post(route('portal.improvements.store'),['payment_plan_id'=>$plan->id,'title'=>'Gate','body'=>'Proposed gate'])->assertSessionHasNoErrors();
        $improvement=Improvement::sole(); $update=$improvement->updates()->sole();
        $this->assertNull($improvement->messageThread);
        $improvement->messageThread()->create(['client_id'=>$client->id,'payment_plan_id'=>$plan->id,'subject'=>'Improvement: Gate','category'=>'general','latest_message_at'=>now()]);
        $this->post(route('portal.improvements.notes.store',$improvement),['note'=>'Will use the north entrance.'])
            ->assertRedirect(route('portal.improvements.show',$improvement).'#notes');
        $thread=$improvement->fresh()->messageThread;
        $this->assertSame('Improvement: Gate',$thread->subject);
        $this->assertSame(1,$thread->messages()->count());
        $this->assertDatabaseHas('admin_notices',['secure_message_thread_id'=>$thread->id,'type'=>'secure_message_reply','dismissed_at'=>null]);
        $this->get(route('portal.messages.index'))->assertOk()->assertSee('Improvement: Gate');
        $this->get(route('portal.messages.show',$thread))->assertRedirect(route('portal.improvements.show',$improvement).'#notes');
        $this->actingAs($admin,'web')->get(route('admin.messages.show',$thread))->assertRedirect(route('admin.improvements.show',$improvement).'#notes');
        $this->get(route('admin.improvements.show',$improvement))->assertOk()->assertSee('Will use the north entrance.');
        $this->assertNotNull($thread->messages()->first()->admin_viewed_at);
        $this->assertNull($update->fresh()->received_at);
        $this->assertDatabaseHas('admin_notices',['improvement_update_id'=>$update->id,'dismissed_at'=>null]);
        $this->post(route('admin.improvements.notes.store',$improvement),['note'=>'Thanks for the clarification.'])->assertSessionHas('success');
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\SecureMessageNotificationMail::class);
        $this->assertSame(1,\App\Models\SecureMessageThread::where('improvement_id',$improvement->id)->count());
        $reply=$thread->messages()->where('sender_type','admin')->sole();
        $this->assertNull($reply->client_viewed_at);
        $this->actingAs($account,'client')->get(route('portal.improvements.show',$improvement))->assertOk()->assertSee('Thanks for the clarification.');
        $this->assertNotNull($reply->fresh()->client_viewed_at);
        $this->assertNull($update->fresh()->received_at);
        $this->post(route('portal.improvements.notes.store',$improvement),['note'=>'<script>alert(1)</script>'])->assertSessionHasNoErrors();
        $this->post(route('portal.improvements.notes.store',$improvement),['note'=>'Fourth note'])->assertSessionHasNoErrors();
        $this->get(route('portal.improvements.show',$improvement))->assertOk()->assertSee('Earlier notes (1)')->assertDontSee('<script>alert(1)</script>',false);
        $this->post(route('portal.improvements.notes.store',$improvement),['note'=>''])->assertSessionHasErrors('note');
        $this->post(route('portal.improvements.notes.store',$improvement),['note'=>str_repeat('a',2001)])->assertSessionHasErrors('note');
        $this->post(route('portal.messages.reply',$thread),['body'=>'Bypass'])->assertNotFound();
        [, $other]=$this->records('NOTEOTHER',$admin);
        $this->actingAs($this->account($other),'client')->post(route('portal.improvements.notes.store',$improvement),['note'=>'Intrusion'])->assertNotFound();
        $this->get(route('portal.messages.show',$thread))->assertNotFound();
        PaymentPlanClient::where('client_id',$client->id)->update(['effective_to'=>today()]);
        $this->actingAs($account,'client')->get(route('portal.messages.show',$thread))->assertNotFound();
        $this->post(route('portal.improvements.notes.store',$improvement),['note'=>'Former member'])->assertNotFound();
    }


    public function test_notes_are_scoped_to_their_section_and_links_target_it(): void {
        \Illuminate\Support\Facades\Mail::fake();
        [$admin,$client,$plan]=$this->records('SECTION');
        $this->actingAs($this->account($client),'client')->post(route('portal.improvements.store'),['payment_plan_id'=>$plan->id,'title'=>'Fence','body'=>'Original plan']);
        $improvement=Improvement::sole();$original=$improvement->updates()->sole();
        $this->post(route('portal.improvements.update',$improvement),['body'=>'Posts installed']);
        $progress=$improvement->updates()->latest('id')->first();
        foreach ([$original,$progress] as $section) {
            $this->post(route('portal.improvements.notes.store',$improvement),['note'=>'Question for '.$section->id,'improvement_update_id'=>$section->id])
                ->assertRedirect(route('portal.improvements.show',$improvement).'#notes-'.$section->id);
            $thread=$section->messageThread;
            $this->assertSame(1,$thread->messages()->count());
            $this->get(route('portal.messages.show',$thread))->assertRedirect(route('portal.improvements.show',$improvement).'#notes-'.$section->id);
        }
        $this->assertNull($improvement->fresh()->messageThread);
        $this->assertSame(2,\App\Models\SecureMessageThread::where('improvement_id',$improvement->id)->count());
        $this->get(route('portal.improvements.show',$improvement))->assertOk()
            ->assertSeeInOrder(['Original plan','Question for '.$original->id,'Posts installed','Question for '.$progress->id])
            ->assertDontSee('General improvement notes');
        $this->post(route('portal.improvements.notes.store',$improvement),['note'=>'Unscoped'])->assertStatus(422);
        $other=Improvement::create(['client_id'=>$client->id,'payment_plan_id'=>$plan->id,'title'=>'Other']);
        $wrong=$other->updates()->create(['body'=>'Other update']);
        $this->post(route('portal.improvements.notes.store',$improvement),['note'=>'Wrong section','improvement_update_id'=>$wrong->id])->assertNotFound();
        $this->actingAs($admin,'web')->post(route('admin.improvements.notes.store',$improvement),['note'=>'Reply to progress','improvement_update_id'=>$progress->id])
            ->assertRedirect(route('admin.improvements.show',$improvement).'#notes-'.$progress->id);
        $this->get(route('admin.messages.show',$progress->messageThread))->assertRedirect(route('admin.improvements.show',$improvement).'#notes-'.$progress->id);
        $this->assertNull($progress->fresh()->received_at);
    }


    public function test_admin_can_hide_restore_and_delete_notes_without_client_access(): void {
        \Illuminate\Support\Facades\Mail::fake();
        [$admin,$client,$plan]=$this->records('MODERATE');
        $account=$this->account($client);
        $this->actingAs($account,'client')->post(route('portal.improvements.store'),['payment_plan_id'=>$plan->id,'title'=>'Gate','body'=>'Plan']);
        $improvement=Improvement::sole();$section=$improvement->updates()->sole();
        $this->actingAs($admin,'web')->post(route('admin.improvements.notes.store',$improvement),['note'=>'Private discussion text','improvement_update_id'=>$section->id]);
        $thread=$section->messageThread;$note=$thread->messages()->sole();
        $this->patch(route('admin.improvements.notes.visibility',[$improvement,$note]),['hidden_from_client'=>1])->assertSessionHas('success');
        $this->assertTrue($note->fresh()->hidden_from_client);
        $this->assertFalse(\App\Models\SecureMessageThread::whereKey($thread->id)->unreadByClient()->exists());
        $this->get(route('admin.improvements.show',$improvement))->assertOk()->assertSee('Private discussion text')->assertSee('Hidden from client')->assertSee('Show to client');
        $this->actingAs($account,'client')->get(route('portal.improvements.show',$improvement))->assertOk()->assertDontSee('Private discussion text')->assertDontSee('Hide from client');
        $this->assertNull($note->fresh()->client_viewed_at);
        $this->get(route('portal.messages.index'))->assertOk()->assertDontSee('>Unread<',false);
        $this->actingAs($admin,'web')->patch(route('admin.improvements.notes.visibility',[$improvement,$note]),['hidden_from_client'=>0])->assertSessionHas('success');
        $this->assertTrue(\App\Models\SecureMessageThread::whereKey($thread->id)->unreadByClient()->exists());
        $this->actingAs($account,'client')->get(route('portal.improvements.show',$improvement))->assertOk()->assertSee('Private discussion text');
        $other=Improvement::create(['client_id'=>$client->id,'payment_plan_id'=>$plan->id,'title'=>'Other']);
        $this->actingAs($admin,'web')->delete(route('admin.improvements.notes.destroy',[$other,$note]))->assertNotFound();
        $this->delete(route('admin.improvements.notes.destroy',[$improvement,$note]))->assertSessionHas('success');
        $this->assertDatabaseMissing('secure_messages',['id'=>$note->id]);
        $this->assertNull($section->fresh()->received_at);
        $this->get(route('admin.improvements.show',$improvement))->assertOk()->assertDontSee('Private discussion text');
        // Client-authenticated sessions must never invoke admin moderation routes.
        $this->actingAs($account,'client')->post(route('portal.improvements.notes.store',$improvement),['note'=>'Client note','improvement_update_id'=>$section->id]);
        $clientNote=$thread->messages()->sole();
        auth('web')->logout();
        $this->patch(route('admin.improvements.notes.visibility',[$improvement,$clientNote]),['hidden_from_client'=>1])->assertRedirect(route('admin.login'));
        $this->delete(route('admin.improvements.notes.destroy',[$improvement,$clientNote]))->assertRedirect(route('admin.login'));
        $this->assertDatabaseHas('secure_messages',['id'=>$clientNote->id,'hidden_from_client'=>false]);
        $this->actingAs($admin,'web')->delete(route('admin.improvements.notes.destroy',[$improvement,$clientNote]))->assertSessionHas('success');
        $this->assertDatabaseMissing('secure_messages',['id'=>$clientNote->id]);
    }


    public function test_note_photos_preview_privacy_and_deletion(): void {
        Storage::fake('local'); \Illuminate\Support\Facades\Mail::fake();
        [$admin,$client,$plan]=$this->records('NOTEPHOTO');
        $account=$this->account($client);
        $this->actingAs($account,'client')->post(route('portal.improvements.store'),['payment_plan_id'=>$plan->id,'title'=>'Gate','body'=>'Plan']);
        $improvement=Improvement::sole();$section=$improvement->updates()->sole();
        $this->actingAs($admin,'web')->post(route('admin.improvements.notes.store',$improvement),[
            'improvement_update_id'=>$section->id,'attachments'=>[UploadedFile::fake()->image('reference.jpg')]
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
        $thread=$section->messageThread;$note=$thread->messages()->sole();$photo=$note->attachments()->sole();
        Storage::disk('local')->assertExists($photo->path);
        $this->get(route('admin.improvements.show',$improvement))->assertOk()->assertSee('data-bs-target="#secureMessageImageModal"',false)->assertSee('reference.jpg');
        $url=route('portal.messages.files.download',[$thread,$note,$photo]);
        $this->actingAs($account,'client')->get($url.'?inline=1')->assertOk()->assertHeader('Content-Type','image/jpeg');
        $this->get($url)->assertOk();
        $this->get(route('portal.improvements.show',$improvement))->assertOk()->assertSee('reference.jpg');
        $this->actingAs($admin,'web')->patch(route('admin.improvements.notes.visibility',[$improvement,$note]),['hidden_from_client'=>1])->assertSessionHas('success');
        $this->actingAs($account,'client')->get($url.'?inline=1')->assertNotFound();
        $this->get($url)->assertNotFound();
        $this->get(route('portal.improvements.show',$improvement))->assertOk()->assertDontSee('reference.jpg');
        $this->actingAs($admin,'web')->get(route('admin.messages.files.download',[$thread,$note,$photo]).'?inline=1')->assertOk();
        $this->delete(route('admin.improvements.notes.destroy',[$improvement,$note]))->assertSessionHas('success');
        Storage::disk('local')->assertMissing($photo->path);
        $this->assertDatabaseMissing('secure_message_attachments',['id'=>$photo->id]);
        $this->post(route('admin.improvements.notes.store',$improvement),[
            'improvement_update_id'=>$section->id,'attachments'=>[UploadedFile::fake()->create('bad.svg',1,'image/svg+xml')]
        ])->assertSessionHasErrors('attachments.0');
        $this->actingAs($account,'client')->post(route('portal.improvements.notes.store',$improvement),[
            'improvement_update_id'=>$section->id,'attachments'=>[UploadedFile::fake()->image('progress.png')]
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertDatabaseHas('admin_notices',['secure_message_thread_id'=>$thread->id,'type'=>'secure_message_reply']);
    }

    private function records(string $suffix, ?User $admin=null): array
    {
        $admin ??= User::factory()->create();
        $client=Client::query()->create(['client_type'=>'individual','first_name'=>'Portal','last_name'=>$suffix,'email'=>strtolower($suffix).'@example.com','country_code'=>'US','created_by_user_id'=>$admin->id,'updated_by_user_id'=>$admin->id]);
        $plan=PaymentPlan::query()->create(['plan_number'=>'LP-'.$suffix,'title'=>'Portal plan '.$suffix,'purchase_price'=>100000,'documentation_fee_standard'=>0,'documentation_fee_waived'=>0,'original_purchase_balance'=>100000,'customary_monthly_payment'=>10000,'monthly_service_fee'=>0,'monthly_due_day'=>1,'first_due_date'=>'2026-08-06','plan_start_date'=>'2026-08-01','status'=>'active','activated_at'=>now(),'created_by_user_id'=>$admin->id,'updated_by_user_id'=>$admin->id]);
        PaymentPlanClient::query()->create(['payment_plan_id'=>$plan->id,'client_id'=>$client->id,'role'=>'primary','responsibility'=>'joint','receives_invoices'=>true,'effective_from'=>'2026-08-01','contact_risk_acknowledged_at'=>now(),'contact_risk_acknowledgment_method'=>'admin_contract_acceptance','created_by_user_id'=>$admin->id]);
        return [$admin,$client,$plan];
    }
}
