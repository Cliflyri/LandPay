<?php
namespace App\Services;
use App\Mail\PortalInvitationMail;
use App\Models\Client;
use App\Models\EmailDelivery;
use App\Models\PortalInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
class PortalInvitationService {
 public function __construct(private readonly EmailTemplateService $templates){}
 public function invite(Client $client,User $user): PortalInvitation {
  if(blank($client->email)) throw ValidationException::withMessages(['portal_account'=>'Add a client email before sending an invitation.']);
  $token=Str::random(64);$email=mb_strtolower($client->email);$expires=now()->addHours(48);
  $invitation=DB::transaction(function()use($client,$user,$token,$email,$expires){PortalInvitation::query()->where('client_id',$client->id)->whereNull('accepted_at')->whereNull('revoked_at')->update(['revoked_at'=>now()]);return PortalInvitation::query()->create(['client_id'=>$client->id,'invited_by_user_id'=>$user->id,'email'=>$email,'token_hash'=>hash('sha256',$token),'expires_at'=>$expires]);});
  $url=route('portal.invitation.show',$token);
  $rendered=$this->templates->renderVariables('portal-invitation',['client_name'=>$client->organization_name ?: trim($client->first_name.' '.$client->last_name),'invitation_link'=>$url,'invitation_expires'=>$expires->format('F j, Y g:i A T'),'company_name'=>\App\Models\AppSetting::valueFor('company_name',config('app.name','LandPay')),'company_email'=>\App\Models\AppSetting::valueFor('company_email',''),'company_phone'=>\App\Models\AppSetting::valueFor('company_phone','')]);
  $delivery=EmailDelivery::query()->create(['recipient_client_id'=>$client->id,'sent_by_user_id'=>$user->id,'template_slug'=>'portal-invitation','recipient_email'=>$email,'subject_snapshot'=>$rendered['subject'],'body_snapshot'=>$rendered['body'],'delivery_format'=>'inline','status'=>'pending']);
  try {Mail::to($email)->send(new PortalInvitationMail($rendered['subject'],$rendered['body']));$delivery->update(['status'=>'sent','sent_at'=>now()]);}
  catch(\Throwable $e){$delivery->update(['status'=>'failed','failed_at'=>now(),'failure_message'=>Str::limit($e->getMessage(),500)]);throw $e;}
  return $invitation;
 }
 public function revoke(PortalInvitation $invitation): void {if($invitation->accepted_at===null)$invitation->update(['revoked_at'=>now()]);}
}
