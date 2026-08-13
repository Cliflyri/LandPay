<?php
namespace App\Services;
use App\Enums\{FinancialActorType,FinancialEffectComponent,FinancialEffectType,FinancialTransactionType,InvoiceItemType,InvoiceStatus,LateFeeType};
use App\Financial\PostingEffect;
use App\Models\{FinancialTransaction,Invoice,InvoiceItem,PaymentPlanBillingTerm,TransactionEffect};
use Illuminate\Support\Carbon;
use Throwable;
class LateFeeAssessmentService {
 public function __construct(private readonly FinancialPostingService $posting){}
 public function run(?Carbon $date=null):array{
  $date??=Carbon::today();$result=['assessed'=>0,'failed'=>0,'skipped'=>0];
  Invoice::query()->with(['paymentPlan','billingTerms','allItems'])->whereIn('status',[InvoiceStatus::Issued->value,InvoiceStatus::PartiallyPaid->value])->whereHas('paymentPlan',fn($q)=>$q->where('status','active'))->chunkById(100,function($invoices)use($date,&$result):void{
   foreach($invoices as $invoice)foreach([1,2] as $stage)try{$this->assess($invoice,$stage,$date)?$result['assessed']++:$result['skipped']++;}catch(Throwable $e){report($e);$result['failed']++;}
  });return $result;
 }
 public function assess(Invoice $invoice,int $stage,Carbon $date):bool{
  $itemType=$stage===1?InvoiceItemType::LateFeeStageOne:InvoiceItemType::LateFeeStageTwo;
  if($invoice->allItems->contains('item_type',$itemType))return false;
  $terms=$invoice->billingTerms??$this->historicalTerms($invoice);$prefix='stage_'.$stage;if(!$terms||!$terms->{$prefix.'_enabled'})return false;
  $assessmentDate=$invoice->due_date->copy()->addDays((int)$terms->{$prefix.'_days_late'});if($date->lt($assessmentDate))return false;
  $unpaid=$this->unpaidScheduledPayment($invoice);if($unpaid<=0)return false;
  $type=$terms->{$prefix.'_fee_type'};$amount=$type===LateFeeType::Fixed?(int)$terms->{$prefix.'_fixed_amount'}:max($this->percentage($unpaid,(string)$terms->{$prefix.'_percentage_rate'}),(int)$terms->{$prefix.'_minimum_amount'});if($amount<=0)return false;
  $description='Late Fee added '.$assessmentDate->format('n/j/y');$component=$stage===1?FinancialEffectComponent::LateFeeStageOne:FinancialEffectComponent::LateFeeStageTwo;
  $this->posting->post($invoice->paymentPlan,FinancialTransactionType::RecurringFee,$amount,$assessmentDate,FinancialActorType::System,function(FinancialTransaction $transaction)use($invoice,$itemType,$stage,$description,$amount,$component):array{
   $item=InvoiceItem::query()->create(['invoice_id'=>$invoice->id,'source_transaction_id'=>$transaction->id,'item_type'=>$itemType,'late_fee_stage'=>'stage_'.$stage,'description'=>$description,'standard_amount'=>$amount,'amount'=>$amount,'waived_amount'=>0,'display_order'=>((int)$invoice->allItems->max('display_order'))+1]);return[new PostingEffect(FinancialEffectType::InvoiceDue,$amount,$component,invoiceId:$invoice->id,invoiceItemId:$item->id,description:$description)];
  },invoice:$invoice,idempotencyKey:'late-fee:'.$invoice->uuid.':stage-'.$stage,description:$description,metadata:['stage'=>$stage,'unpaid_scheduled_payment'=>$unpaid]);return true;
 }
 private function historicalTerms(Invoice $invoice):?PaymentPlanBillingTerm{return PaymentPlanBillingTerm::query()->where('payment_plan_id',$invoice->payment_plan_id)->whereDate('effective_from','<=',$invoice->issue_date)->where(fn($q)=>$q->whereNull('effective_to')->orWhereDate('effective_to','>=',$invoice->issue_date))->latest('effective_from')->first();}
 private function unpaidScheduledPayment(Invoice $invoice):int{return max(0,(int)TransactionEffect::query()->where('invoice_id',$invoice->id)->where('component',FinancialEffectComponent::ScheduledPurchasePayment->value)->sum('amount_delta'));}
 private function percentage(int $amount,string $rate):int{return intdiv(($amount*(int)round((float)$rate*10000))+500000,1000000);}
}
