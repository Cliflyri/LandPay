<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo,HasMany};
class AdminReminderOccurrence extends Model{
 protected $guarded=['id'];
 protected function casts():array{return ['due_at'=>'datetime','email_processed_at'=>'datetime','email_sent_at'=>'datetime'];}
 public function reminder():BelongsTo{return $this->belongsTo(AdminReminder::class,'admin_reminder_id');}
 public function dismissals():HasMany{return $this->hasMany(AdminReminderDismissal::class);}
}
