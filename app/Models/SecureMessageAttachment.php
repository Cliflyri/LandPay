<?php
namespace App\Models;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class SecureMessageAttachment extends Model{
 use HasPublicUuid;
 protected $guarded=['id','uuid'];
 protected function casts():array{return ['size'=>'integer','client_downloaded_at'=>'datetime'];}
 public function getRouteKeyName():string{return 'uuid';}
 public function message():BelongsTo{return $this->belongsTo(SecureMessage::class,'secure_message_id');}
}
