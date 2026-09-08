<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('property_tax_batches',function(Blueprint $t):void{$t->id();$t->uuid('uuid')->unique();$t->unsignedSmallInteger('tax_year');$t->date('issue_date');$t->date('due_date');$t->string('fallback_description',500)->nullable();$t->boolean('email_clients')->default(true);$t->string('source_filename')->nullable();$t->longText('source_text');$t->string('status',24)->default('draft')->index();$t->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();$t->foreignId('confirmed_by_user_id')->nullable()->constrained('users')->restrictOnDelete();$t->timestamp('confirmed_at')->nullable();$t->timestamps();});
  Schema::table('invoices',function(Blueprint $t):void{$t->unsignedSmallInteger('property_tax_year')->nullable()->after('generation_source');$t->index(['payment_plan_id','property_tax_year']);});
  Schema::create('property_tax_batch_rows',function(Blueprint $t):void{$t->id();$t->foreignId('property_tax_batch_id')->constrained()->cascadeOnDelete();$t->unsignedInteger('row_number');$t->string('original_apn',100)->nullable();$t->string('normalized_apn',100)->nullable()->index();$t->unsignedBigInteger('amount')->nullable();$t->string('imported_description',500)->nullable();$t->string('resolved_description',500)->nullable();$t->foreignId('payment_plan_id')->nullable()->constrained()->restrictOnDelete();$t->string('match_status',24)->index();$t->string('note',500)->nullable();$t->foreignId('invoice_id')->nullable()->constrained()->restrictOnDelete();$t->string('email_status',24)->nullable();$t->string('email_note',500)->nullable();$t->timestamps();$t->unique(['property_tax_batch_id','row_number']);});
 }
 public function down(): void {Schema::dropIfExists('property_tax_batch_rows');Schema::table('invoices',function(Blueprint $t):void{$t->dropIndex(['payment_plan_id','property_tax_year']);$t->dropColumn('property_tax_year');});Schema::dropIfExists('property_tax_batches');}
};
