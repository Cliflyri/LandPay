<?php
namespace Tests\Feature\Portal;
use App\Models\Client;
use App\Models\PortalAccount;
use App\Models\SecureMessage;
use App\Models\SharedDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
class SharedDocumentTest extends TestCase{
 use RefreshDatabase;
 public function test_admin_can_share_reference_hide_archive_and_delete_a_document():void{
  Storage::fake('local');Mail::fake();[$admin,$client,$account]=$this->records();
  $this->actingAs($admin)->post(route('admin.documents.store'),['client_id'=>$client->id,'category'=>'contract','document'=>UploadedFile::fake()->create('contract.pdf',10,'application/pdf'),'visible_to_client'=>1,'notify_client'=>1,'message'=>'Please review this contract.'])->assertSessionHas('success');
  $document=SharedDocument::query()->sole();$this->assertTrue($document->visible_to_client);Storage::disk('local')->assertExists($document->path);$this->assertSame($document->id,SecureMessage::query()->sole()->shared_document_id);
  $this->actingAs($account,'client')->get(route('portal.documents.index'))->assertOk()->assertSee('contract.pdf');
  $this->get(route('portal.documents.download',$document))->assertOk();
  $this->get(route('portal.messages.show',SecureMessage::query()->sole()->thread))->assertOk()->assertSee('View document');
  $this->actingAs($admin)->post(route('admin.documents.visibility',$document))->assertSessionHas('success');
  $this->actingAs($account,'client')->get(route('portal.documents.download',$document))->assertNotFound();
  $this->actingAs($admin)->post(route('admin.documents.archive',$document))->assertSessionHas('success');
  $this->delete(route('admin.documents.destroy',$document))->assertSessionHas('success');
  $this->assertDatabaseMissing('shared_documents',['id'=>$document->id]);Storage::disk('local')->assertMissing($document->path);
 }
 public function test_client_can_upload_a_private_document_for_their_account():void{
  Storage::fake('local');[$admin,$client,$account]=$this->records();
  $this->actingAs($account,'client')->post(route('portal.documents.store'),['category'=>'general','document'=>UploadedFile::fake()->create('notes.docx',10,'application/vnd.openxmlformats-officedocument.wordprocessingml.document')])->assertSessionHas('success');
  $document=SharedDocument::query()->sole();$this->assertSame($client->id,$document->uploaded_by_client_id);$this->assertTrue($document->visible_to_client);$this->assertDatabaseHas('admin_notices',['type'=>'shared_document_uploaded','client_id'=>$client->id]);
 }
 private function records():array{$admin=User::factory()->create();$client=Client::query()->create(['client_type'=>'individual','first_name'=>'Document','last_name'=>'Client','email'=>'documents@example.com','country_code'=>'US','created_by_user_id'=>$admin->id,'updated_by_user_id'=>$admin->id]);$account=PortalAccount::query()->create(['client_id'=>$client->id,'email'=>$client->email,'password'=>'password','enabled'=>true]);return [$admin,$client,$account];}
}
