<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AdminNotice extends Model {
 public function improvementUpdate() {return $this->belongsTo(ImprovementUpdate::class);}

 public function billingContext(): array {
  $plan=$this->paymentPlan; $invoice=$this->invoice; $client=$this->client;
  // Older automation notices only stored identifiers in their message.
  if ($this->type==='billing_automation_failure' && preg_match('/^Plan (.+?)(?:, invoice ([^:]+))?: /',$this->message,$parts)) {
   if (!$invoice && !empty($parts[2])) $invoice=Invoice::where('invoice_number',$parts[2])->first();
   $plan ??= $invoice ? PaymentPlan::withTrashed()->find($invoice->payment_plan_id) : null;
   if (!$plan) {
    $matches=PaymentPlan::withTrashed()->where('plan_number',$parts[1])->limit(2)->get();
    if ($matches->count()===1) $plan=$matches->first();
   }
  }
  $client ??= $plan?->memberships()->where('role','primary')->whereNull('effective_to')->whereDate('effective_from','<=',today())->first()?->client;
  return compact('plan','invoice','client');
 }
 protected $guarded=['id'];
 protected function casts(): array {return ['dismissed_at'=>'datetime'];}
 public function sharedDocument(): BelongsTo {return $this->belongsTo(SharedDocument::class);}
 public function client(): BelongsTo {return $this->belongsTo(Client::class);}
 public function paymentPlan(): BelongsTo {return $this->belongsTo(PaymentPlan::class);}
 public function dismissedBy(): BelongsTo {return $this->belongsTo(User::class,'dismissed_by_user_id');}
 public function invoice(): BelongsTo {return $this->belongsTo(Invoice::class);}
 public function changeRequest(): BelongsTo {return $this->belongsTo(ClientChangeRequest::class,'client_change_request_id');}
 public function paymentIntent(): BelongsTo {return $this->belongsTo(ClientPaymentIntent::class,'client_payment_intent_id');}
 public function secureMessageThread(): BelongsTo {return $this->belongsTo(SecureMessageThread::class);}
}
