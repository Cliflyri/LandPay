<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{AuditLog,PropertyTaxBatch,PropertyTaxBatchRow};
use App\Services\PropertyTaxBatchService;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PropertyTaxBatchController extends Controller
{
 public function __construct(private readonly PropertyTaxBatchService $service){}
 public function index(Request $request):View
 {
  $query=PropertyTaxBatch::query()->withCount(['rows','rows as existing_invoice_count'=>fn($q)=>$q->whereNotNull('existing_invoice_id')])->latest();
  if(in_array($request->status,['draft','issued','partially_issued','not_issued'],true))$query->where('status',$request->status);
  if($request->email==='failed')$query->whereHas('rows',fn($q)=>$q->where('email_status','failed'));
  return view('admin.property-taxes.index',['batches'=>$query->paginate(25)->withQueryString()]);
 }
 public function create():View{return $this->form(new PropertyTaxBatch(['tax_year'=>today()->year,'label'=>'Annual','issue_date'=>today(),'due_date'=>today()->addDays(30),'email_clients'=>true]));}
 public function store(Request $request):RedirectResponse{return $this->save($request,new PropertyTaxBatch);}
 public function edit(PropertyTaxBatch $propertyTaxBatch):View{abort_unless($propertyTaxBatch->status==='draft',404);return $this->form($propertyTaxBatch);}
 public function update(Request $request,PropertyTaxBatch $propertyTaxBatch):RedirectResponse{abort_unless($propertyTaxBatch->status==='draft',409);return $this->save($request,$propertyTaxBatch);}
 public function show(PropertyTaxBatch $propertyTaxBatch):View
 {
  $propertyTaxBatch->load(['rows.paymentPlan.memberships'=>fn($q)=>$q->whereNull('effective_to')->with('client'),'rows.invoice.emailDeliveries','rows.existingInvoice']);
  return view('admin.property-taxes.show',['batch'=>$propertyTaxBatch]);
 }
 public function issue(Request $request,PropertyTaxBatch $propertyTaxBatch):RedirectResponse
 {
  $data=$request->validate(['include_drafts'=>['nullable','array'],'include_drafts.*'=>['integer'],'exclude_rows'=>['nullable','array'],'exclude_rows.*'=>['integer'],'force_duplicates'=>['nullable','array'],'force_duplicates.*'=>['integer'],'duplicate_acknowledgment'=>['nullable','boolean']]);
  if(($data['force_duplicates']??[])!==[]&&!$request->boolean('duplicate_acknowledgment'))throw ValidationException::withMessages(['duplicate_acknowledgment'=>'Acknowledge the duplicate invoices before continuing.']);
  $forces=array_map('intval',$data['force_duplicates']??[]);
  $batch=$this->service->issue($propertyTaxBatch,$request->user(),array_map('intval',$data['include_drafts']??[]),array_map('intval',$data['exclude_rows']??[]),$forces);
  AuditLog::create(['actor_type'=>'administrator','actor_user_id'=>$request->user()->id,'event'=>'property_tax_batch.issued','auditable_type'=>PropertyTaxBatch::class,'auditable_id'=>$batch->id,'after_values'=>['status'=>$batch->status,'invoice_count'=>$batch->rows->whereNotNull('invoice_id')->count(),'duplicate_overrides'=>count($forces)],'ip_address'=>$request->ip(),'user_agent'=>str($request->userAgent())->limit(500)]);
  return redirect()->route('admin.property-tax-batches.show',$batch)->with('success','Property-tax batch processed. Invoice and email results are shown below.');
 }
 public function retryEmail(Request $request,PropertyTaxBatch $propertyTaxBatch,PropertyTaxBatchRow $row):RedirectResponse
 {
  abort_unless($row->property_tax_batch_id===$propertyTaxBatch->id,404);
  try{$this->service->retryEmail($row,$request->user());return back()->with('success','Invoice email sent.');}
  catch(\Throwable){return back()->withErrors(['email'=>'Invoice email could not be sent. The invoice remains valid; review the row result below.']);}
 }
 private function form(PropertyTaxBatch $batch):View{return view('admin.property-taxes.form',compact('batch'));}
 private function save(Request $request,PropertyTaxBatch $batch):RedirectResponse
 {
  $data=$request->validate(['tax_year'=>['required','integer','min:2000','max:2100'],'label'=>['nullable','string','max:100'],'issue_date'=>['required','date'],'due_date'=>['required','date','after_or_equal:issue_date'],'fallback_description'=>['nullable','string','max:500'],'email_clients'=>['nullable','boolean'],'source_text'=>['nullable','string'],'csv_file'=>['nullable','file','max:5120']]);
  if(filled($data['source_text']??null)&&$request->hasFile('csv_file'))throw ValidationException::withMessages(['source_text'=>'Paste data or upload a CSV, not both.']);
  $text=$request->hasFile('csv_file')?(string)file_get_contents($request->file('csv_file')->getRealPath()):(string)($data['source_text']??'');
  if(trim($text)==='')throw ValidationException::withMessages(['source_text'=>'Paste property-tax data or upload a CSV file.']);
  $rows=$this->service->rows($text,$data['fallback_description']??null,(int)$data['tax_year']);
  if($rows===[])throw ValidationException::withMessages(['source_text'=>'No data rows were found.']);
  DB::transaction(function()use($batch,$data,$text,$rows,$request){
   $batch->fill(['tax_year'=>$data['tax_year'],'label'=>trim($data['label']??'Annual')?:'Annual','issue_date'=>$data['issue_date'],'due_date'=>$data['due_date'],'fallback_description'=>trim((string)($data['fallback_description']??''))?:null,'email_clients'=>$request->boolean('email_clients'),'source_filename'=>$request->file('csv_file')?->getClientOriginalName(),'source_text'=>$text,'status'=>'draft','created_by_user_id'=>$batch->created_by_user_id?:$request->user()->id])->save();
   $batch->rows()->delete();$batch->rows()->createMany($rows);
  });
  AuditLog::create(['actor_type'=>'administrator','actor_user_id'=>$request->user()->id,'event'=>'property_tax_batch.saved','auditable_type'=>PropertyTaxBatch::class,'auditable_id'=>$batch->id,'after_values'=>['status'=>'draft','tax_year'=>$batch->tax_year,'row_count'=>count($rows)],'ip_address'=>$request->ip(),'user_agent'=>str($request->userAgent())->limit(500)]);
  return redirect()->route('admin.property-tax-batches.show',$batch)->with('success','Draft property-tax batch saved. No invoices have been created.');
 }
}
