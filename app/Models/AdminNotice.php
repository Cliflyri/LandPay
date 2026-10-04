<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AdminNotice extends Model {

 protected static function booted(): void {
  static::addGlobalScope('main_supported_notices',function(\Illuminate\Database\Eloquent\Builder $query):void {
   $schema=$query->getModel()->getConnection()->getSchemaBuilder();
   if($schema->hasColumn('secure_message_threads','improvement_id')) {
    $query->whereNotExists(function($threads){
     $threads->selectRaw('1')->from('secure_message_threads')
      ->whereColumn('secure_message_threads.id','admin_notices.secure_message_thread_id')
      ->whereNotNull('secure_message_threads.improvement_id');
    });
   }
   if($schema->hasColumn('admin_notices','improvement_update_id')) $query->whereNull('admin_notices.improvement_update_id');
  });
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
