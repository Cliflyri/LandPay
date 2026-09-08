<?php
namespace Tests\Feature\Admin;
use App\Models\{Client,Invoice,PaymentPlan,PaymentPlanBillingTerm,PaymentPlanClient,PropertyTaxBatch,User};
use App\Services\FinancialBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class PropertyTaxBatchTest extends TestCase
{
 use RefreshDatabase;
 public function test_admin_saves_reviews_and_later_issues_a_property_tax_batch_even_without_email():void
 {
  [$user,,$plan]=$this->plan('123-45-678',null);
  $this->actingAs($user)->get(route('admin.actions.index'))->assertOk()->assertSee('Property tax invoices')->assertSee('href='.route('admin.actions.index'),false);
  $this->actingAs($user)->post(route('admin.property-tax-batches.store'),['tax_year'=>2026,'issue_date'=>'2026-09-10','due_date'=>'2026-10-15','fallback_description'=>'2026 Property Tax','source_text'=>'property APN,Amount,description'.PHP_EOL.'12345678,84.27,'.PHP_EOL,'email_clients'=>'1'])->assertRedirect();
  $batch=PropertyTaxBatch::query()->sole();
  $this->assertSame('draft',$batch->status);
  $this->assertDatabaseCount('invoices',0);
  $this->get(route('admin.property-tax-batches.show',$batch))->assertOk()->assertSee('No invoices have been created')->assertSee('2026 Property Tax');
  $this->post(route('admin.property-tax-batches.issue',$batch))->assertRedirect();
  $invoice=Invoice::query()->sole();
  $this->assertSame('2026-09-10',$invoice->issue_date->toDateString());
  $this->assertSame('2026-10-15',$invoice->due_date->toDateString());
  $this->assertSame('property_tax',$invoice->generation_source);
  $this->assertSame(2026,$invoice->property_tax_year);
  $this->assertSame(8427,app(FinancialBalanceService::class)->invoiceBalance($invoice));
  $this->assertDatabaseHas('invoice_items',['invoice_id'=>$invoice->id,'item_type'=>'property_tax','description'=>'2026 Property Tax','amount'=>8427]);
  $this->assertDatabaseHas('property_tax_batch_rows',['payment_plan_id'=>$plan->id,'invoice_id'=>$invoice->id,'email_status'=>'ineligible']);
 }
 public function test_collision_is_flagged_and_inactive_plan_is_skipped():void
 {
  [$user]=$this->plan('AA-11','one@example.com');
  $this->plan('AA11','two@example.com',$user);
  [,,$closed]=$this->plan('ZZ-99','closed@example.com',$user,'closed');
  $this->actingAs($user)->post(route('admin.property-tax-batches.store'),['tax_year'=>2026,'issue_date'=>'2026-09-10','due_date'=>'2026-10-15','fallback_description'=>'2026 Property Tax','source_text'=>'AA-11,10.00'.PHP_EOL.'ZZ99,20.00','email_clients'=>'0']);
  $batch=PropertyTaxBatch::query()->sole();
  $this->assertDatabaseHas('property_tax_batch_rows',['property_tax_batch_id'=>$batch->id,'normalized_apn'=>'AA11','match_status'=>'ambiguous']);
  $this->assertDatabaseHas('property_tax_batch_rows',['property_tax_batch_id'=>$batch->id,'payment_plan_id'=>$closed->id,'match_status'=>'inactive']);
  $this->post(route('admin.property-tax-batches.issue',$batch));
  $this->assertDatabaseCount('invoices',0);
 }
 public function test_duplicate_batch_is_not_issued_unless_admin_explicitly_overrides_it():void
 {
  [$user,,$plan]=$this->plan('DUP-100','client@example.com');
  $payload=['tax_year'=>2026,'label'=>'Annual','issue_date'=>'2026-09-10','due_date'=>'2026-10-15','fallback_description'=>'2026 Property Tax','source_text'=>'DUP-100,10.00','email_clients'=>'0'];
  $this->actingAs($user)->post(route('admin.property-tax-batches.store'),$payload);
  $first=PropertyTaxBatch::orderByDesc('id')->first();$this->post(route('admin.property-tax-batches.issue',$first))->assertSessionHasNoErrors();
  $this->assertNull($first->rows()->first()->note);
  $this->assertDatabaseCount('invoices',1);
  $this->post(route('admin.property-tax-batches.store'),$payload);
  $second=PropertyTaxBatch::orderByDesc('id')->first();$this->post(route('admin.property-tax-batches.issue',$second));
  $this->assertSame('not_issued',$second->fresh()->status);
  $this->assertSame('not_created',$second->rows()->first()->issuance_status);
  $this->assertDatabaseCount('invoices',1);
  $this->post(route('admin.property-tax-batches.store'),$payload);
  $third=PropertyTaxBatch::orderByDesc('id')->first();$row=$third->rows()->first();
  $this->post(route('admin.property-tax-batches.issue',$third),['force_duplicates'=>[$row->id],'duplicate_acknowledgment'=>'1']);
  $this->assertSame('issued',$third->fresh()->status);
  $this->assertTrue((bool)$row->fresh()->duplicate_override);
  $this->assertDatabaseCount('invoices',2);
  $this->assertDatabaseHas('invoices',['payment_plan_id'=>$plan->id,'property_tax_year'=>2026]);
 }
 private function plan(string $number,?string $email,?User $user=null,string $status='active'):array
 {
  $user??=User::factory()->create();
  $plan=PaymentPlan::create(['plan_number'=>$number,'apn'=>$number,'title'=>'Tax parcel','purchase_price'=>100000,'documentation_fee_standard'=>0,'documentation_fee_waived'=>0,'original_purchase_balance'=>100000,'customary_monthly_payment'=>10000,'monthly_service_fee'=>0,'monthly_due_day'=>1,'plan_start_date'=>'2026-01-01','status'=>$status,'created_by_user_id'=>$user->id,'updated_by_user_id'=>$user->id]);
  $client=Client::create(['client_type'=>'individual','first_name'=>'Tax','last_name'=>'Client','email'=>$email,'country_code'=>'US','created_by_user_id'=>$user->id,'updated_by_user_id'=>$user->id]);
  PaymentPlanClient::create(['payment_plan_id'=>$plan->id,'client_id'=>$client->id,'role'=>'primary','responsibility'=>'joint','receives_invoices'=>true,'effective_from'=>'2026-01-01','created_by_user_id'=>$user->id]);
  PaymentPlanBillingTerm::create(['payment_plan_id'=>$plan->id,'frequency'=>'monthly','invoice_day'=>1,'due_days_after_issue'=>10,'grace_days'=>0,'scheduled_payment_amount'=>10000,'monthly_service_fee'=>0,'stage_one_enabled'=>false,'stage_two_enabled'=>false,'default_eligibility_days'=>60,'effective_from'=>'2026-01-01','created_by_user_id'=>$user->id]);
  return [$user,$client,$plan];
 }
}
