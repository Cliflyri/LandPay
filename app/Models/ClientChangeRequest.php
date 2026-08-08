<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class ClientChangeRequest extends Model {
 protected $guarded=['id'];
 protected function casts(): array {return ['changes'=>'array','reviewed_at'=>'datetime'];}
 public function client(): BelongsTo {return $this->belongsTo(Client::class);}
 public function portalAccount(): BelongsTo {return $this->belongsTo(PortalAccount::class);}
 public function reviewedBy(): BelongsTo {return $this->belongsTo(User::class,'reviewed_by_user_id');}
 public function notices(): HasMany {return $this->hasMany(AdminNotice::class);}
}
