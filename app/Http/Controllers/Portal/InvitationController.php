<?php
namespace App\Http\Controllers\Portal;
use App\Http\Controllers\Controller;
use App\Models\AdminNotice;
use App\Models\PortalAccount;
use App\Models\PortalInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
class InvitationController extends Controller {
 public function show(string $token): View {$invitation=$this->find($token);return view('portal.auth.accept-invitation',compact('invitation','token'));}
 public function accept(Request $request,string $token): RedirectResponse {
  $invitation=$this->find($token);$data=$request->validate(['password'=>['required','confirmed',Password::defaults()]]);
  DB::transaction(function()use($invitation,$data){$account=PortalAccount::query()->updateOrCreate(['client_id'=>$invitation->client_id],['email'=>$invitation->email,'password'=>$data['password'],'enabled'=>true]);$invitation->update(['accepted_at'=>now()]);AdminNotice::query()->create(['type'=>'portal_invitation_accepted','client_id'=>$invitation->client_id,'title'=>'Portal invitation accepted','message'=>$account->load('client')->displayName().' - '.$invitation->email.' activated portal access.']);});
  return redirect()->route('portal.login')->with('status','Your portal account is ready. You may now sign in.');
 }
 private function find(string $token): PortalInvitation {$invitation=PortalInvitation::query()->where('token_hash',hash('sha256',$token))->firstOrFail();abort_unless($invitation->isUsable(),410);return $invitation;}
}
