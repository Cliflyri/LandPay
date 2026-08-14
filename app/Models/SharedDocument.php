<?php
namespace App\Models;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class SharedDocument extends Model{
 use HasPublicUuid;
 protected $guarded=['id','uuid'];
 protected function casts():array{return ['visible_to_client'=>'boolean','archived_at'=>'datetime','size'=>'integer'];}
 public function getRouteKeyName():string{return 'uuid';}
 public function client():BelongsTo{return $this->belongsTo(Client::class);}
 public function paymentPlan():BelongsTo{return $this->belongsTo(PaymentPlan::class);}
 public function uploadedByUser():BelongsTo{return $this->belongsTo(User::class,'uploaded_by_user_id');}
 public function uploadedByClient():BelongsTo{return $this->belongsTo(Client::class,'uploaded_by_client_id');}
}
