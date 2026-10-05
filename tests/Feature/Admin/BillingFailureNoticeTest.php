<?php
namespace Tests\Feature\Admin;
use App\Models\{AdminNotice,Client,Invoice,PaymentPlan,PaymentPlanClient,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class BillingFailureNoticeTest extends TestCase {
 use RefreshDatabase;
 public function test_new_and_legacy_failures_link_client_plan_and_invoice(): void {
  [$admin,$client,$plan]=$this->records('FAIL');
  $invoice=Invoice::create(['payment_plan_id'=>$plan->id,'invoice_number'=>'INV-FAIL','issue_date'=>today(),'due_date'=>today(),'status'=>'issued','created_by_user_id'=>$admin->id]);
  $method=new \ReflectionMethod(\App\Services\AutomaticInvoiceService::class,'notice');
  $method->invoke(app(\App\Services\AutomaticInvoiceService::class),$plan,$invoice,'Automatic invoice email failed',new \RuntimeException('No valid invoice-recipient email is configured for this payment plan.'));
  $notice=AdminNotice::where('type','billing_automation_failure')->sole();
  $this->assertEquals($plan->id,$notice->payment_plan_id);
  $this->assertEquals($invoice->id,$notice->invoice_id);
  $this->assertEquals($client->id,$notice->client_id);
  foreach ([false,true] as $legacy) {
   if ($legacy) {$notice->update(['payment_plan_id'=>null,'invoice_id'=>null,'client_id'=>null]);$notice=$notice->fresh();}
   $html=view('admin.partials.dashboard-notices',['notices'=>collect([$notice])])->render();
   $email=(new \App\Mail\AdminNoticeMail($notice->title,$notice->message,null,$notice))->render();
   foreach ([$html,$email] as $content) {
    $this->assertStringContainsString(route('admin.plans.show',$plan),$content);
    $this->assertStringContainsString(route('admin.invoices.show',$invoice),$content);
    $this->assertStringContainsString(route('admin.clients.show',$client),$content);
    $this->assertStringContainsString('Portal FAIL',$content);
    $this->assertStringContainsString('No valid invoice-recipient email',$content);
   }
  }
 }
 public function test_unresolvable_legacy_notice_keeps_its_message(): void {
  $notice=new AdminNotice(['type'=>'billing_automation_failure','message'=>'Plan missing: Original failure.']);
  $this->assertStringContainsString('Plan missing: Original failure.',view('shared.billing-failure-notice',compact('notice'))->render());
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
