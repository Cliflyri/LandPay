<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\PortalInvitation;
use App\Services\PortalInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
class PortalInvitationController extends Controller {
 public function __construct(private readonly PortalInvitationService $invitations){}
 public function store(Request $request,Client $client): RedirectResponse {$invitation=$this->invitations->invite($client,$request->user());return back()->with('success','Portal invitation sent to '.$invitation->email.'.');}
 public function destroy(Request $request,Client $client,PortalInvitation $invitation): RedirectResponse {abort_unless($invitation->client_id===$client->id,404);$this->invitations->revoke($invitation);return back()->with('success','Portal invitation revoked.');}
}
