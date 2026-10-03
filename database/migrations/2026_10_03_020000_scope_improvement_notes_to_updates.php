<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('secure_message_threads',function(Blueprint $table){
   $table->index('improvement_id');
   $table->foreignId('improvement_update_id')->nullable()->unique()->constrained()->restrictOnDelete();
  });
  Schema::table('secure_message_threads',fn(Blueprint $table)=>$table->dropUnique(['improvement_id']));
 }
 public function down(): void {
  // Multiple section conversations cannot be merged without losing context.
  if (\Illuminate\Support\Facades\DB::table('secure_message_threads')->whereNotNull('improvement_update_id')->exists()) {
   throw new \RuntimeException('Cannot roll back while section conversations exist.');
  }
  Schema::table('secure_message_threads',function(Blueprint $table){
   $table->unique('improvement_id');
   $table->dropForeign(['improvement_update_id']);
   $table->dropUnique(['improvement_update_id']);
   $table->dropColumn('improvement_update_id');
   $table->dropIndex(['improvement_id']);
  });
 }
};
