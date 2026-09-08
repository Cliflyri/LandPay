<?php
namespace App\Models;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo,HasMany};
class ClientAnnouncement extends Model{
 use HasPublicUuid;protected $guarded=['id','uuid'];
 protected function casts():array{return ['published_at'=>'datetime','starts_at'=>'datetime','ends_at'=>'datetime','deactivated_at'=>'datetime','removed_from_clients_at'=>'datetime','recipients_created_at'=>'datetime','send_email'=>'boolean'];}
 public function getRouteKeyName():string{return 'uuid';}
 public function recipients():HasMany{return $this->hasMany(ClientAnnouncementRecipient::class);}
 public function creator():BelongsTo{return $this->belongsTo(User::class,'created_by_user_id');}
 public function publisher():BelongsTo{return $this->belongsTo(User::class,'published_by_user_id');}
 public function status():string{if(!$this->published_at)return 'draft';if($this->removed_from_clients_at)return 'removed';if($this->deactivated_at)return 'deactivated';if($this->starts_at?->isFuture())return 'scheduled';if($this->ends_at?->isPast())return 'expired';return 'active';}
 public function isVisibleNow():bool{return (bool)$this->published_at&&!$this->deactivated_at&&!$this->removed_from_clients_at&&(!$this->starts_at||$this->starts_at->lte(now()))&&(!$this->ends_at||$this->ends_at->gt(now()));}
}
