<?php
namespace App\Http\Controllers\Portal;
use App\Http\Controllers\Controller;
use App\Models\AdminNotice;
use App\Models\PortalAccount;
use App\Models\PortalInvitation;
use App\Services\PortalInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
class InvitationController extends Controller {
 public function __construct(private readonly PortalInvitationService $invitations){}
 public function show(string $token): View {$invitation=$this->find($token)->load('client.portalAccount');return view('portal.auth.accept-invitation',compact('invitation','token'));}
 public function accept(Request $request,string $token): RedirectResponse {
  $invitation=$this->find($token);abort_unless($invitation->isUsable(),410);$data=$request->validate(['password'=>['required','confirmed',Password::defaults()]]);
  DB::transaction(function()use($invitation,$data){$account=PortalAccount::query()->updateOrCreate(['client_id'=>$invitation->client_id],['email'=>$invitation->email,'password'=>$data['password'],'enabled'=>true]);$invitation->update(['accepted_at'=>now()]);AdminNotice::query()->create(['type'=>'portal_invitation_accepted','client_id'=>$invitation->client_id,'title'=>'Portal invitation accepted','message'=>$account->load('client')->displayName().' - '.$invitation->email.' activated portal access.']);});
  return redirect()->route('portal.login')->with('status','Your portal account is ready. You may now sign in.');
 }
 public function resend(string $token): RedirectResponse {
  $invitation=$this->find($token)->load('client.portalAccount','invitedBy');
  if($invitation->client->portalAccount?->enabled)return redirect()->route('portal.login')->with('status','Your portal account is already active. You may sign in.');
  $this->invitations->invite($invitation->client,$invitation->invitedBy);
  return back()->with('status','A new portal invitation has been sent to the email address on file.');
 }
 private function find(string $token): PortalInvitation {return PortalInvitation::query()->where('token_hash',hash('sha256',$token))->firstOrFail();}
}
