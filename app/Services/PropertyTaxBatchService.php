<?php
namespace App\Services;
use App\Enums\{FinancialActorType,FinancialEffectComponent,FinancialEffectType,FinancialTransactionType,InvoiceItemType,InvoiceStatus};
use App\Financial\PostingEffect;
use App\Models\{FinancialTransaction,Invoice,InvoiceItem,PaymentPlan,PropertyTaxBatch,PropertyTaxBatchRow,User};
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class PropertyTaxBatchService
{
 public function __construct(private readonly FinancialPostingService $posting,private readonly AccountCreditApplicationService $credits,private readonly InvoiceEmailService $emails){}

 public function rows(string $text,?string $fallback,int $taxYear):array
 {
  $plans=PaymentPlan::withTrashed()->with(['memberships'=>fn($q)=>$q->whereNull('effective_to')->with('client')])->get();
  $byApn=[];
  foreach($plans as $plan){foreach(array_unique([$this->normalize($plan->apn),$this->normalize($plan->plan_number)]) as $key){if($key!=='')$byApn[$key][]=$plan;}}
  $result=[];$seen=[];
  foreach(preg_split('/\R/',trim($text))?:[] as $line){
   if(trim($line)==='')continue;
   $delimiter=str_contains($line,chr(9))?chr(9):(str_contains($line,'|')?'|':',');
   $fields=array_map('trim',str_getcsv($line,$delimiter));
   $apn=(string)($fields[0]??'');$normalized=$this->normalize($apn);
   if($result===[]&&in_array(strtolower(str_replace([' ','_'],'',$apn)),['apn','propertyapn'],true))continue;
   $description=trim((string)($fields[2]??''));$resolved=$description!==''?$description:trim((string)$fallback);
   $row=['row_number'=>count($result)+1,'original_apn'=>$apn?:null,'normalized_apn'=>$normalized?:null,'amount'=>null,'imported_description'=>$description?:null,'resolved_description'=>$resolved?:null,'payment_plan_id'=>null,'match_status'=>'unmatched','note'=>null];
   try{$row['amount']=Money::toCents((string)($fields[1]??''));if($row['amount']<=0)throw new \InvalidArgumentException();}catch(Throwable){$row['match_status']='invalid';$row['note']='Amount must be greater than zero with no more than two decimal places.';$result[]=$row;continue;}
   if($normalized===''){$row['match_status']='invalid';$row['note']='APN is required.';$result[]=$row;continue;}
   if(isset($seen[$normalized])){$row['match_status']='duplicate';$row['note']='Duplicate APN in this batch.';$result[]=$row;continue;}$seen[$normalized]=true;
   $matches=collect($byApn[$normalized]??[])->unique('id');
   $eligible=$matches->filter(fn($p)=>!$p->trashed()&&in_array($p->status,['active','paused'],true));
   if($eligible->count()>1){$row['match_status']='ambiguous';$row['note']='More than one active or paused plan matches this APN.';}
   elseif($eligible->count()===1){$row['match_status']='matched';$row['payment_plan_id']=$eligible->first()->id;}
   else{
    $drafts=$matches->filter(fn($p)=>!$p->trashed()&&$p->status==='draft');
    if($drafts->count()===1){$row['match_status']='draft';$row['payment_plan_id']=$drafts->first()->id;$row['note']='Draft plan; include manually to issue.';}
    elseif($drafts->count()>1){$row['match_status']='ambiguous';$row['note']='More than one draft plan matches this APN.';}
    elseif($matches->isNotEmpty()){$plan=$matches->first();$row['match_status']='inactive';$row['payment_plan_id']=$plan->id;$row['note']=$plan->trashed()?'Deleted plan; skipped.':ucfirst($plan->status).' plan; skipped.';}
   }
   if($row['payment_plan_id']){
    $existing=Invoice::query()->where('payment_plan_id',$row['payment_plan_id'])->where('property_tax_year',$taxYear)->where('status','!=',InvoiceStatus::Voided->value)->first();
    if($existing){$row['existing_invoice_id']=$existing->id;$row['note']='Existing invoice: '.$existing->invoice_number.'. Skipped unless explicitly overridden.';}
   }
   $result[]=$row;
  }
  return $result;
 }

 public function issue(PropertyTaxBatch $batch,User $actor,array $draftIds=[],array $excludedIds=[],array $forceIds=[]):PropertyTaxBatch
 {
  $batch->refresh();abort_unless($batch->status==='draft',409,'This batch has already been issued.');
  $rows=$batch->rows()->with('paymentPlan')->get();
  foreach($rows as $row){
   $included=$row->match_status==='matched'||($row->match_status==='draft'&&in_array($row->id,$draftIds,true));
   if(!$included){$row->update(['issuance_status'=>$row->match_status==='draft'?'not_included':'not_created']);continue;}
   if(in_array($row->id,$excludedIds,true)){$row->update(['issuance_status'=>'excluded']);continue;}
   $force=in_array($row->id,$forceIds,true);
   if($row->existing_invoice_id&&!$force){$row->update(['issuance_status'=>'not_created']);continue;}
   try{$invoice=$this->issueRow($batch,$row,$actor,$force);$row->update(['invoice_id'=>$invoice->id,'issuance_status'=>'created','duplicate_override'=>$force]);}
   catch(Throwable $e){$row->update(['issuance_status'=>'not_created','note'=>Str::limit($e->getMessage(),500)]);continue;}
   if(!$batch->email_clients){$row->update(['email_status'=>'not_requested']);continue;}
   try{$delivery=$this->emails->send($invoice,$actor,'inline');$row->update(['email_status'=>'sent','email_sent_at'=>$delivery->sent_at??now(),'email_note'=>$delivery->recipient_email]);}
   catch(Throwable $e){$row->update(['email_status'=>str_contains($e->getMessage(),'No valid invoice-recipient')?'ineligible':'failed','email_sent_at'=>null,'email_note'=>Str::limit($e->getMessage(),500)]);}
  }
  $intended=$rows->filter(fn($r)=>$r->match_status==='matched'||($r->match_status==='draft'&&in_array($r->id,$draftIds,true)))->reject(fn($r)=>in_array($r->id,$excludedIds,true));
  $created=$intended->filter(fn($r)=>$r->fresh()->invoice_id!==null)->count();
  $status=$created===0?'not_issued':($created===$intended->count()?'issued':'partially_issued');
  $batch->update(['status'=>$status,'confirmed_by_user_id'=>$actor->id,'confirmed_at'=>now()]);
  return $batch->fresh('rows.invoice');
 }

 public function retryEmail(PropertyTaxBatchRow $row,User $actor):void
 {
  if(!$row->invoice)throw new \RuntimeException('This row has no invoice to email.');
  try{$delivery=$this->emails->send($row->invoice,$actor,'inline');$row->update(['email_status'=>'sent','email_sent_at'=>$delivery->sent_at??now(),'email_note'=>$delivery->recipient_email]);}
  catch(Throwable $e){$row->update(['email_status'=>str_contains($e->getMessage(),'No valid invoice-recipient')?'ineligible':'failed','email_sent_at'=>null,'email_note'=>Str::limit($e->getMessage(),500)]);throw $e;}
 }

 private function issueRow(PropertyTaxBatch $batch,PropertyTaxBatchRow $row,User $actor,bool $force=false):Invoice
 {
  return DB::transaction(function()use($batch,$row,$actor,$force){
   $plan=PaymentPlan::withTrashed()->lockForUpdate()->findOrFail($row->payment_plan_id);
   if($plan->trashed()||!in_array($plan->status,['active','paused','draft'],true))throw new \RuntimeException('Plan is no longer eligible.');
   $existing=Invoice::query()->where('payment_plan_id',$plan->id)->where('property_tax_year',$batch->tax_year)->where('status','!=',InvoiceStatus::Voided->value)->first();
   if($existing&&!$force)throw new \RuntimeException('An active property-tax invoice already exists: '.$existing->invoice_number);
   do{$number='PT-'.$batch->tax_year.'-'.$plan->id.'-'.Str::upper(Str::random(3));}while(Invoice::where('invoice_number',$number)->exists());
   $invoice=Invoice::create(['payment_plan_id'=>$plan->id,'payment_plan_billing_term_id'=>$plan->currentBillingTerms()->value('id'),'invoice_number'=>$number,'issue_date'=>$batch->issue_date,'due_date'=>$batch->due_date,'status'=>InvoiceStatus::Issued,'issued_at'=>now(),'created_by_user_id'=>$actor->id,'generation_source'=>'property_tax','property_tax_year'=>$batch->tax_year]);
   $this->posting->post($plan,FinancialTransactionType::InvoiceCharge,$row->amount,$batch->issue_date,FinancialActorType::Administrator,function(FinancialTransaction $transaction)use($invoice,$row){
    $item=InvoiceItem::create(['invoice_id'=>$invoice->id,'source_transaction_id'=>$transaction->id,'item_type'=>InvoiceItemType::PropertyTax,'description'=>$row->resolved_description??'','standard_amount'=>$row->amount,'amount'=>$row->amount,'waived_amount'=>0,'display_order'=>1]);
    return [new PostingEffect(FinancialEffectType::InvoiceDue,$row->amount,FinancialEffectComponent::PropertyTax,invoiceId:$invoice->id,invoiceItemId:$item->id,description:($item->description?:'Property tax').' due')];
   },actor:$actor,invoice:$invoice,idempotencyKey:'property-tax-row:'.$row->id,description:$row->resolved_description?:'Property tax');
   $this->credits->applyToInvoice($plan,$invoice,$actor,false,'property-tax-row:'.$row->id.':account-credit');
   return $invoice->load('items');
  },3);
 }

 private function normalize(?string $value):string{return strtoupper((string)preg_replace('/[^A-Z0-9]/i','',(string)$value));}
}
