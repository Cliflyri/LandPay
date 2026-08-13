<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('invoices',fn(Blueprint $table)=>$table->foreignId('payment_plan_billing_term_id')->nullable()->after('payment_plan_id')->constrained('payment_plan_billing_terms')->restrictOnDelete());
  DB::table('email_templates')->where('slug','payment-reminder')->orderBy('id')->each(function($template):void{if(!str_contains($template->body_html,'{{ late_fee_notice }}'))DB::table('email_templates')->where('id',$template->id)->update(['body_html'=>$template->body_html.'<p>{{ late_fee_notice }}</p>']);});
 }
 public function down(): void {Schema::table('invoices',fn(Blueprint $table)=>$table->dropConstrainedForeignId('payment_plan_billing_term_id'));}
};
