<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ClientAnnouncementRecipient extends Model{
 protected $guarded=['id'];
 protected function casts():array{return ['email_sent_at'=>'datetime','email_failed_at'=>'datetime','first_viewed_at'=>'datetime','dismissed_at'=>'datetime','acknowledged_at'=>'datetime'];}
 public function announcement():BelongsTo{return $this->belongsTo(ClientAnnouncement::class,'client_announcement_id');}
 public function client():BelongsTo{return $this->belongsTo(Client::class);}
 public function portalAccount():BelongsTo{return $this->belongsTo(PortalAccount::class);}
}
