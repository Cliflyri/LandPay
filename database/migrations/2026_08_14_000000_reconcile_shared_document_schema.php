<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration{
 public function up():void{
  if(!Schema::hasColumn('shared_documents','client_viewed_at'))Schema::table('shared_documents',function(Blueprint $table):void{$table->timestamp('client_viewed_at')->nullable()->after('visible_to_client');$table->timestamp('client_downloaded_at')->nullable()->after('client_viewed_at');});
  if(!Schema::hasTable('secure_message_documents'))Schema::create('secure_message_documents',function(Blueprint $table):void{$table->foreignId('secure_message_id')->constrained()->cascadeOnDelete();$table->foreignId('shared_document_id')->constrained()->cascadeOnDelete();$table->timestamps();$table->primary(['secure_message_id','shared_document_id']);});
  if(!Schema::hasTable('secure_message_attachments'))Schema::create('secure_message_attachments',function(Blueprint $table):void{$table->id();$table->uuid('uuid')->unique();$table->foreignId('secure_message_id')->constrained()->cascadeOnDelete();$table->string('disk',30)->default('local');$table->string('path');$table->string('name');$table->string('mime',100);$table->unsignedBigInteger('size');$table->timestamp('client_downloaded_at')->nullable();$table->timestamps();});
 }
 public function down():void{}
};
