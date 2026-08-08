<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\ClientPaymentIntent;
use Illuminate\Http\RedirectResponse;
class ClientPaymentIntentController extends Controller {
 public function __invoke(ClientPaymentIntent $intent): RedirectResponse {
  abort_unless($intent->status==='announced',409);
  return redirect()->route('admin.plans.payments.create',['plan'=>$intent->payment_plan_id,'client_payment_intent_id'=>$intent->id,'amount'=>number_format($intent->amount/100,2,'.',''),'payment_method'=>$intent->method,'payer_client_id'=>$intent->client_id,'external_reference'=>$intent->client_reference,'overpayment_disposition'=>$intent->overpayment_disposition]);
 }
}