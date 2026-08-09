<?php
namespace App\Http\Controllers;
use App\Enums\OverpaymentDisposition;
use App\Enums\PaymentMethod;
use App\Models\AdminNotice;
use App\Models\AppSetting;
use App\Models\ClientPaymentIntent;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class ProviderWebhookController extends Controller {
 public function __construct(private readonly PaymentService $payments){}
 public function __invoke(Request $request,string $provider): Response {
  abort_unless(in_array($provider,['square','stripe'],true),404);$body=$request->getContent();$secret=AppSetting::encryptedValueFor($provider.'_webhook_secret');abort_if(blank($secret),503);
  abort_unless($provider==='square'?$this->validSquare($request,$body,$secret):$this->validStripe($request,$body,$secret),401);
  $payload=$request->json()->all();$data=$provider==='square'?$this->squareData($payload):$this->stripeData($payload);if($data===null)return response('ignored',200);
  $intent=ClientPaymentIntent::query()->where($provider==='square'?'provider_checkout_id':'provider_checkout_id',$data['checkout_id'])->first();if(!$intent)return response('ignored',200);
  if($intent->status==='received'||filled($intent->provider_payment_id))return response('ok',200);
  if($data['amount']!==$intent->amount||$data['currency']!=='USD'){$intent->update(['status'=>'review_required']);AdminNotice::create(['type'=>'provider_payment_exception','client_id'=>$intent->client_id,'client_payment_intent_id'=>$intent->id,'title'=>'Online payment requires review','message'=>'Provider amount or currency did not match LandPay checkout '.$intent->uuid.'.']);return response('review',200);}
  $actor=User::query()->where('status','active')->oldest()->firstOrFail();$payment=$this->payments->post($intent->paymentPlan,$actor,$intent->amount,'regular',PaymentMethod::Card,now()->toDateString(),$intent->client_id,$provider.':'.$data['payment_id'],$intent->overpayment_disposition?OverpaymentDisposition::from($intent->overpayment_disposition):null,'provider:'.$provider.':'.$data['payment_id']);
  $intent->update(['status'=>'received','provider_payment_id'=>$data['payment_id'],'payment_id'=>$payment->id,'received_at'=>now()]);
  $clientName=trim($intent->client->first_name.' '.$intent->client->last_name);
  AdminNotice::create(['type'=>'online_payment_received','client_id'=>$intent->client_id,'client_payment_intent_id'=>$intent->id,'title'=>'Online payment received','message'=>$clientName.' paid '.\App\Support\Money::format($intent->amount).' by '.ucfirst($provider).' on '.$payment->received_date->format('M j, Y').'. Payment posted successfully.']);
  return response('ok',200);
 }
 private function validSquare(Request $r,string $body,string $secret): bool{$expected=base64_encode(hash_hmac('sha256',url('/webhooks/square').$body,$secret,true));return hash_equals($expected,(string)$r->header('x-square-hmacsha256-signature'));}
 private function validStripe(Request $r,string $body,string $secret): bool{$parts=[];foreach(explode(',',(string)$r->header('stripe-signature')) as $part){[$k,$v]=array_pad(explode('=',$part,2),2,'');$parts[$k]=$v;}if(empty($parts['t'])||empty($parts['v1'])||abs(time()-(int)$parts['t'])>300)return false;return hash_equals(hash_hmac('sha256',$parts['t'].'.'.$body,$secret),$parts['v1']);}
 private function squareData(array $p): ?array{$pay=$p['data']['object']['payment']??null;if(($p['type']??null)!=='payment.updated'||($pay['status']??null)!=='COMPLETED')return null;return ['checkout_id'=>$pay['order_id']??'','payment_id'=>$pay['id']??'','amount'=>(int)($pay['amount_money']['amount']??0),'currency'=>$pay['amount_money']['currency']??''];}
 private function stripeData(array $p): ?array{$s=$p['data']['object']??null;if(($p['type']??null)!=='checkout.session.completed'||($s['payment_status']??null)!=='paid')return null;return ['checkout_id'=>$s['id']??'','payment_id'=>$s['payment_intent']??($s['id']??''),'amount'=>(int)($s['amount_total']??0),'currency'=>strtoupper($s['currency']??'')];}
}