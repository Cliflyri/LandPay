<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('portal_invitations',function(Blueprint $table){$table->id();$table->foreignId('client_id')->constrained()->restrictOnDelete();$table->foreignId('invited_by_user_id')->constrained('users')->restrictOnDelete();$table->string('email',254);$table->char('token_hash',64)->unique();$table->timestamp('expires_at')->index();$table->timestamp('accepted_at')->nullable();$table->timestamp('revoked_at')->nullable();$table->timestamps();$table->index(['client_id','created_at']);});
  Schema::create('client_change_requests',function(Blueprint $table){$table->id();$table->foreignId('client_id')->constrained()->restrictOnDelete();$table->foreignId('portal_account_id')->constrained()->restrictOnDelete();$table->json('changes');$table->string('status',24)->default('pending')->index();$table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->restrictOnDelete();$table->timestamp('reviewed_at')->nullable();$table->string('admin_note',500)->nullable();$table->timestamps();});
  Schema::create('admin_notices',function(Blueprint $table){$table->id();$table->string('type',50)->index();$table->foreignId('client_id')->nullable()->constrained()->restrictOnDelete();$table->foreignId('client_change_request_id')->nullable()->constrained()->restrictOnDelete();$table->string('title',180);$table->text('message');$table->foreignId('dismissed_by_user_id')->nullable()->constrained('users')->restrictOnDelete();$table->timestamp('dismissed_at')->nullable()->index();$table->timestamps();});
 }
 public function down(): void {Schema::dropIfExists('admin_notices');Schema::dropIfExists('client_change_requests');Schema::dropIfExists('portal_invitations');}
};
