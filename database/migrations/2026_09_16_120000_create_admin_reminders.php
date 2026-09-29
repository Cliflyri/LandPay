<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void{
  Schema::create('admin_reminders',function(Blueprint $t):void{
   $t->id();$t->uuid('uuid')->unique();$t->string('title',150);$t->text('message')->nullable();
   $t->string('recurrence_type',20)->default('monthly')->index();$t->unsignedTinyInteger('day_of_month');$t->time('display_time');$t->string('destination',80)->default('contracts_report');
   $t->boolean('send_email')->default(false);$t->boolean('active')->default(true)->index();
   $t->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
   $t->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();$t->timestamps();
  });
  Schema::create('admin_reminder_occurrences',function(Blueprint $t):void{
   $t->id();$t->foreignId('admin_reminder_id')->constrained()->cascadeOnDelete();$t->char('period',7);$t->timestamp('due_at');
   $t->timestamp('email_processed_at')->nullable();$t->timestamp('email_sent_at')->nullable();$t->timestamps();
   $t->unique(['admin_reminder_id','period'],'admin_reminder_period_unique');
  });
  Schema::create('admin_reminder_dismissals',function(Blueprint $t):void{
   $t->id();$t->foreignId('admin_reminder_occurrence_id')->constrained()->cascadeOnDelete();
   $t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->timestamp('dismissed_at');$t->timestamps();
   $t->unique(['admin_reminder_occurrence_id','user_id'],'admin_reminder_user_unique');
  });
 }
 public function down():void{
  Schema::dropIfExists('admin_reminder_dismissals');Schema::dropIfExists('admin_reminder_occurrences');Schema::dropIfExists('admin_reminders');
 }
};
