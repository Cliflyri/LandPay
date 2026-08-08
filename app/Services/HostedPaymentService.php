<?php
namespace App\Services;
use App\Models\AppSetting;
use App\Models\ClientPaymentIntent;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
class HostedPaymentService {
 public function create(ClientPaymentIntent $intent): ClientPaymentIntent {
  $provider=AppSetting::valueFor('card_provider','disabled');if(!in_array($provider,['square','stripe'],true))throw ValidationException::withMessages(['method'=>'Card payments are not currently enabled.']);
  $secret=AppSetting::encryptedValueFor($provider.'_api_secret');if(blank($secret))throw ValidationException::withMessages(['method'=>ucfirst($provider).' is not connected.']);
  return $provider==='square'?$this->square($intent,$secret):$this->stripe($intent,$secret);
 }
 private function square(ClientPaymentIntent $intent,string $secret): ClientPaymentIntent {
  $sandbox=AppSetting::valueFor('square_environment','sandbox')!=='live';$url=($sandbox?'https://connect.squareupsandbox.com':'https://connect.squareup.com').'/v2/online-checkout/payment-links';
  $response=Http::withToken($secret)->withHeaders(['Square-Version'=>'2026-07-15'])->post($url,['idempotency_key'=>$intent->uuid,'quick_pay'=>['name'=>'LandPay plan '.$intent->paymentPlan->plan_number,'price_money'=>['amount'=>$intent->amount,'currency'=>'USD'],'location_id'=>AppSetting::valueFor('square_public_id','')],'checkout_options'=>['redirect_url'=>route('portal.make-payment.show',$intent)]]);
  if(!$response->successful()||blank($response->json('payment_link.url')))throw ValidationException::withMessages(['method'=>'Square checkout could not be started.']);
  $intent->update(['status'=>'checkout_pending','provider'=>'square','provider_checkout_id'=>$response->json('payment_link.order_id'),'checkout_url'=>$response->json('payment_link.url'),'expires_at'=>now()->addDay()]);return $intent->fresh();
 }
 private function stripe(ClientPaymentIntent $intent,string $secret): ClientPaymentIntent {
  $response=Http::withBasicAuth($secret,'')->asForm()->post('https://api.stripe.com/v1/checkout/sessions',['mode'=>'payment','success_url'=>route('portal.make-payment.show',$intent).'?checkout=success','cancel_url'=>route('portal.make-payment.create',['plan'=>$intent->payment_plan_id]),'client_reference_id'=>$intent->uuid,'metadata[landpay_intent_uuid]'=>$intent->uuid,'line_items[0][price_data][currency]'=>'usd','line_items[0][price_data][product_data][name]'=>'LandPay plan '.$intent->paymentPlan->plan_number,'line_items[0][price_data][unit_amount]'=>$intent->amount,'line_items[0][quantity]'=>1]);
  if(!$response->successful()||blank($response->json('url')))throw ValidationException::withMessages(['method'=>'Stripe checkout could not be started.']);
  $intent->update(['status'=>'checkout_pending','provider'=>'stripe','provider_checkout_id'=>$response->json('id'),'checkout_url'=>$response->json('url'),'expires_at'=>now()->addDay()]);return $intent->fresh();
 }
}