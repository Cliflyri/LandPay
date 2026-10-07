<?php
namespace App\Services;
use App\Models\{AdminNotice,AdminReminder,AdminReminderOccurrence,User};
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
class AdminReminderService{
 public function __construct(private readonly AdminNoticeEmailService $emails){}
 public function processDue(?Carbon $at=null):int{
  if(!Schema::hasTable('admin_reminders'))return 0;
  $now=($at?:now())->copy()->timezone(config('app.timezone'));$created=0;
  AdminReminder::query()->where('active',true)->each(function(AdminReminder $reminder)use($now,&$created):void{
   $period=$reminder->period($now);$due=$reminder->dueAt($now);
   if($now->lt($due))return;
   $occurrence=AdminReminderOccurrence::query()->firstOrCreate(
    ['admin_reminder_id'=>$reminder->id,'period'=>$period],['due_at'=>$due]
   );
   if(!$occurrence->wasRecentlyCreated)return;
   $sent=false;
   if($reminder->send_email){
    $notice=new AdminNotice(['type'=>'scheduled_reminder','title'=>$reminder->title,'message'=>$reminder->message?:$reminder->title]);$notice->setAttribute('action_url',$reminder->destinationUrl());
    $sent=$this->emails->send($notice);
   }
   $occurrence->update(['email_processed_at'=>now(),'email_sent_at'=>$sent?now():null]);$created++;
  });
  return $created;
 }
 public function activeCount():int{return Schema::hasTable('admin_reminders')?AdminReminder::query()->where('active',true)->count():0;}
 public function dueFor(User $user,?Carbon $at=null):Collection{
  if(!Schema::hasTable('admin_reminder_occurrences'))return collect();
  $now=($at?:now())->copy()->timezone(config('app.timezone'));
  return AdminReminderOccurrence::query()->where('due_at','<=',$now)
   ->whereHas('reminder',fn($q)=>$q->where('active',true))
   ->whereDoesntHave('dismissals',fn($q)=>$q->where('user_id',$user->id))
   ->with('reminder')->orderBy('due_at')->get()->filter(fn($occurrence)=>$occurrence->period===$occurrence->reminder->period($now))->values();
 }
}
