<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void{
  Schema::create('client_announcements',function(Blueprint $t):void{
   $t->id();$t->uuid('uuid')->unique();$t->string('title',150);$t->text('body');$t->string('severity',20)->default('information');
   $t->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();$t->foreignId('published_by_user_id')->nullable()->constrained('users')->nullOnDelete();
   $t->timestamp('published_at')->nullable()->index();$t->timestamp('starts_at')->nullable()->index();$t->timestamp('ends_at')->nullable()->index();
   $t->timestamp('deactivated_at')->nullable()->index();$t->timestamp('removed_from_clients_at')->nullable()->index();
   $t->boolean('send_email')->default(true);$t->timestamp('recipients_created_at')->nullable();$t->timestamps();
  });
  Schema::create('client_announcement_recipients',function(Blueprint $t):void{
   $t->id();$t->foreignId('client_announcement_id')->constrained()->cascadeOnDelete();$t->foreignId('client_id')->constrained()->restrictOnDelete();
   $t->foreignId('portal_account_id')->nullable()->constrained()->nullOnDelete();$t->string('email',254)->nullable();$t->string('phone',40)->nullable();
   $t->string('email_status',20)->default('not_requested');$t->timestamp('email_sent_at')->nullable();$t->timestamp('email_failed_at')->nullable();
   $t->timestamp('first_viewed_at')->nullable();$t->timestamp('dismissed_at')->nullable();$t->timestamp('acknowledged_at')->nullable();$t->timestamps();
   $t->unique(['client_announcement_id','client_id'],'announcement_client_unique');
  });
 }
 public function down():void{Schema::dropIfExists('client_announcement_recipients');Schema::dropIfExists('client_announcements');}
};
