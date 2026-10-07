<?php
namespace App\Models;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class AdminReminder extends Model{
 use HasPublicUuid;
 public const DESTINATIONS=['notification_only'=>'Notification only','contracts_report'=>'Contracts Report'];
 protected $attributes=['destination'=>'notification_only','recurrence_type'=>'monthly'];
 protected $guarded=['id'];
 protected function casts():array{return ['active'=>'boolean','send_email'=>'boolean'];}
 public function period(\Illuminate\Support\Carbon $at):string{
  return match($this->recurrence_type){'weekly'=>$at->format('\\WWo'),'annually'=>'A'.$at->format('Y'),default=>$at->format('Y-m')};
 }
 public function dueAt(\Illuminate\Support\Carbon $at):\Illuminate\Support\Carbon{
  $due=match($this->recurrence_type){
   'weekly'=>$at->copy()->startOfWeek(1)->addDays((int)$this->day_of_week-1),
   'annually'=>$at->copy()->startOfYear()->month((int)$this->month_of_year),
   default=>$at->copy()->startOfMonth(),
  };
  if($this->recurrence_type!=='weekly')$due->day(min((int)$this->day_of_month,$due->daysInMonth));
  return $due->setTimeFromTimeString($this->display_time);
 }
 public function scheduleLabel():string{
  return match($this->recurrence_type){
   'weekly'=>'Weekly on '.([1=>'Monday',2=>'Tuesday',3=>'Wednesday',4=>'Thursday',5=>'Friday',6=>'Saturday',7=>'Sunday'][$this->day_of_week]??'Monday'),
   'annually'=>'Annually on '.\Illuminate\Support\Carbon::create(2000,(int)$this->month_of_year,1)->format('F').' '.$this->day_of_month,
   default=>'Monthly on day '.$this->day_of_month,
  };
 }
 public function occurrences():HasMany{return $this->hasMany(AdminReminderOccurrence::class);}
 public function destinationUrl():?string{
  return match($this->destination){'notification_only'=>null,'contracts_report'=>route('admin.reports.show',['report'=>'contracts']),default=>route('admin.dashboard')};
 }
 public function destinationLabel():string{return self::DESTINATIONS[$this->destination]??'Admin Dashboard';}
}
