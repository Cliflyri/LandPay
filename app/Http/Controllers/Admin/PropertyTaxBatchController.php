<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{AuditLog,PaymentPlan,PropertyTaxBatch,PropertyTaxBatchRow};
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
  $unmatchedPlans=PaymentPlan::whereIn('status',['active','paused'])
   ->whereNotIn('id',$propertyTaxBatch->rows->whereIn('match_status',['matched','draft'])->pluck('payment_plan_id')->filter()->unique())
   ->with(['memberships'=>fn($q)=>$q->whereNull('effective_to')->with('client')])
   ->orderBy('plan_number')->get();
  $countyKey=mb_strtolower(trim((string)$propertyTaxBatch->property_county));
  $sameCountyPlanIds=$countyKey===''?collect():$unmatchedPlans->filter(fn($plan)=>mb_strtolower(trim((string)$plan->property_county))===$countyKey)->pluck('id');
  if($sameCountyPlanIds->isNotEmpty())$unmatchedPlans=$unmatchedPlans->sortBy(fn($plan)=>$sameCountyPlanIds->contains($plan->id)?0:1)->values();
  $matchCandidates=[];
  if($propertyTaxBatch->status==='draft'){
   $reviewRows=$propertyTaxBatch->rows->filter(fn($row)=>$row->match_status==='ambiguous'||str_starts_with((string)$row->note,'Manually selected'));
   if($reviewRows->isNotEmpty()){
    $plans=PaymentPlan::whereIn('status',['active','paused','draft'])->with(['memberships'=>fn($q)=>$q->whereNull('effective_to')->with('client')])->get();
    foreach($reviewRows as $row)$matchCandidates[$row->id]=$this->service->candidates($row->original_apn,$plans);
   }
  }
  return view('admin.property-taxes.show',['batch'=>$propertyTaxBatch,'unmatchedPlans'=>$unmatchedPlans,'matchCandidates'=>$matchCandidates,'sameCountyPlanIds'=>$sameCountyPlanIds]);
 }
 public function saveMatch(Request $request,PropertyTaxBatch $propertyTaxBatch,PropertyTaxBatchRow $row):RedirectResponse
 {
  abort_unless($row->property_tax_batch_id===$propertyTaxBatch->id,404);
  $data=$request->validate(['payment_plan_id'=>['required','integer']]);
  DB::transaction(function()use($request,$propertyTaxBatch,$row,$data):void{
   $batch=PropertyTaxBatch::query()->lockForUpdate()->findOrFail($propertyTaxBatch->id);
   $row=PropertyTaxBatchRow::query()->lockForUpdate()->findOrFail($row->id);
   abort_unless($batch->status==='draft'&&!$row->invoice_id,409);
   abort_unless($row->match_status==='ambiguous'||str_starts_with((string)$row->note,'Manually selected'),409);
   $plan=$this->service->candidates($row->original_apn)->firstWhere('id',(int)$data['payment_plan_id']);
   if(!$plan)throw ValidationException::withMessages(['payment_plan_id'=>'Choose a currently eligible plan matching this uploaded APN.']);
   $before=$row->only(['payment_plan_id','match_status','existing_invoice_id','note']);
   $existing=\App\Models\Invoice::where('payment_plan_id',$plan->id)->where('property_tax_year',$batch->tax_year)->where('status','!=','voided')->first();
   $row->update(['payment_plan_id'=>$plan->id,'match_status'=>$plan->status==='draft'?'draft':'matched','existing_invoice_id'=>$existing?->id,'note'=>'Manually selected'.($plan->status==='draft'?'; draft plan must be included to issue.':'.')]);
   AuditLog::create(['actor_type'=>'administrator','actor_user_id'=>$request->user()->id,'event'=>'property_tax_batch.match_selected','auditable_type'=>PropertyTaxBatchRow::class,'auditable_id'=>$row->id,'before_values'=>$before,'after_values'=>$row->only(['payment_plan_id','match_status','existing_invoice_id','note']),'ip_address'=>$request->ip(),'user_agent'=>str($request->userAgent())->limit(500)]);
  });
  return back()->with('success','Match saved. No invoices have been created.');
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
 public function destroy(Request $request,PropertyTaxBatch $propertyTaxBatch):RedirectResponse
 {
  DB::transaction(function()use($request,$propertyTaxBatch):void{
   $batch=PropertyTaxBatch::query()->lockForUpdate()->findOrFail($propertyTaxBatch->id);
   abort_unless($batch->status==='draft',409);
   AuditLog::create(['actor_type'=>'administrator','actor_user_id'=>$request->user()->id,'event'=>'property_tax_batch.deleted','auditable_type'=>PropertyTaxBatch::class,'auditable_id'=>$batch->id,'before_values'=>['status'=>$batch->status,'tax_year'=>$batch->tax_year,'label'=>$batch->label,'row_count'=>$batch->rows()->count()],'ip_address'=>$request->ip(),'user_agent'=>str($request->userAgent())->limit(500)]);
   $batch->delete();
  });
  return redirect()->route('admin.property-tax-batches.index',['status'=>'draft'])->with('success','Draft property-tax batch deleted.');
 }
 private function countySuggestions():\Illuminate\Support\Collection
 {
  return PaymentPlan::whereNotNull('property_county')->distinct()->orderBy('property_county')->pluck('property_county')
   ->map(fn($county)=>trim($county))->filter()->unique(fn($county)=>mb_strtolower($county))->values();
 }
 private function form(PropertyTaxBatch $batch):View{return view('admin.property-taxes.form',['batch'=>$batch,'counties'=>$this->countySuggestions()]);}
 private function save(Request $request,PropertyTaxBatch $batch):RedirectResponse
 {
  $data=$request->validate(['tax_year'=>['required','integer','min:2000','max:2100'],'label'=>['nullable','string','max:100'],'property_county'=>['nullable','string','max:100'],'issue_date'=>['required','date'],'due_date'=>['required','date','after_or_equal:issue_date'],'fallback_description'=>['nullable','string','max:500'],'email_clients'=>['nullable','boolean'],'source_text'=>['nullable','string'],'csv_file'=>['nullable','file','max:5120']]);
  if(filled($data['source_text']??null)&&$request->hasFile('csv_file'))throw ValidationException::withMessages(['source_text'=>'Paste data or upload a CSV, not both.']);
  $text=$request->hasFile('csv_file')?(string)file_get_contents($request->file('csv_file')->getRealPath()):(string)($data['source_text']??'');
  if(trim($text)==='')throw ValidationException::withMessages(['source_text'=>'Paste property-tax data or upload a CSV file.']);
  $county=trim((string)($data['property_county']??$batch->property_county));
  if(array_key_exists('property_county',$data)&&$data['property_county']===null)$county='';
  $data['property_county']=$county===''?null:($this->countySuggestions()->first(fn($existing)=>mb_strtolower($existing)===mb_strtolower($county))??$county);
  $rows=$this->service->rows($text,$data['fallback_description']??null,(int)$data['tax_year']);
  if($rows===[])throw ValidationException::withMessages(['source_text'=>'No data rows were found.']);
  DB::transaction(function()use($batch,$data,$text,$rows,$request){
   $batch->fill(['tax_year'=>$data['tax_year'],'property_county'=>$data['property_county'],'label'=>trim($data['label']??'Annual')?:'Annual','issue_date'=>$data['issue_date'],'due_date'=>$data['due_date'],'fallback_description'=>trim((string)($data['fallback_description']??''))?:null,'email_clients'=>$request->boolean('email_clients'),'source_filename'=>$request->file('csv_file')?->getClientOriginalName(),'source_text'=>$text,'status'=>'draft','created_by_user_id'=>$batch->created_by_user_id?:$request->user()->id])->save();
   $batch->rows()->delete();$batch->rows()->createMany($rows);
  });
  AuditLog::create(['actor_type'=>'administrator','actor_user_id'=>$request->user()->id,'event'=>'property_tax_batch.saved','auditable_type'=>PropertyTaxBatch::class,'auditable_id'=>$batch->id,'after_values'=>['status'=>'draft','tax_year'=>$batch->tax_year,'property_county'=>$batch->property_county,'row_count'=>count($rows)],'ip_address'=>$request->ip(),'user_agent'=>str($request->userAgent())->limit(500)]);
  return redirect()->route('admin.property-tax-batches.show',$batch)->with('success','Draft property-tax batch saved. No invoices have been created.');
 }
}
