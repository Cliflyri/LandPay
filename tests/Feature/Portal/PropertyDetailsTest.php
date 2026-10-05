<?php
namespace Tests\Feature\Portal;
use App\Models\{Client,PaymentPlan,PaymentPlanClient,PortalAccount,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
class PropertyDetailsTest extends TestCase {
 use RefreshDatabase;
 private function account($client) {return PortalAccount::create(['client_id'=>$client->id,'email'=>$client->email,'password'=>'password','enabled'=>true]);}
 public function test_optional_card_editor_gallery_and_private_access(): void {
  Storage::fake('local');[$admin,$client,$plan]=$this->records('PROPERTY');
  $account=$this->account($client);
  $this->actingAs($account,'client')->get(route('portal.dashboard'))->assertOk()->assertDontSee('My Property');
  $this->actingAs($admin,'web')->get(route('admin.plans.show',$plan))->assertOk()->assertSee('My Property')->assertSee('Edit property details');
  $this->get(route('admin.property.edit',$plan))->assertOk();
  $this->put(route('admin.property.update',$plan),[
   'property_latitude'=>'34.123','property_longitude'=>'-112.456','property_notes'=>'Enter at the north gate.',
   'photos'=>[UploadedFile::fake()->image('first.jpg'),UploadedFile::fake()->image('second.png')]
  ])->assertSessionHasNoErrors()->assertSessionHas('success');
  $photos=$plan->fresh()->property_photos;
  $this->assertCount(2,$photos);
  $this->actingAs($account,'client')->get(route('portal.dashboard'))->assertOk()
   ->assertSeeInOrder(['Your Plan','My Property','Your Improvements'])->assertSee('Enter at the north gate.')->assertSee('maps.google.com/maps',false)->assertSee('data-property-gallery',false);
  $photoUrl=route('portal.property.photo',[$plan,$photos[0]['id']]);
  $this->get($photoUrl)->assertOk();
  [,$other]=$this->records('OTHERPROPERTY',$admin);
  $this->actingAs($this->account($other),'client')->get($photoUrl)->assertNotFound();
  $this->actingAs($admin,'web')->put(route('admin.property.update',$plan),[
   'order'=>[$photos[0]['id']=>2,$photos[1]['id']=>1],
  ])->assertSessionHasNoErrors();
  $this->assertSame($photos[1]['id'],$plan->fresh()->property_photos[0]['id']);
  $this->put(route('admin.property.update',$plan),['remove'=>array_column($photos,'id')])->assertSessionHasNoErrors();
  Storage::disk('local')->assertMissing($photos[0]['path']);
  $this->assertFalse($plan->fresh()->hasPropertyDetails());
  $this->actingAs($account,'client')->get(route('portal.dashboard'))->assertOk()->assertDontSee('My Property');
 }
 public function test_validation_notes_only_and_admin_guard(): void {
  [$admin,$client,$plan]=$this->records('PROPERTYVALID');
  $this->actingAs($admin,'web')->put(route('admin.property.update',$plan),['property_latitude'=>95])->assertSessionHasErrors(['property_latitude','property_longitude']);
  $this->put(route('admin.property.update',$plan),['property_notes'=>'Directions only'])->assertSessionHasNoErrors();
  $this->actingAs($this->account($client),'client')->get(route('portal.dashboard'))->assertOk()->assertSee('Directions only')->assertDontSee('maps.google.com/maps',false);
  auth('web')->logout();
  $this->put(route('admin.property.update',$plan),['property_notes'=>'Unauthorized'])->assertRedirect(route('admin.login'));
  $this->assertSame('Directions only',$plan->fresh()->property_notes);
 }

 public function test_directional_coordinates_and_pasted_pairs(): void {
  [$admin,$client,$plan]=$this->records('COORDS');
  $this->actingAs($admin,'web');
  foreach ([
   ['property_coordinates'=>"35.674744\u{00B0} N 114.156267\u{00B0} W"],
   ['property_latitude'=>"35.674744\u{00B0} N",'property_longitude'=>"114.156267\u{00B0} W"],
   ['property_coordinates'=>'35.674744, -114.156267'],
  ] as $data) {
   $this->put(route('admin.property.update',$plan),$data)->assertSessionHasNoErrors();
   $this->assertEquals(35.674744,(float)$plan->fresh()->property_latitude);
   $this->assertEquals(-114.156267,(float)$plan->fresh()->property_longitude);
  }
  $this->put(route('admin.property.update',$plan),['property_latitude'=>'35 S','property_longitude'=>'114 E'])->assertSessionHasNoErrors();
  $this->assertEquals(-35,(float)$plan->fresh()->property_latitude);
  $this->assertEquals(114,(float)$plan->fresh()->property_longitude);
  $this->put(route('admin.property.update',$plan),['property_coordinates'=>'Bad pin'])->assertSessionHasErrors('property_coordinates');
  $this->put(route('admin.property.update',$plan),['property_coordinates'=>'95 N 114 W'])->assertSessionHasErrors('property_latitude');
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
