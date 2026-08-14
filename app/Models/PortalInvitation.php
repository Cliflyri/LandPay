<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class PortalInvitation extends Model {
 protected $guarded=['id'];
 protected function casts(): array {return ['encrypted_token'=>'encrypted','expires_at'=>'datetime','accepted_at'=>'datetime','revoked_at'=>'datetime'];}
 public function client(): BelongsTo {return $this->belongsTo(Client::class);}
 public function invitedBy(): BelongsTo {return $this->belongsTo(User::class,'invited_by_user_id');}
 public function isUsable(): bool {return $this->accepted_at===null && $this->revoked_at===null && $this->expires_at->isFuture();}
}
