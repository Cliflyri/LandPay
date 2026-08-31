<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AdminNotice extends Model {
 protected $guarded=['id'];
 protected function casts(): array {return ['dismissed_at'=>'datetime'];}
 public function client(): BelongsTo {return $this->belongsTo(Client::class);}
 public function invoice(): BelongsTo {return $this->belongsTo(Invoice::class);}
 public function changeRequest(): BelongsTo {return $this->belongsTo(ClientChangeRequest::class,'client_change_request_id');}
 public function paymentIntent(): BelongsTo {return $this->belongsTo(ClientPaymentIntent::class,'client_payment_intent_id');}
 public function secureMessageThread(): BelongsTo {return $this->belongsTo(SecureMessageThread::class);}
}
