<?php
namespace App\Http\Controllers\Portal;
use App\Http\Controllers\Controller;
use App\Models\AdminNotice;
use App\Models\ClientPaymentIntent;
use App\Models\PaymentPlan;
use App\Services\FinancialBalanceService;
use App\Services\PaymentMethodConfigurationService;
use App\Services\PaymentService;
use App\Services\HostedPaymentService;
use App\Services\SquareCardPaymentService;
use App\Services\SquareProcessingFee;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
class MakePaymentController extends Controller {
 public function __construct(private readonly PaymentMethodConfigurationService $methods,private readonly PaymentService $payments,private readonly FinancialBalanceService $balances,private readonly HostedPaymentService $hosted,private readonly SquareProcessingFee $squareFees,private readonly SquareCardPaymentService $squareCards){}
 public function create(Request $request): View{return $this->form($request);}
 public function preview(Request $request): View{$data=$this->validateInput($request);$plan=$this->plan($request,(int)$data['payment_plan_id']);$preview=$this->payments->preview($plan,Money::toCents($data['amount']),'regular',$data['overpayment_disposition']??null);return $this->form($request,$preview,$data);}
 public function store(Request $request) {
  $data=$this->validateInput($request);$account=$request->user('client');$plan=$this->plan($request,(int)$data['payment_plan_id']);$method=$this->methods->method($data['method']);abort_unless($method['enabled'],422);
  $this->payments->preview($plan,Money::toCents($data['amount']),'regular',$data['overpayment_disposition']??null);
  if($data['method']==='card'){
   $baseAmount=Money::toCents($data['amount']);$square=$this->squareFees->clientConfiguration();$landpay=$this->methods->general()['card_provider']==='square'&&$square['experience']==='landpay';
   if($landpay){$request->validate(['square_source_id'=>['required','string','max:255'],'square_card_type'=>['required',Rule::in(['CREDIT','DEBIT','PREPAID','UNKNOWN'])]]);$fee=$this->squareFees->calculate($baseAmount,$data['square_card_type']);}else{$fee=0;}
   $intent=ClientPaymentIntent::query()->create(['payment_plan_id'=>$plan->id,'client_id'=>$account->client_id,'portal_account_id'=>$account->id,'method'=>'card','amount'=>$baseAmount+$fee,'base_amount'=>$baseAmount,'processing_fee_amount'=>$fee,'card_type'=>$data['square_card_type']??null,'payment_type'=>'regular','overpayment_disposition'=>$data['overpayment_disposition']??null,'client_note'=>$data['client_note']??null,'status'=>'announced','provider'=>$landpay?'square':null,'expires_at'=>now()->addDays($this->methods->general()['intent_expiry_days'])]);
   if($landpay){$intent=$this->squareCards->pay($intent,$data['square_source_id']);return redirect()->route('portal.make-payment.show',$intent);}
   $intent=$this->hosted->create($intent);return redirect()->away($intent->checkout_url);
  }
  $intent=DB::transaction(function()use($account,$plan,$data,$method){
   PaymentPlan::query()->lockForUpdate()->findOrFail($plan->id);
   $created=ClientPaymentIntent::query()->create(['payment_plan_id'=>$plan->id,'client_id'=>$account->client_id,'portal_account_id'=>$account->id,'method'=>$data['method'],'amount'=>Money::toCents($data['amount']),'payment_type'=>'regular','overpayment_disposition'=>$data['overpayment_disposition']??null,'client_note'=>$data['client_note']??null,'status'=>'announced','expires_at'=>now()->addDays($this->methods->general()['intent_expiry_days'])]);
   AdminNotice::query()->create(['type'=>'client_payment_announced','client_id'=>$account->client_id,'client_payment_intent_id'=>$created->id,'title'=>'Payment intended','message'=>$account->displayName().' intends to pay '.Money::format($created->amount).' by '.$method['name'].' for plan '.$plan->plan_number.'.'.(filled($created->client_note)?' Client note: '.$created->client_note:'')]);
   return $created;
  },3);
  if($request->expectsJson())return response()->json(['intent_id'=>$intent->uuid,'message'=>'Admin notified of '.Money::format($intent->amount).' '.$this->methods->method($intent->method)['name'].' payment.','cancel_url'=>route('portal.make-payment.cancel',$intent)]);
  return redirect()->route('portal.make-payment.create',['plan'=>$intent->payment_plan_id])->with('success','Administrator notified of your intended payment.');
 }
 public function show(Request $request,ClientPaymentIntent $intent): View{abort_unless($intent->client_id===$request->user('client')->client_id,404);$intent->load('paymentPlan');return view('portal.make-payment.confirmation',['intent'=>$intent,'method'=>$this->methods->method($intent->method)]);}
 public function cancel(Request $request,ClientPaymentIntent $intent) {
  abort_unless($intent->client_id===$request->user('client')->client_id,404);
  DB::transaction(function()use($intent):void{
   $locked=ClientPaymentIntent::query()->lockForUpdate()->findOrFail($intent->id);
   abort_unless($locked->status==='announced',409);
   $locked->update(['status'=>'cancelled','cancelled_at'=>now()]);
   AdminNotice::query()->where('client_payment_intent_id',$locked->id)->whereNull('dismissed_at')->update(['dismissed_at'=>now()]);
  });
  if($request->expectsJson())return response()->json(['cancelled'=>true]);
  return redirect()->route('portal.make-payment.create',['plan'=>$intent->payment_plan_id,'amount'=>number_format($intent->amount/100,2,'.',''),'method'=>$intent->method])->with('success','Payment notification cancelled.');
 }
 private function form(Request $request,?array $preview=null,array $input=[]): View {
  $oldInput=$request->old();
  if(is_array($oldInput))$input=array_replace($oldInput,$input);
  $account=$request->user('client');
  $plans=PaymentPlan::query()->whereIn('id',$account->activePlanIds())->whereIn('status',['active','paused'])->with('invoices')->get();
  abort_if($plans->isEmpty(),403);
  $methods=$this->methods->enabled();
  $configuredMethods=collect($methods)->keyBy('key');
  $pendingNotifications=ClientPaymentIntent::query()->where('client_id',$account->client_id)->whereIn('payment_plan_id',$plans->pluck('id'))->where('status','announced')->where('method','!=','card')->whereHas('adminNotice',fn($notice)=>$notice->whereNull('dismissed_at'))->latest('id')->get()->map(function(ClientPaymentIntent $intent)use($configuredMethods){$intent->setAttribute('method_name',$configuredMethods->get($intent->method)['name']??str($intent->method)->replace('_',' ')->title());return $intent;});
  $requestedPlan=(int)($input['payment_plan_id']??$request->integer('plan'));
  $selected=$plans->firstWhere('id',$requestedPlan)??$plans->first();
  $balances=$plans->mapWithKeys(fn(PaymentPlan $plan)=>[$plan->id=>(int)$plan->invoices->sum(fn($invoice)=>max(0,$this->balances->invoiceBalance($invoice)))]);
  $requestedAmount=$request->query('amount');
  $amount=is_string($requestedAmount)&&preg_match('/^(?:0|[1-9]\d*)(?:\.\d{1,2})?$/',$requestedAmount)&&Money::toCents($requestedAmount)>0
   ?number_format(Money::toCents($requestedAmount)/100,2,'.','')
   :number_format($balances[$selected->id]/100,2,'.','');
  $requestedMethod=$request->query('method');
  $methodKeys=collect($methods)->pluck('key');
  $input+=['payment_plan_id'=>$selected->id,'amount'=>$amount,'method'=>$methodKeys->contains($requestedMethod)?$requestedMethod:($methods[0]['key']??null)];
  $activeStates=[];
  return view('portal.make-payment.simple',['plans'=>$plans,'planBalances'=>$balances,'selectedPlan'=>$selected,'methods'=>$methods,'general'=>$this->methods->general(),'square'=>$this->squareFees->clientConfiguration(),'activeStates'=>$activeStates,'pendingNotifications'=>$pendingNotifications,'input'=>$input]);
 }
 private function plan(Request $request,int $id): PaymentPlan{abort_unless(in_array($id,$request->user('client')->activePlanIds(),true),404);return PaymentPlan::findOrFail($id);}
 private function validateInput(Request $request): array{return $request->validate(['payment_plan_id'=>['required','integer'],'amount'=>['required','decimal:0,2','gt:0'],'method'=>['required',Rule::in(PaymentMethodConfigurationService::METHODS)],'overpayment_disposition'=>['nullable',Rule::in(['principal','next_invoice_credit'])],'client_note'=>['nullable','string','max:1000'],'square_source_id'=>['nullable','string','max:255'],'square_card_type'=>['nullable','string','max:20']]);}
}
