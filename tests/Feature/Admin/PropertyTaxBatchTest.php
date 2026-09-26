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
 public function test_numbered_suffixes_match_independently_without_changing_plan_numbers():void
 {
  [$user,,$first]=$this->plan('333-18-162 (2)',null);
  [,,$second]=$this->plan('333-18-440 (1)',null,$user);
  $rows=app(\App\Services\PropertyTaxBatchService::class)->rows("333-18-162,10.00\n333-18-440,20.00",'Tax',2026);
  $this->assertSame(['matched','matched'],array_column($rows,'match_status'));
  $this->assertSame([$first->id,$second->id],array_column($rows,'payment_plan_id'));
  $this->assertSame('333-18-162 (2)',$first->fresh()->plan_number);
 }
 public function test_admin_selects_only_a_candidate_and_can_change_it_before_issuance():void
 {
  [$user,,$first]=$this->plan('333-18-162 (1)',null);
  [,,$second]=$this->plan('333-18-162 (2)',null,$user);
  [,,$other]=$this->plan('OTHER',null,$user);
  $payload=['tax_year'=>2026,'issue_date'=>'2026-09-10','due_date'=>'2026-10-15','fallback_description'=>'Tax','source_text'=>'333-18-162,10.00','email_clients'=>'0'];
  $this->actingAs($user)->post(route('admin.property-tax-batches.store'),$payload)->assertSessionHasNoErrors();
  $batch=PropertyTaxBatch::query()->sole();$row=$batch->rows()->first();
  $this->assertSame('ambiguous',$row->match_status);
  $this->get(route('admin.property-tax-batches.show',$batch))->assertOk()->assertSee('multiple possible plans')->assertSee('Match with upload')->assertSee('333-18-162 (1)')->assertSee('333-18-162 (2)');
  $url=route('admin.property-tax-batches.match',[$batch,$row]);
  $this->post($url,['payment_plan_id'=>$other->id])->assertSessionHasErrors('payment_plan_id');
  $this->assertNull($row->fresh()->payment_plan_id);
  $this->post($url,['payment_plan_id'=>$first->id])->assertSessionHasNoErrors();
  $this->assertSame($first->id,$row->fresh()->payment_plan_id);
  $this->assertSame('matched',$row->fresh()->match_status);
  $this->assertDatabaseCount('invoices',0);
  $this->get(route('admin.property-tax-batches.show',$batch))->assertOk()->assertSee('Manually selected')->assertViewHas('unmatchedPlans',fn($plans)=>!$plans->contains('id',$first->id)&&$plans->contains('id',$second->id));
  $existing=Invoice::create(['payment_plan_id'=>$second->id,'invoice_number'=>'PT-EXISTING','issue_date'=>'2026-09-10','due_date'=>'2026-10-15','status'=>'issued','property_tax_year'=>2026,'generation_source'=>'property_tax','created_by_user_id'=>$user->id]);
  $this->post($url,['payment_plan_id'=>$second->id])->assertSessionHasNoErrors();
  $this->assertSame($existing->id,$row->fresh()->existing_invoice_id);
  $this->get(route('admin.property-tax-batches.show',$batch))->assertOk()->assertSee('PT-EXISTING')->assertSee('Manually selected');
  $this->assertDatabaseHas('audit_logs',['event'=>'property_tax_batch.match_selected','actor_user_id'=>$user->id,'auditable_id'=>$row->id]);
  $this->post(route('admin.property-tax-batches.issue',$batch))->assertSessionHasNoErrors();
  $this->assertDatabaseCount('invoices',1);
  $this->post($url,['payment_plan_id'=>$first->id])->assertStatus(409);
 }
 public function test_county_prioritizes_unmatched_plans_without_restricting_matching():void
 {
  [$user,,$matched]=$this->plan('MATCH',null);
  [,,$missing]=$this->plan('A-MISSING',null,$user);
  [,,$other]=$this->plan('B-OTHER',null,$user);$other->update(['property_county'=>'Yavapai']);
  [,,$same]=$this->plan('Z-SAME',null,$user);$same->update(['property_county'=>'Mohave']);
  $this->actingAs($user)->get(route('admin.property-tax-batches.create'))->assertOk()->assertSee('Mohave')->assertSee('batch-counties');
  $payload=['tax_year'=>2026,'issue_date'=>'2026-09-10','due_date'=>'2026-10-15','source_text'=>'MATCH,10.00','email_clients'=>'0','property_county'=>' mohave '];
  $this->post(route('admin.property-tax-batches.store'),$payload)->assertSessionHasNoErrors();
  $batch=PropertyTaxBatch::query()->sole();
  $this->assertSame('Mohave',$batch->property_county);
  $this->assertSame($matched->id,$batch->rows()->first()->payment_plan_id);
  $this->get(route('admin.property-tax-batches.show',$batch))->assertOk()->assertSee('Same county')->assertSee('County missing')->assertViewHas('unmatchedPlans',fn($plans)=>$plans->pluck('id')->all()===[$same->id,$missing->id,$other->id]);
  $this->get(route('admin.property-tax-batches.index'))->assertOk()->assertSee('County: Mohave');
  $this->get(route('admin.property-tax-batches.edit',$batch))->assertOk()->assertSee('Mohave');
  $payload['property_county']='';
  $this->put(route('admin.property-tax-batches.update',$batch),$payload)->assertSessionHasNoErrors();
  $this->assertNull($batch->fresh()->property_county);
  $this->get(route('admin.property-tax-batches.show',$batch))->assertOk()->assertDontSee('Same county')->assertViewHas('unmatchedPlans',fn($plans)=>$plans->pluck('id')->all()===[$missing->id,$other->id,$same->id]);
  $payload['property_county']='New County';
  $this->put(route('admin.property-tax-batches.update',$batch),$payload)->assertSessionHasNoErrors();
  $this->assertSame('New County',$batch->fresh()->property_county);
 }
 public function test_zero_rows_require_confirmation_and_never_create_invoices_or_email():void
 {
  [$user,,$paid]=$this->plan('POSITIVE',null);
  [,,$zero]=$this->plan('ZERO',null,$user);
  $this->plan('UNCHECKED',null,$user);
  $this->plan('EXCLUDED',null,$user);
  $this->plan('DRAFTZERO',null,$user,'draft');
  $this->plan('AMB (1)',null,$user);$this->plan('AMB (2)',null,$user);
  $this->plan('CLOSED',null,$user,'closed');
  $payload=['tax_year'=>2026,'issue_date'=>'2026-09-10','due_date'=>'2026-10-15','fallback_description'=>'Tax','source_text'=>"POSITIVE,10.00\nZERO,0.00\nUNCHECKED,0\nEXCLUDED,0\nDRAFTZERO,0\nAMB,0\nUNKNOWN,12.00\nBAD,\nZERO,0\nCLOSED,5",'email_clients'=>'1'];
  $this->actingAs($user)->post(route('admin.property-tax-batches.store'),$payload)->assertSessionHasNoErrors();
  $batch=PropertyTaxBatch::query()->sole();$rows=$batch->rows()->get()->keyBy('original_apn');
  $zeroRow=$batch->rows()->where('payment_plan_id',$zero->id)->first();
  $this->assertSame('matched',$zeroRow->match_status);
  $this->assertSame('invalid',$rows['BAD']->match_status);
  $this->assertSame('duplicate',$rows['ZERO']->match_status);
  $this->get(route('admin.property-tax-batches.show',$batch))->assertOk()->assertSee('Confirm no tax due')->assertSeeInOrder(['$0.00 — Review required','POSITIVE','Ambiguous — multiple possible plans','Create invoices','Clients / plans with no match','Unmatched / invalid batch lines','Skipped inactive plans']);
  $this->post(route('admin.property-tax-batches.issue',$batch),['confirm_zero_rows'=>[$zeroRow->id,$rows['EXCLUDED']->id,$rows['DRAFTZERO']->id,$rows['AMB']->id],'exclude_rows'=>[$rows['EXCLUDED']->id]])->assertSessionHasNoErrors();
  $this->assertDatabaseCount('invoices',1);
  $this->assertDatabaseHas('invoices',['payment_plan_id'=>$paid->id]);
  $this->assertDatabaseHas('property_tax_batch_rows',['id'=>$zeroRow->id,'issuance_status'=>'no_tax_due','invoice_id'=>null,'email_status'=>'not_requested']);
  $this->assertSame('zero_unconfirmed',$rows['UNCHECKED']->fresh()->issuance_status);
  $this->assertSame('excluded',$rows['EXCLUDED']->fresh()->issuance_status);
  $this->assertSame('no_tax_due',$rows['DRAFTZERO']->fresh()->issuance_status);
  $this->assertSame('not_created',$rows['AMB']->fresh()->issuance_status);
  $this->assertSame('partially_issued',$batch->fresh()->status);
  $this->get(route('admin.property-tax-batches.show',$batch))->assertOk()->assertSee('No tax due — confirmed')->assertSee('Zero amount unconfirmed');
  $this->assertDatabaseMissing('financial_transactions',['payment_plan_id'=>$zero->id]);
 }
 public function test_zero_only_batch_completes_without_invoice_and_blocks_stale_eligibility():void
 {
  [$user,,$zero]=$this->plan('ZERO',null);
  $payload=['tax_year'=>2026,'issue_date'=>'2026-09-10','due_date'=>'2026-10-15','source_text'=>'ZERO,0.00','email_clients'=>'1'];
  $this->actingAs($user)->post(route('admin.property-tax-batches.store'),$payload);
  $batch=PropertyTaxBatch::query()->sole();$row=$batch->rows()->first();
  $this->post(route('admin.property-tax-batches.issue',$batch),['confirm_zero_rows'=>[$row->id]])->assertSessionHasNoErrors();
  $this->assertSame('issued',$batch->fresh()->status);
  $this->assertSame('no_tax_due',$row->fresh()->issuance_status);
  $this->assertDatabaseCount('invoices',0);
  $this->post(route('admin.property-tax-batches.store'),$payload);
  $next=PropertyTaxBatch::latest('id')->first();$nextRow=$next->rows()->first();
  $zero->update(['status'=>'closed']);
  $this->post(route('admin.property-tax-batches.issue',$next),['confirm_zero_rows'=>[$nextRow->id]])->assertSessionHasNoErrors();
  $this->assertSame('zero_unconfirmed',$nextRow->fresh()->issuance_status);
  $this->assertDatabaseCount('invoices',0);
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
