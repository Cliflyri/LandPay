<?php
namespace Tests\Feature\Admin;
use App\Models\{Client,PaymentPlan,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class TestDataReportingTest extends TestCase{
 use RefreshDatabase;
 public function test_client_exclusion_is_retroactive_but_portal_and_sms_reports_remain_operational():void{
  $user=User::factory()->create();
  $this->actingAs($user)->post(route('admin.clients.store'),['client_type'=>'individual','first_name'=>'Retro','last_name'=>'Tester','email'=>'retro@example.test','country_code'=>'US']);
  $client=Client::query()->sole();
  $plan=$this->plan($user,$client,'RETRO-PLAN');
  $this->get(route('admin.reports.show',['report'=>'contracts']))->assertSee('RETRO-PLAN');
  $this->post(route('admin.settings.test-data.clients.add'),['client_id'=>$client->id])->assertSessionHasNoErrors();
  $this->get(route('admin.reports.show',['report'=>'contracts']))->assertDontSee('RETRO-PLAN');
  $this->get(route('admin.reports.export',['report'=>'contracts']))->assertDontSee('RETRO-PLAN');
  $this->get(route('admin.reports.show',['report'=>'client-portals','portal_status'=>'none']))->assertSee('Retro Tester');
  $this->get(route('admin.reports.show',['report'=>'client-sms']))->assertSee('Retro Tester');
  $this->get(route('admin.dashboard'))->assertSee('RETRO-PLAN')->assertSee('Test client');
  $this->assertDatabaseHas('audit_logs',['event'=>'reporting.test_data_added','auditable_id'=>$client->id]);
  $this->delete(route('admin.settings.test-data.clients.remove',$client))->assertSessionHasNoErrors();
  $this->get(route('admin.reports.show',['report'=>'contracts']))->assertSee('RETRO-PLAN');
 }
 public function test_plan_can_be_excluded_independently():void{
  $user=User::factory()->create();$this->actingAs($user)->post(route('admin.clients.store'),['client_type'=>'individual','first_name'=>'Plan','last_name'=>'Tester','country_code'=>'US']);
  $client=Client::query()->sole();$plan=$this->plan($user,$client,'ONLY-THIS');
  $this->post(route('admin.settings.test-data.plans.add'),['payment_plan_id'=>$plan->id])->assertSessionHasNoErrors();
  $this->get(route('admin.settings.index',['section'=>'test-data']))->assertOk()->assertSee('ONLY-THIS');
  $this->get(route('admin.reports.show',['report'=>'contracts']))->assertDontSee('ONLY-THIS');
  $this->get(route('admin.dashboard'))->assertSee('Test plan');
 }
 private function plan(User $u,Client $c,string $number):PaymentPlan{
  $p=PaymentPlan::create(['plan_number'=>$number,'title'=>'Test Parcel','purchase_price'=>100000,'documentation_fee_standard'=>0,'documentation_fee_waived'=>0,'original_purchase_balance'=>100000,'customary_monthly_payment'=>10000,'monthly_service_fee'=>0,'monthly_due_day'=>1,'plan_start_date'=>'2026-08-01','status'=>'active','activated_at'=>now(),'created_by_user_id'=>$u->id,'updated_by_user_id'=>$u->id]);
  $p->memberships()->create(['client_id'=>$c->id,'role'=>'primary','responsibility'=>'joint','receives_invoices'=>true,'effective_from'=>'2026-08-01','created_by_user_id'=>$u->id]);return $p;
 }
}