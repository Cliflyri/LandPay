<?php
namespace App\Http\Controllers\Portal;
use App\Http\Controllers\Controller;
use App\Models\AdminNotice;
use App\Models\PaymentPlan;
use App\Models\SecureMessage;
use App\Models\SecureMessageAttachment;
use App\Models\SecureMessageThread;
use App\Models\SharedDocument;
use App\Services\SecureMessageFileService;
use App\Services\SecureMessageNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
class SecureMessageController extends Controller{
 public function __construct(private readonly SecureMessageFileService $files,private readonly SecureMessageNotificationService $notifications){}
 public function index(Request $request):View{$threads=SecureMessageThread::query()->where('client_id',$request->user('client')->client_id)->with('paymentPlan')->withCount(['messages as unread_count'=>fn($q)=>$q->where('sender_type','admin')->whereNull('client_viewed_at')])->orderByDesc('latest_message_at')->paginate(20);return view('portal.messages.index',compact('threads'));}
 public function create(Request $request):View{$clientId=$request->user('client')->client_id;return view('portal.messages.create',['plans'=>$this->plans($clientId),'documents'=>$this->documents($clientId)]);}
 public function store(Request $request):RedirectResponse{
  $clientId=$request->user('client')->client_id;$data=$this->validateMessage($request,true);$this->validatePlan($clientId,$data['payment_plan_id']??null);$this->validateDocuments($clientId,$data['shared_document_ids']??[]);
  $thread=DB::transaction(function()use($request,$clientId,$data){$thread=SecureMessageThread::query()->create(['client_id'=>$clientId,'payment_plan_id'=>$data['payment_plan_id']??null,'subject'=>$data['subject'],'category'=>'general','latest_message_at'=>now()]);$message=$thread->messages()->create(['sender_type'=>'client','sender_client_id'=>$clientId,'body'=>$data['body']]);$this->files->attach($message,$request->file('attachments',[]),$data['save_in_documents']??[],$data['shared_document_ids']??[],['client_id'=>$clientId,'payment_plan_id'=>$thread->payment_plan_id,'uploader_client_id'=>$clientId,'category'=>'general']);$this->notice($thread,'New secure message','sent a new secure message.');return $thread;});
  $this->notifications->sendToAdmin($thread);return redirect()->route('portal.messages.show',$thread)->with('success','Your secure message was sent.');
 }
 public function show(Request $request,SecureMessageThread $thread):View{$this->authorizeThread($request,$thread);$thread->messages()->where('sender_type','admin')->whereNull('client_viewed_at')->update(['client_viewed_at'=>now()]);$thread->load(['paymentPlan','messages.senderUser','messages.senderClient','messages.documents','messages.attachments']);$documents=$this->documents($thread->client_id);return view('portal.messages.show',compact('thread','documents'));}
 public function reply(Request $request,SecureMessageThread $thread):RedirectResponse{
  $this->authorizeThread($request,$thread);$data=$this->validateMessage($request,false);$this->validateDocuments($thread->client_id,$data['shared_document_ids']??[]);
  DB::transaction(function()use($request,$thread,$data){$message=$thread->messages()->create(['sender_type'=>'client','sender_client_id'=>$thread->client_id,'body'=>$data['body']]);$this->files->attach($message,$request->file('attachments',[]),$data['save_in_documents']??[],$data['shared_document_ids']??[],['client_id'=>$thread->client_id,'payment_plan_id'=>$thread->payment_plan_id,'uploader_client_id'=>$thread->client_id,'category'=>'general']);$thread->update(['latest_message_at'=>now()]);$this->notice($thread,'Secure message reply','replied to "'.$thread->subject.'".');});
  $this->notifications->sendToAdmin($thread);return back()->with('success','Your reply was sent securely.');
 }
 public function download(Request $request,SecureMessageThread $thread,SecureMessage $message){$this->authorizeThread($request,$thread);abort_unless($message->secure_message_thread_id===$thread->id&&filled($message->attachment_path),404);$disk=Storage::disk($message->attachment_disk?:'local');abort_unless($disk->exists($message->attachment_path),404);if($request->boolean('inline')&&in_array($message->attachment_mime,['image/jpeg','image/png'],true))return response()->file($disk->path($message->attachment_path),['Content-Type'=>$message->attachment_mime]);$message->update(['attachment_downloaded_at'=>$message->attachment_downloaded_at??now()]);return $disk->download($message->attachment_path,$message->attachment_name);}
 public function downloadFile(Request $request,SecureMessageThread $thread,SecureMessage $message,SecureMessageAttachment $attachment){$this->authorizeThread($request,$thread);abort_unless($message->secure_message_thread_id===$thread->id&&$attachment->secure_message_id===$message->id,404);$disk=Storage::disk($attachment->disk);abort_unless($disk->exists($attachment->path),404);if($request->boolean('inline')&&in_array($attachment->mime,['image/jpeg','image/png'],true))return response()->file($disk->path($attachment->path),['Content-Type'=>$attachment->mime]);$attachment->update(['client_downloaded_at'=>$attachment->client_downloaded_at??now()]);return $disk->download($attachment->path,$attachment->name,['X-Content-Type-Options'=>'nosniff']);}
 private function validateMessage(Request $request,bool $new):array{$rules=['body'=>['required','string','max:10000'],'attachments'=>['nullable','array','max:5'],'attachments.*'=>['file','mimes:pdf,jpg,jpeg,png,docx','mimetypes:application/pdf,image/jpeg,image/png,application/vnd.openxmlformats-officedocument.wordprocessingml.document','max:10240'],'save_in_documents'=>['nullable','array','max:5'],'save_in_documents.*'=>['integer','between:0,4'],'shared_document_ids'=>['nullable','array','max:5'],'shared_document_ids.*'=>['integer','distinct','exists:shared_documents,id']];if($new)$rules+=['subject'=>['required','string','max:150'],'payment_plan_id'=>['nullable','integer','exists:payment_plans,id']];return $request->validate($rules);}
 private function documents(int $clientId){return SharedDocument::query()->where('client_id',$clientId)->where('visible_to_client',true)->whereNull('archived_at')->latest()->get();}
 private function plans(int $clientId){return PaymentPlan::query()->whereHas('memberships',fn($q)=>$q->where('client_id',$clientId))->orderBy('plan_number')->get();}
 private function validatePlan(int $clientId,mixed $planId):void{if($planId)abort_unless(DB::table('payment_plan_clients')->where('client_id',$clientId)->where('payment_plan_id',$planId)->exists(),422);}
 private function validateDocuments(int $clientId,array $ids):void{if(!$ids)return;$count=SharedDocument::query()->whereKey($ids)->where('client_id',$clientId)->where('visible_to_client',true)->whereNull('archived_at')->count();abort_unless($count===count(array_unique($ids)),422);}
 private function notice(SecureMessageThread $thread,string $title,string $action):void{AdminNotice::query()->create(['type'=>'secure_message_reply','client_id'=>$thread->client_id,'secure_message_thread_id'=>$thread->id,'title'=>$title,'message'=>auth('client')->user()->displayName().' '.$action]);}
 private function authorizeThread(Request $request,SecureMessageThread $thread):void{abort_unless($thread->client_id===$request->user('client')->client_id,404);}
}
