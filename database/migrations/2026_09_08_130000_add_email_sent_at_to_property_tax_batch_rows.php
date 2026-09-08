<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void{Schema::table('property_tax_batch_rows',fn(Blueprint $t)=>$t->timestamp('email_sent_at')->nullable()->after('email_status'));}
 public function down():void{Schema::table('property_tax_batch_rows',fn(Blueprint $t)=>$t->dropColumn('email_sent_at'));}
};
