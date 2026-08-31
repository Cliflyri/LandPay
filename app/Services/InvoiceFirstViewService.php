<?php
namespace App\Services;
use App\Models\AdminNotice;
use App\Models\AppSetting;
use App\Models\Invoice;
use App\Models\PortalAccount;
use App\Models\Client;
use Illuminate\Support\Facades\DB;
class InvoiceFirstViewService{
 public function record(Invoice $invoice,PortalAccount|Client $viewer):void{
  $client=$viewer instanceof PortalAccount?$viewer->client:$viewer;
  $at=now();$first=DB::transaction(function()use($invoice,$client,$at):bool{
   $updated=Invoice::query()->whereKey($invoice->id)->whereNull('first_viewed_at')->update(['first_viewed_at'=>$at,'updated_at'=>$at]);
   if($updated!==1)return false;
   if(AppSetting::valueFor('invoice_view_admin_notice_enabled','0')==='1')AdminNotice::query()->create(['type'=>'invoice_first_viewed','client_id'=>$client->id,'invoice_id'=>$invoice->id,'title'=>'Invoice first viewed','message'=>($client->organization_name ?: trim($client->first_name.' '.$client->last_name)).' first viewed invoice '.$invoice->invoice_number.' on '.$at->format('M j, Y \a\t g:i A').'.']);
   return true;
  },3);
  if(!$first)return;$invoice->setAttribute('first_viewed_at',$at);

 }
}