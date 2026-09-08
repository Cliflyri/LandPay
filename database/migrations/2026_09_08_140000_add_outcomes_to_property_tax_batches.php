<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
 public function up():void{
  Schema::table('property_tax_batches',fn(Blueprint $t)=>$t->string('label',100)->default('Annual')->after('tax_year'));
  Schema::table('property_tax_batch_rows',function(Blueprint $t):void{$t->foreignId('existing_invoice_id')->nullable()->after('invoice_id')->constrained('invoices')->restrictOnDelete();$t->boolean('duplicate_override')->default(false)->after('existing_invoice_id');$t->string('issuance_status',24)->default('pending')->after('match_status');});
  DB::table('property_tax_batch_rows')->whereNotNull('invoice_id')->update(['issuance_status'=>'created']);
  DB::table('property_tax_batch_rows as r')->join('property_tax_batches as b','b.id','=','r.property_tax_batch_id')->whereNull('r.invoice_id')->whereNotNull('r.payment_plan_id')->select('r.id','r.payment_plan_id','b.tax_year')->get()->each(function($row):void{$invoice=DB::table('invoices')->where('payment_plan_id',$row->payment_plan_id)->where('property_tax_year',$row->tax_year)->where('status','!=','voided')->first();if($invoice)DB::table('property_tax_batch_rows')->where('id',$row->id)->update(['existing_invoice_id'=>$invoice->id]);});
  DB::table('property_tax_batch_rows')->whereNull('invoice_id')->whereIn('property_tax_batch_id',DB::table('property_tax_batches')->whereNotNull('confirmed_at')->select('id'))->update(['issuance_status'=>'not_created']);
  DB::table('property_tax_batches')->where('status','partially_issued')->whereNotExists(fn($q)=>$q->selectRaw('1')->from('property_tax_batch_rows')->whereColumn('property_tax_batch_rows.property_tax_batch_id','property_tax_batches.id')->whereNotNull('invoice_id'))->update(['status'=>'not_issued']);
 }
 public function down():void{
  Schema::table('property_tax_batch_rows',function(Blueprint $t):void{$t->dropConstrainedForeignId('existing_invoice_id');$t->dropColumn(['duplicate_override','issuance_status']);});
  Schema::table('property_tax_batches',fn(Blueprint $t)=>$t->dropColumn('label'));
 }
};
