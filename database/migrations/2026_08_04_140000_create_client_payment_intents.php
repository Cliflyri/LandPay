<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('client_payment_intents',function(Blueprint $table){
   $table->id();$table->uuid('uuid')->unique();$table->foreignId('payment_plan_id')->constrained()->restrictOnDelete();$table->foreignId('client_id')->constrained()->restrictOnDelete();$table->foreignId('portal_account_id')->constrained()->restrictOnDelete();
   $table->string('method',40);$table->unsignedBigInteger('amount');$table->string('payment_type',30)->default('regular');$table->string('overpayment_disposition',30)->nullable();$table->string('client_reference',150)->nullable();$table->text('client_note')->nullable();
   $table->string('status',30)->default('announced')->index();$table->string('provider',20)->nullable();$table->string('provider_checkout_id',190)->nullable()->unique();$table->string('provider_payment_id',190)->nullable()->unique();$table->text('checkout_url')->nullable();$table->timestamp('expires_at')->nullable();
   $table->foreignId('payment_id')->nullable()->constrained()->restrictOnDelete();$table->timestamp('received_at')->nullable();$table->timestamps();$table->index(['payment_plan_id','status']);$table->index(['client_id','status']);
  });
  Schema::table('admin_notices',function(Blueprint $table){$table->foreignId('client_payment_intent_id')->nullable()->constrained()->restrictOnDelete();});
 }
 public function down(): void {Schema::table('admin_notices',fn(Blueprint $table)=>$table->dropConstrainedForeignId('client_payment_intent_id'));Schema::dropIfExists('client_payment_intents');}
};