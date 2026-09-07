<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\ClientAnnouncement;
use App\Services\ClientAnnouncementService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Validation\Rule;
use Illuminate\View\View;
class ClientAnnouncementController extends Controller{
 public function __construct(private readonly ClientAnnouncementService $service){}
 public function index():View{return view('admin.announcements.index',['announcements'=>ClientAnnouncement::query()->withCount('recipients')->latest()->paginate(25)]);}
 public function create():View{return view('admin.announcements.form',['announcement'=>new ClientAnnouncement]);}
 public function store(Request $r):RedirectResponse{$a=ClientAnnouncement::query()->create($this->data($r)+['created_by_user_id'=>$r->user()->id]);return redirect()->route('admin.announcements.show',$a)->with('success','Announcement draft saved.');}
 public function show(ClientAnnouncement $announcement):View{$announcement->load(['creator','publisher'])->loadCount(['recipients','recipients as responded_count'=>fn($q)=>$q->where(fn($q)=>$q->whereNotNull('dismissed_at')->orWhereNotNull('acknowledged_at'))]);$recipients=$announcement->recipients()->with('client')->latest('acknowledged_at')->paginate(50);return view('admin.announcements.show',compact('announcement','recipients'));}
 public function edit(ClientAnnouncement $announcement):View{abort_if($announcement->published_at,422,'Published announcements cannot be edited. Create a new announcement for material changes.');return view('admin.announcements.form',compact('announcement'));}
 public function update(Request $r,ClientAnnouncement $announcement):RedirectResponse{abort_if($announcement->published_at,422,'Published announcements cannot be edited.');$announcement->update($this->data($r));return redirect()->route('admin.announcements.show',$announcement)->with('success','Announcement updated.');}
 public function publish(Request $r,ClientAnnouncement $announcement):RedirectResponse{
  abort_if($announcement->published_at,422,'Announcement is already published.');
  abort_if($announcement->ends_at?->lte(now()),422,'The end time must be in the future.');
  $announcement->update(['published_at'=>now(),'published_by_user_id'=>$r->user()->id]);
  $this->service->activate($announcement->fresh());
  $announcement->refresh()->loadCount('recipients');
  $scheduled=$announcement->starts_at?->isFuture()??false;
  $message=$scheduled
   ? 'Announcement scheduled successfully. It will become visible and notifications will begin '.$announcement->starts_at->format('M j, Y g:i A').'. '.$announcement->recipients_count.' eligible client(s) recorded.'
   : 'Announcement published successfully and is visible now to '.$announcement->recipients_count.' eligible client(s).';
  if(!$scheduled&&$announcement->send_email){$sent=$announcement->recipients()->where('email_status','sent')->count();$failed=$announcement->recipients()->where('email_status','failed')->count();$message.=' Email notifications: '.$sent.' sent'.($failed?', '.$failed.' failed':'.');}
  return back()->with('publication_success',$message);
 }
 public function deactivate(ClientAnnouncement $announcement):RedirectResponse{$announcement->update(['deactivated_at'=>now()]);return back()->with('success','Announcement deactivated. It remains in client history.');}
 public function reactivate(ClientAnnouncement $announcement):RedirectResponse{$announcement->update(['deactivated_at'=>null]);$this->service->activate($announcement->fresh());return back()->with('success','Announcement reactivated.');}
 public function remove(ClientAnnouncement $announcement):RedirectResponse{$announcement->update(['removed_from_clients_at'=>now(),'deactivated_at'=>$announcement->deactivated_at?:now()]);return back()->with('success','Announcement removed from all client banners and history.');}
 public function destroy(ClientAnnouncement $announcement):RedirectResponse{abort_if($announcement->published_at,422,'Only drafts can be permanently deleted.');$announcement->delete();return redirect()->route('admin.announcements.index')->with('success','Draft deleted.');}
private function data(Request $r):array{$end=['nullable','date','after:now'];if($r->filled('starts_at'))$end[]='after:starts_at';return $r->validate(['title'=>['required','string','max:150'],'body'=>['required','string','max:10000'],'severity'=>['required',Rule::in(['information','important','urgent'])],'starts_at'=>['nullable','date'],'ends_at'=>$end,'send_email'=>['nullable','boolean']])+['send_email'=>$r->boolean('send_email')];}
}
