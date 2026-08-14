<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\PaymentPlan;
use App\Models\SecureMessageThread;
use App\Models\SharedDocument;
use App\Services\SecureMessageNotificationService;
use App\Services\SharedDocumentStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;
class SharedDocumentController extends Controller{
 public function __construct(private readonly SharedDocumentStorageService $storage,private readonly SecureMessageNotificationService $notifications){}
 public function index(Request $request):View{
  $documents=SharedDocument::query()->with(['client','paymentPlan','uploadedByUser','uploadedByClient'])->when(!$request->boolean('archived'),fn($q)=>$q->whereNull('archived_at'))->when($request->filled('client'),fn($q)=>$q->where('client_id',$request->integer('client')))->latest()->paginate(25)->withQueryString();
  return view('admin.documents.index',['documents'=>$documents,'clients'=>Client::query()->whereNull('archived_at')->orderBy('last_name')->orderBy('first_name')->get(),'plans'=>PaymentPlan::query()->with('memberships')->orderBy('plan_number')->get()]);
 }
 public function store(Request $request):RedirectResponse{
  $data=$request->validate(['client_id'=>['required','integer','exists:clients,id'],'payment_plan_id'=>['nullable','integer','exists:payment_plans,id'],'category'=>['required',Rule::in(['general','contract','closing_document','identification','property_image'])],'document'=>['required','file','mimes:pdf,jpg,jpeg,png,docx','mimetypes:application/pdf,image/jpeg,image/png,application/vnd.openxmlformats-officedocument.wordprocessingml.document','max:10240'],'visible_to_client'=>['nullable','boolean'],'notify_client'=>['nullable','boolean'],'message'=>['nullable','string','max:10000']]);
  $this->validatePlan((int)$data['client_id'],$data['payment_plan_id']??null);$file=$this->storage->store($request->file('document'));$document=null;
  try{$document=DB::transaction(function()use($request,$data,$file){$visible=$request->boolean('visible_to_client')||$request->boolean('notify_client');$document=SharedDocument::query()->create($file+['client_id'=>$data['client_id'],'payment_plan_id'=>$data['payment_plan_id']??null,'uploaded_by_user_id'=>$request->user()->id,'category'=>$data['category'],'visible_to_client'=>$visible]);if($request->boolean('notify_client')){$thread=SecureMessageThread::query()->create(['client_id'=>$document->client_id,'payment_plan_id'=>$document->payment_plan_id,'subject'=>'New document: '.$document->name,'category'=>'general','latest_message_at'=>now()]);$thread->messages()->create(['sender_type'=>'admin','sender_user_id'=>$request->user()->id,'body'=>$data['message']?:'A new document has been shared with you.','shared_document_id'=>$document->id]);}return $document;});}catch(Throwable $e){if($document)$this->storage->delete($document);else Storage::disk($file['disk'])->delete($file['path']);throw $e;}
  if($request->boolean('notify_client')){$thread=SecureMessageThread::query()->whereHas('messages',fn($q)=>$q->where('shared_document_id',$document->id))->latest('id')->first();$this->notifications->send($thread->load('client'));}
  return back()->with('success','Document uploaded.'.($request->boolean('notify_client')?' Client notified.':''));
 }
 public function download(SharedDocument $document){$disk=Storage::disk($document->disk);abort_unless($disk->exists($document->path),404);return $disk->download($document->path,$document->name,['X-Content-Type-Options'=>'nosniff']);}
 public function preview(Request $request,SharedDocument $document){abort_unless(in_array($document->mime,['application/pdf','image/jpeg','image/png'],true),404);$disk=Storage::disk($document->disk);abort_unless($disk->exists($document->path),404);if(str_starts_with($document->mime,'image/')&&!$request->boolean('raw')){$src=route('admin.documents.preview',$document).'?raw=1';return response('<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><style>html,body{height:100%;margin:0}body{display:flex;align-items:center;justify-content:center;background:#f4f6f6}img{display:block;max-width:100%;max-height:100%;object-fit:contain}</style></head><body><img src="'.e($src).'" alt="'.e($document->name).'"></body></html>')->header('X-Content-Type-Options','nosniff');}return response()->file($disk->path($document->path),['Content-Type'=>$document->mime,'Content-Disposition'=>'inline','X-Content-Type-Options'=>'nosniff']);}
 public function visibility(SharedDocument $document):RedirectResponse{$document->update(['visible_to_client'=>!$document->visible_to_client]);return back()->with('success',$document->visible_to_client?'Document shared with client.':'Document hidden from client.');}
 public function archive(SharedDocument $document):RedirectResponse{$document->update(['archived_at'=>$document->archived_at?null:now()]);return back()->with('success',$document->archived_at?'Document archived.':'Document restored.');}
 public function destroy(SharedDocument $document):RedirectResponse{abort_if(!$this->storage->delete($document),500,'The document could not be deleted.');$document->delete();return back()->with('success','Document permanently deleted.');}
 private function validatePlan(int $clientId,mixed $planId):void{if(!$planId)return;abort_unless(DB::table('payment_plan_clients')->where('client_id',$clientId)->where('payment_plan_id',$planId)->exists(),422,'The selected plan is not associated with this client.');}
}
