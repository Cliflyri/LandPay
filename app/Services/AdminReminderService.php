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
  $now=($at?:now())->copy()->timezone(config('app.timezone'));$period=$now->format('Y-m');$created=0;
  AdminReminder::query()->where('active',true)->where('recurrence_type','monthly')->each(function(AdminReminder $reminder)use($now,$period,&$created):void{
   $due=$now->copy()->startOfMonth()->day(min($reminder->day_of_month,$now->daysInMonth))->setTimeFromTimeString($reminder->display_time);
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
  return AdminReminderOccurrence::query()->where('period',$now->format('Y-m'))
   ->whereHas('reminder',fn($q)=>$q->where('active',true))
   ->whereDoesntHave('dismissals',fn($q)=>$q->where('user_id',$user->id))
   ->with('reminder')->orderBy('due_at')->get();
 }
}
