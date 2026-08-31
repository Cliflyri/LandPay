<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void{Schema::table('invoices',fn(Blueprint $t)=>$t->timestamp('first_viewed_at')->nullable()->index());Schema::table('admin_notices',fn(Blueprint $t)=>$t->foreignId('invoice_id')->nullable()->constrained()->restrictOnDelete());}
 public function down():void{Schema::table('admin_notices',fn(Blueprint $t)=>$t->dropConstrainedForeignId('invoice_id'));Schema::table('invoices',fn(Blueprint $t)=>$t->dropColumn('first_viewed_at'));}
};