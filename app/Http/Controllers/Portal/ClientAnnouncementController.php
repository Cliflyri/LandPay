<?php
namespace App\Http\Controllers\Portal;
use App\Http\Controllers\Controller;
use App\Models\{ClientAnnouncement,ClientAnnouncementRecipient};
use Illuminate\Http\{RedirectResponse,Request};
class ClientAnnouncementController extends Controller{
 public function act(Request $r,ClientAnnouncement $announcement):RedirectResponse{$recipient=$this->recipient($r,$announcement);abort_unless($announcement->isVisibleNow(),404);$field=$announcement->severity==='information'?'dismissed_at':'acknowledged_at';$recipient->update([$field=>$recipient->$field?:now(),'first_viewed_at'=>$recipient->first_viewed_at?:now()]);return back()->with('success','Announcement '.($field==='dismissed_at'?'dismissed':'acknowledged').'. It remains available under Messages > Announcements.');}
 public function view(Request $r,ClientAnnouncement $announcement):RedirectResponse{$recipient=$this->recipient($r,$announcement);abort_if($announcement->removed_from_clients_at,404);$recipient->update(['first_viewed_at'=>$recipient->first_viewed_at?:now()]);return redirect()->route('portal.messages.index',['tab'=>'announcements','announcement'=>$announcement->uuid]);}
 private function recipient(Request $r,ClientAnnouncement $a):ClientAnnouncementRecipient{return ClientAnnouncementRecipient::query()->where('client_announcement_id',$a->id)->where('client_id',$r->user('client')->client_id)->firstOrFail();}
}
