<?php
namespace Tests\Feature\Payments;
use App\Models\AppSetting;
use App\Models\Client;
use App\Models\ClientPaymentIntent;
use App\Models\PaymentPlan;
use App\Models\PaymentPlanClient;
use App\Models\PortalAccount;
use App\Models\User;
use App\Services\ContractOpeningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class PaymentOptionsTest extends TestCase {
 use RefreshDatabase;
 public function test_admin_configures_methods_and_client_announces_zelle_payment(): void {
  [$admin,$client,$plan,$account]=$this->records();
  $this->actingAs($admin)->put(route('admin.payment-methods.general.update'),['enabled'=>'1','allow_custom_amount'=>'1','card_provider'=>'disabled','intent_expiry_days'=>14])->assertSessionHas('success');
  $this->put(route('admin.payment-methods.method.update','zelle'),['enabled'=>'1','name'=>'Zelle','instructions'=>'Send to payments@example.com','recipient'=>'payments@example.com','link'=>'','button'=>'I sent this payment'])->assertSessionHas('success');
  $this->actingAs($account,'client')->get(route('portal.make-payment.create'))->assertOk()->assertSee('Recommended')->assertSee('payments@example.com');
  $this->get(route('portal.make-payment.create',['plan'=>$plan->id,'amount'=>'42.35']))->assertOk()->assertSee('value="42.35"',false);
  $data=['payment_plan_id'=>$plan->id,'amount'=>'100.00','method'=>'zelle','overpayment_disposition'=>'principal'];
  $response=$this->post(route('portal.make-payment.store'),$data);
  $intent=ClientPaymentIntent::query()->sole();$response->assertRedirect(route('portal.make-payment.create',['plan'=>$plan->id]));
  $this->assertSame('announced',$intent->status);$this->assertDatabaseHas('admin_notices',['client_payment_intent_id'=>$intent->id,'dismissed_at'=>null]);
  $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Receive payment')->assertSee('Client overpayment instruction:')->assertSee('Apply extra to principal.');
  $this->get(route('admin.payment-intents.receive',$intent))->assertOk()->assertSee('Allocation preview')->assertSee('Confirm and post payment')->assertSee('Client overpayment instruction:')->assertSee('Apply extra to principal.')->assertSee('100.00');
  $post=['received_date'=>now()->toDateString(),'amount'=>'100.00','payment_type'=>'regular','payment_method'=>'zelle','payer_client_id'=>$client->id,'external_reference'=>'ZELLE-1','overpayment_disposition'=>'principal','idempotency_token'=>'55555555-5555-4555-8555-555555555555','client_payment_intent_id'=>$intent->id];
  $this->post(route('admin.plans.payments.store',$plan),$post)->assertSessionHasNoErrors();
  $this->assertSame('received',$intent->fresh()->status);$this->assertNotNull($intent->fresh()->payment_id);$this->assertNotNull(\App\Models\AdminNotice::query()->sole()->dismissed_at);
 }
 public function test_plan_balances_are_exposed_and_offline_notice_can_be_cancelled_with_a_note(): void {
  [$admin,$client,$plan,$account]=$this->records();
  $second=PaymentPlan::create(['plan_number'=>'LP-PAY-2','title'=>'Second payment plan','original_purchase_balance'=>1,'customary_monthly_payment'=>7500,'monthly_due_day'=>1,'first_due_date'=>'2026-08-06','plan_start_date'=>'2026-08-01','status'=>'draft','created_by_user_id'=>$admin->id,'updated_by_user_id'=>$admin->id]);
  app(ContractOpeningService::class)->open($second,$admin,100000,0,0,'2026-08-01');$second->update(['status'=>'active','activated_at'=>now()]);
  PaymentPlanClient::create(['payment_plan_id'=>$second->id,'client_id'=>$client->id,'role'=>'primary','responsibility'=>'joint','receives_invoices'=>true,'effective_from'=>'2026-08-01','contact_risk_acknowledged_at'=>now(),'contact_risk_acknowledgment_method'=>'admin_contract_acceptance','created_by_user_id'=>$admin->id]);
  app(\App\Services\FirstPaymentInvoiceService::class)->issue($plan,$admin,12345,'2026-08-01','2026-08-06');
  app(\App\Services\FirstPaymentInvoiceService::class)->issue($second,$admin,6789,'2026-08-01','2026-08-06');
  AppSetting::putMany(['payment_zelle_enabled'=>'1','payment_zelle_name'=>'Zelle','payment_zelle_link'=>'https://example.com/pay','payment_zelle_image_url'=>'https://example.com/zelle.png','payment_cash_app_enabled'=>'1','payment_cash_app_name'=>'Cash App','client_payments_custom_amount'=>'1']);

  $page=$this->actingAs($account,'client')->get(route('portal.make-payment.create'));
  $page->assertOk()->assertSee('data-open-balance="123.45"',false)->assertSee('data-open-balance="67.89"',false)->assertSee('src="https://example.com/zelle.png"',false)->assertSee('plan.addEventListener',false);
  $data=['payment_plan_id'=>$plan->id,'amount'=>'50.00','method'=>'zelle','overpayment_disposition'=>'principal','client_note'=>'Payment will arrive under the name Billy Jones.'];
  $this->postJson(route('portal.make-payment.store'),$data)->assertOk()->assertJsonStructure(['intent_id','message','cancel_url']);
  $this->postJson(route('portal.make-payment.store'),array_merge($data,['amount'=>'51.00']))->assertOk();
  $this->assertDatabaseCount('client_payment_intents',1);
  $intent=ClientPaymentIntent::query()->sole();
  $this->assertSame('announced',$intent->status);
  $this->assertSame($data['client_note'],$intent->client_note);
  $this->get(route('portal.make-payment.create'))->assertOk()->assertSee('Admin notified of $50.00 Zelle payment.')->assertDontSee('plan.disabled=value',false);
  $simultaneousData=['payment_plan_id'=>$second->id,'amount'=>'25.00','method'=>'cash_app','client_note'=>'Simultaneous second plan'];
  $this->postJson(route('portal.make-payment.store'),$simultaneousData)->assertOk();
  $simultaneousIntent=ClientPaymentIntent::query()->where('payment_plan_id',$second->id)->where('status','announced')->sole();
  $this->assertDatabaseCount('client_payment_intents',2);
  $this->get(route('portal.make-payment.create',['plan'=>$second->id]))->assertOk()->assertSee('Admin notified of $25.00 Cash App payment.');
  $this->deleteJson(route('portal.make-payment.cancel',$simultaneousIntent))->assertOk();
  $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('intends to pay $50.00 by Zelle')->assertSee('Client note: Payment will arrive under the name Billy Jones.');
  $this->get(route('admin.payment-intents.receive',$intent))->assertOk()->assertSee(route('admin.invoices.show',$plan->invoices()->first()),false)->assertSee('Client note:')->assertSee('Billy Jones');

  $response=$this->actingAs($account,'client')->deleteJson(route('portal.make-payment.cancel',$intent));
  $response->assertOk()->assertJson(['cancelled'=>true]);
  $this->assertSame('cancelled',$intent->fresh()->status);
  $this->assertNotNull($intent->fresh()->cancelled_at);
  $this->assertNotNull(\App\Models\AdminNotice::query()->where('client_payment_intent_id',$intent->id)->sole()->dismissed_at);
  $this->assertDatabaseCount('payments',0);
  $this->actingAs($admin)->get(route('admin.payment-intents.receive',$intent))->assertStatus(409);
  $stalePost=['received_date'=>now()->toDateString(),'amount'=>'50.00','payment_type'=>'regular','payment_method'=>'zelle','payer_client_id'=>$client->id,'overpayment_disposition'=>'principal','idempotency_token'=>'77777777-7777-4777-8777-777777777777','client_payment_intent_id'=>$intent->id];
  $this->post(route('admin.plans.payments.store',$plan),$stalePost)->assertStatus(409);
  $this->assertDatabaseCount('payments',0);
  $this->assertNull($intent->fresh()->payment_id);
  $this->get(route('admin.dashboard'))->assertOk()->assertDontSee('Billy Jones');

  $secondData=['payment_plan_id'=>$second->id,'amount'=>'25.00','method'=>'zelle','client_note'=>'Second plan'];
  $this->actingAs($account,'client')->postJson(route('portal.make-payment.store'),$secondData)->assertOk();
  $secondIntent=ClientPaymentIntent::query()->where('payment_plan_id',$second->id)->where('status','announced')->sole();
  $secondNotice=\App\Models\AdminNotice::query()->where('client_payment_intent_id',$secondIntent->id)->sole();
  $this->actingAs($account,'client')->get(route('portal.dashboard'))->assertOk()->assertSee('Payment notices awaiting admin confirmation')->assertSee('$25.00');
  $this->actingAs($admin)->post(route('admin.notices.dismiss',$secondNotice))->assertSessionHas('success');
  $this->assertSame('cancelled',$secondIntent->fresh()->status);
 }

 public function test_signed_stripe_webhook_posts_once(): void {
  [$admin,$client,$plan,$account]=$this->records();
  AppSetting::putEncrypted('stripe_webhook_secret','whsec_test');
  $intent=ClientPaymentIntent::create(['payment_plan_id'=>$plan->id,'client_id'=>$client->id,'portal_account_id'=>$account->id,'method'=>'card','amount'=>10000,'payment_type'=>'regular','overpayment_disposition'=>'principal','status'=>'checkout_pending','provider'=>'stripe','provider_checkout_id'=>'cs_test_1']);
  $payload=json_encode(['type'=>'checkout.session.completed','data'=>['object'=>['id'=>'cs_test_1','payment_status'=>'paid','payment_intent'=>'pi_1','amount_total'=>10000,'currency'=>'usd']]],JSON_THROW_ON_ERROR);
  $timestamp=time();$signature=hash_hmac('sha256',$timestamp.'.'.$payload,'whsec_test');
  $this->call('POST',route('webhooks.provider','stripe'),[],[],[],['CONTENT_TYPE'=>'application/json','HTTP_STRIPE_SIGNATURE'=>"t={$timestamp},v1={$signature}"],$payload)->assertOk();
  $this->assertSame('received',$intent->fresh()->status);$this->assertDatabaseCount('payments',1);
  $this->call('POST',route('webhooks.provider','stripe'),[],[],[],['CONTENT_TYPE'=>'application/json','HTTP_STRIPE_SIGNATURE'=>"t={$timestamp},v1={$signature}"],$payload)->assertOk();
  $this->assertDatabaseCount('payments',1);$this->assertDatabaseHas('admin_notices',['type'=>'online_payment_received','message'=>'Paying Client paid $100.00 by Stripe on '.now()->format('M j, Y').'. Payment posted successfully.']);
  $payment=$intent->fresh()->payment;
  $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Paying Client')->assertSee('$100.00')->assertSee(now()->format('M j, Y'))->assertSee(route('admin.clients.show',$client),false)->assertSee(route('admin.payments.show',$payment),false);
 }
 private function records(): array {
  $admin=User::factory()->create(['status'=>'active']);$client=Client::create(['client_type'=>'individual','first_name'=>'Paying','last_name'=>'Client','email'=>'payer@example.com','country_code'=>'US','created_by_user_id'=>$admin->id,'updated_by_user_id'=>$admin->id]);
  $plan=PaymentPlan::create(['plan_number'=>'LP-PAY','title'=>'Payment plan','original_purchase_balance'=>1,'customary_monthly_payment'=>10000,'monthly_due_day'=>1,'first_due_date'=>'2026-08-06','plan_start_date'=>'2026-08-01','status'=>'draft','created_by_user_id'=>$admin->id,'updated_by_user_id'=>$admin->id]);
  app(ContractOpeningService::class)->open($plan,$admin,100000,0,0,'2026-08-01');$plan->update(['status'=>'active','activated_at'=>now()]);
  PaymentPlanClient::create(['payment_plan_id'=>$plan->id,'client_id'=>$client->id,'role'=>'primary','responsibility'=>'joint','receives_invoices'=>true,'effective_from'=>'2026-08-01','contact_risk_acknowledged_at'=>now(),'contact_risk_acknowledgment_method'=>'admin_contract_acceptance','created_by_user_id'=>$admin->id]);
  $account=PortalAccount::create(['client_id'=>$client->id,'email'=>$client->email,'password'=>'password','enabled'=>true]);return[$admin,$client,$plan,$account];
 }
}