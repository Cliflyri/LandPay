<?php
namespace App\Http\Controllers\Portal;
use App\Http\Controllers\Controller;
use App\Models\AdminNotice;
use App\Models\PaymentPlan;
use App\Models\SharedDocument;
use App\Services\SharedDocumentStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
class SharedDocumentController extends Controller{
 public function __construct(private readonly SharedDocumentStorageService $storage){}
 public function index(Request $request):View{$clientId=$request->user('client')->client_id;$documents=SharedDocument::query()->where('client_id',$clientId)->where('visible_to_client',true)->whereNull('archived_at')->with('paymentPlan')->latest()->paginate(20);$plans=PaymentPlan::query()->whereHas('memberships',fn($q)=>$q->where('client_id',$clientId))->orderBy('plan_number')->get();return view('portal.documents.index',compact('documents','plans'));}
 public function store(Request $request):RedirectResponse{$clientId=$request->user('client')->client_id;$data=$request->validate(['payment_plan_id'=>['nullable','integer','exists:payment_plans,id'],'category'=>['required',Rule::in(['general','contract','closing_document','identification','property_image'])],'document'=>['required','file','mimes:pdf,jpg,jpeg,png,docx','mimetypes:application/pdf,image/jpeg,image/png,application/vnd.openxmlformats-officedocument.wordprocessingml.document','max:10240']]);if(!empty($data['payment_plan_id']))abort_unless(DB::table('payment_plan_clients')->where('client_id',$clientId)->where('payment_plan_id',$data['payment_plan_id'])->exists(),422);$file=$this->storage->store($request->file('document'));$document=SharedDocument::query()->create($file+['client_id'=>$clientId,'payment_plan_id'=>$data['payment_plan_id']??null,'uploaded_by_client_id'=>$clientId,'category'=>$data['category'],'visible_to_client'=>true]);AdminNotice::query()->create(['type'=>'shared_document_uploaded','client_id'=>$clientId,'title'=>'Document uploaded','message'=>$request->user('client')->displayName().' uploaded '.$document->name.'.']);return back()->with('success','Document uploaded securely.');}
 public function download(Request $request,SharedDocument $document){abort_unless($document->client_id===$request->user('client')->client_id&&$document->visible_to_client&&$document->archived_at===null,404);$disk=Storage::disk($document->disk);abort_unless($disk->exists($document->path),404);return $disk->download($document->path,$document->name,['X-Content-Type-Options'=>'nosniff']);}
}
