<?php
namespace App\Services;
use App\Mail\ClientAnnouncementMail;
use App\Models\{AppSetting,ClientAnnouncement,ClientAnnouncementRecipient,PortalAccount};
use Illuminate\Support\Facades\{DB,Mail};
use Throwable;
class ClientAnnouncementService{
 public function processDue():int{$count=0;ClientAnnouncement::query()->whereNotNull('published_at')->whereNull('deactivated_at')->whereNull('removed_from_clients_at')->where(fn($q)=>$q->whereNull('starts_at')->orWhere('starts_at','<=',now()))->where(fn($q)=>$q->whereNull('ends_at')->orWhere('ends_at','>',now()))->each(function($a)use(&$count){$this->activate($a);$count++;});return $count;}
 public function activate(ClientAnnouncement $a):void{
  DB::transaction(function()use($a){PortalAccount::query()->where('enabled',true)->with('client')->each(function($account)use($a){$email=filter_var($account->client->email,FILTER_VALIDATE_EMAIL)?strtolower(trim($account->client->email)):null;$phone=$account->client->primary_phone?:$account->client->secondary_phone;if(!$email&&!$phone)return;ClientAnnouncementRecipient::query()->firstOrCreate(['client_announcement_id'=>$a->id,'client_id'=>$account->client_id],['portal_account_id'=>$account->id,'email'=>$email,'phone'=>$phone,'email_status'=>$a->send_email&&$email?'pending':'not_requested']);});$a->update(['recipients_created_at'=>now()]);});
  if(!$a->isVisibleNow())return;
  if(!$a->send_email)return;$company=AppSetting::valueFor('company_name',config('app.name','LandPay'));
  $a->recipients()->where('email_status','pending')->each(function($r)use($company){try{Mail::to($r->email)->send(new ClientAnnouncementMail($company,route('portal.messages.index',['tab'=>'announcements'])));$r->update(['email_status'=>'sent','email_sent_at'=>now()]);}catch(Throwable $e){report($e);$r->update(['email_status'=>'failed','email_failed_at'=>now()]);}});
 }
}
