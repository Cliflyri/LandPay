<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('shared_documents',function(Blueprint $table):void{$table->id();$table->uuid('uuid')->unique();$table->foreignId('client_id')->constrained()->restrictOnDelete();$table->foreignId('payment_plan_id')->nullable()->constrained()->nullOnDelete();$table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();$table->foreignId('uploaded_by_client_id')->nullable()->constrained('clients')->nullOnDelete();$table->string('name');$table->string('category',30)->default('general');$table->string('disk',30)->default('local');$table->string('path');$table->string('mime',100);$table->unsignedBigInteger('size');$table->boolean('visible_to_client')->default(true)->index();$table->timestamp('archived_at')->nullable()->index();$table->timestamps();$table->index(['client_id','created_at']);});
  Schema::table('secure_messages',fn(Blueprint $table)=>$table->foreignId('shared_document_id')->nullable()->constrained()->nullOnDelete());
 }
 public function down(): void {Schema::table('secure_messages',fn(Blueprint $table)=>$table->dropConstrainedForeignId('shared_document_id'));Schema::dropIfExists('shared_documents');}
};
