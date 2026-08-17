<?php
namespace App\Http\Controllers\Portal;
use App\Http\Controllers\Controller;
use App\Models\AdminNotice;
use App\Models\ClientChangeRequest;
use App\Models\PaymentPlan;
use App\Services\CurrentPayoffService;
use App\Services\FinancialBalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
class AccountController extends Controller {
 public function __construct(private readonly CurrentPayoffService $payoffs, private readonly FinancialBalanceService $balances){}
 public function show(Request $request): View {$account=$request->user('client')->load('client');$plans=PaymentPlan::query()->whereIn('id',$account->activePlanIds())->get()->map(fn($plan)=>['plan'=>$plan,'current_payoff'=>$this->payoffs->amount($plan),'principal_paid'=>$this->balances->administratorPaidInValue($plan)]);$pending=ClientChangeRequest::query()->where('client_id',$account->client_id)->where('status','pending')->latest()->first();return view('portal.account.show',compact('account','plans','pending'));}
 public function edit(Request $request): View {$account=$request->user('client')->load('client');return view('portal.account.edit',compact('account'));}
 public function update(Request $request): RedirectResponse {
  $account=$request->user('client')->load('client');$data=$request->validate(['email'=>['required','email','max:254'],'primary_phone'=>['nullable','string','max:32'],'secondary_phone'=>['nullable','string','max:32'],'address_line_1'=>['nullable','string','max:150'],'address_line_2'=>['nullable','string','max:150'],'city'=>['nullable','string','max:100'],'state_region'=>['nullable','string','max:100'],'postal_code'=>['nullable','string','max:24'],'country_code'=>['required','string','size:2']]);
  $changes=[];foreach($data as $field=>$value){$value=is_string($value)?trim($value):$value;if($field==='email')$value=mb_strtolower($value);if($field==='country_code')$value=mb_strtoupper($value);if(($account->client->{$field}??null)!==$value)$changes[$field]=['from'=>$account->client->{$field},'to'=>$value];}
  if($changes===[])return back()->with('status','No changes were submitted.');
  DB::transaction(function()use($account,$changes){ClientChangeRequest::query()->where('client_id',$account->client_id)->where('status','pending')->update(['status'=>'superseded','reviewed_at'=>now()]);$change=ClientChangeRequest::query()->create(['client_id'=>$account->client_id,'portal_account_id'=>$account->id,'changes'=>$changes]);AdminNotice::query()->create(['type'=>'client_contact_change','client_id'=>$account->client_id,'client_change_request_id'=>$change->id,'title'=>'Client contact update requested','message'=>$account->displayName().' submitted '.count($changes).' contact change(s) for review.']);});
  return redirect()->route('portal.account.show')->with('status','Your changes were submitted for administrator review.');
 }
 public function password(Request $request): RedirectResponse {
  $data=$request->validate(['current_password'=>['required','current_password:client'],'password'=>['required','confirmed',\Illuminate\Validation\Rules\Password::defaults()]]);
  $request->user('client')->update(['password'=>$data['password']]);
  return back()->with('status','Your portal password was updated.');
 }
}
