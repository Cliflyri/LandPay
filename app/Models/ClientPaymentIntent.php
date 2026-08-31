<?php
namespace App\Models;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
class ClientPaymentIntent extends Model {
 use HasPublicUuid;
 protected $guarded=['id','uuid'];
 protected function casts(): array{return ['amount'=>'integer','base_amount'=>'integer','processing_fee_amount'=>'integer','expires_at'=>'datetime','received_at'=>'datetime','cancelled_at'=>'datetime'];}
 public function paymentPlan(): BelongsTo{return $this->belongsTo(PaymentPlan::class);}
 public function invoice(): BelongsTo{return $this->belongsTo(Invoice::class);}
 public function client(): BelongsTo{return $this->belongsTo(Client::class);}
 public function portalAccount(): BelongsTo{return $this->belongsTo(PortalAccount::class);}
 public function payment(): BelongsTo{return $this->belongsTo(Payment::class);}
 public function adminNotice(): HasOne{return $this->hasOne(AdminNotice::class);}
}
