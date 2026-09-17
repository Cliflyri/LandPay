<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AdminReminderDismissal extends Model{
 protected $guarded=['id'];
 protected function casts():array{return ['dismissed_at'=>'datetime'];}
 public function occurrence():BelongsTo{return $this->belongsTo(AdminReminderOccurrence::class,'admin_reminder_occurrence_id');}
}
