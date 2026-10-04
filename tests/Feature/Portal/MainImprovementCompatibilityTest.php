<?php
namespace Tests\Feature\Portal;
use App\Models\{AdminNotice,Client,PortalAccount,SecureMessageThread,User};
use App\Services\{AdminNoticeEmailService,SecureMessageNotificationService};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\{Mail,Schema,Storage};
use Tests\TestCase;

class MainImprovementCompatibilityTest extends TestCase {
 use DatabaseMigrations;
 public function runDatabaseMigrations() {
  $this->refreshTestDatabase();
  $this->beforeApplicationDestroyed(function () {
   // Avoid unrelated legacy down() defects; next test rebuilds the isolated test DB.
   \Illuminate\Foundation\Testing\RefreshDatabaseState::$migrated=false;
  });
 }
 private function records(): array {
  Mail::fake();Storage::fake('local');
  $admin=User::factory()->create();
  $client=Client::create(['client_type'=>'individual','first_name'=>'Compat','last_name'=>'Client','email'=>'compat@example.com','country_code'=>'US','created_by_user_id'=>$admin->id,'updated_by_user_id'=>$admin->id]);
  $account=PortalAccount::create(['client_id'=>$client->id,'email'=>$client->email,'password'=>'password','enabled'=>true]);
  return [$admin,$client,$account];
 }
 private function ordinaryMessages($admin,$client,$account): void {
  $this->actingAs($admin,'web')->post(route('admin.messages.store'),['client_id'=>$client->id,'subject'=>'Ordinary conversation','category'=>'general','body'=>'Normal message'])->assertSessionHasNoErrors()->assertRedirect();
  $thread=SecureMessageThread::where('subject','Ordinary conversation')->sole();
  $this->actingAs($account,'client')->get(route('portal.messages.index'))->assertOk()->assertSee('Ordinary conversation');
  $this->get(route('portal.messages.show',$thread))->assertOk()->assertSee('Normal message');
  $this->post(route('portal.messages.reply',$thread),['body'=>'Normal reply'])->assertSessionHas('success');
  $this->actingAs($admin,'web')->get(route('admin.messages.show',$thread))->assertOk()->assertSee('Normal reply');
  $this->delete(route('admin.messages.destroy',$thread))->assertSessionHas('success');
  $this->assertDatabaseMissing('secure_message_threads',['id'=>$thread->id]);
 }
 public function test_main_without_feature_columns_keeps_normal_messages_working(): void {
  $this->assertFalse(Schema::hasColumn('secure_message_threads','improvement_id'));
  $this->ordinaryMessages(...$this->records());
 }
 public function test_main_with_feature_columns_hides_and_blocks_entire_conversations(): void {
  // Reproduce the added column shapes without requiring feature migrations on main.
  Schema::table('secure_message_threads',function(Blueprint $table){
   $table->unsignedBigInteger('improvement_id')->nullable()->index();
   $table->unsignedBigInteger('improvement_update_id')->nullable()->unique();
  });
  Schema::table('secure_messages',fn(Blueprint $table)=>$table->boolean('hidden_from_client')->default(false));
  Schema::table('admin_notices',fn(Blueprint $table)=>$table->unsignedBigInteger('improvement_update_id')->nullable());
  [$admin,$client,$account]=$this->records();
  $thread=SecureMessageThread::create(['client_id'=>$client->id,'subject'=>'Protected improvement','category'=>'general','latest_message_at'=>now(),'improvement_id'=>123,'improvement_update_id'=>456]);
  // Models may cache the pre-migration column list from the previous test.
  $thread->forceFill(['improvement_id'=>123,'improvement_update_id'=>456])->save();
  $message=$thread->messages()->create(['sender_type'=>'admin','sender_user_id'=>$admin->id,'body'=>'Hidden sensitive note','hidden_from_client'=>true,'attachment_path'=>'private.jpg','attachment_disk'=>'local','attachment_name'=>'private.jpg','attachment_mime'=>'image/jpeg']);
  $attachment=$message->attachments()->create(['disk'=>'local','path'=>'photo.jpg','name'=>'photo.jpg','mime'=>'image/jpeg','size'=>1]);
  Storage::disk('local')->put('photo.jpg','private');
  Storage::disk('local')->put('private.jpg','private');
  $notice=AdminNotice::create(['type'=>'secure_message_reply','client_id'=>$client->id,'secure_message_thread_id'=>$thread->id,'title'=>'Protected notice','message'=>'Private improvement notice']);
  $updateNotice=AdminNotice::create(['type'=>'improvement_updated','improvement_update_id'=>456,'title'=>'Protected update','message'=>'Private improvement update']);
  $updateNotice->forceFill(['improvement_update_id'=>456])->save();
  $this->assertSame(0,SecureMessageThread::whereKey($thread->id)->unreadByClient()->count(), SecureMessageThread::query()->toSql());
  $this->assertSame(0,AdminNotice::whereKey([$notice->id,$updateNotice->id])->count());
  $this->assertFalse(app(SecureMessageNotificationService::class)->send($thread));
  $this->assertFalse(app(AdminNoticeEmailService::class)->send($notice));
  $this->assertFalse(app(AdminNoticeEmailService::class)->send($updateNotice));
  Mail::assertNothingSent();
  $this->actingAs($account,'client')->get(route('portal.messages.index'))->assertOk()->assertDontSee('Protected improvement');
  foreach(['show'=>[$thread],'download'=>[$thread,$message],'files.download'=>[$thread,$message,$attachment]] as $action=>$params) {
   $this->get(route('portal.messages.'.$action,$params))->assertNotFound();
  }
  $this->post(route('portal.messages.reply',$thread),['body'=>'Attempt'])->assertNotFound();
  $this->actingAs($admin,'web')->get(route('admin.messages.index'))->assertOk()->assertDontSee('Protected improvement');
  $this->get(route('admin.notices.index'))->assertOk()->assertDontSee('Protected notice')->assertDontSee('Protected update');
  $this->get(route('admin.dashboard'))->assertOk()->assertDontSee('Protected notice')->assertDontSee('Protected update');
  foreach(['show'=>[$thread],'download'=>[$thread,$message],'files.download'=>[$thread,$message,$attachment]] as $action=>$params) $this->get(route('admin.messages.'.$action,$params))->assertNotFound();
  foreach(['reply','star','remind'] as $action) $this->post(route('admin.messages.'.$action,$thread),['body'=>'Attempt'])->assertNotFound();
  $this->put(route('admin.messages.update',[$thread,$message]),['body'=>'Attempt'])->assertNotFound();
  foreach(['destroy'=>[$thread],'files.destroy'=>[$thread,$message,$attachment],'attachments.destroy'=>[$thread,$message]] as $action=>$params) $this->delete(route('admin.messages.'.$action,$params))->assertNotFound();
  $this->post(route('admin.notices.dismiss',$notice))->assertNotFound();
  $this->assertDatabaseHas('secure_messages',['id'=>$message->id,'body'=>'Hidden sensitive note']);
  Storage::disk('local')->assertExists('photo.jpg');
  $this->ordinaryMessages($admin,$client,$account);
 }
}
