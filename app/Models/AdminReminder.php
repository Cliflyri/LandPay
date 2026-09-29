<?php
namespace App\Models;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class AdminReminder extends Model{
 use HasPublicUuid;
 public const DESTINATIONS=['contracts_report'=>'Contracts Report'];
 protected $guarded=['id'];
 protected function casts():array{return ['active'=>'boolean','send_email'=>'boolean'];}
 public function occurrences():HasMany{return $this->hasMany(AdminReminderOccurrence::class);}
 public function destinationUrl():string{
  return match($this->destination){'contracts_report'=>route('admin.reports.show',['report'=>'contracts']),default=>route('admin.dashboard')};
 }
 public function destinationLabel():string{return self::DESTINATIONS[$this->destination]??'Admin Dashboard';}
}
