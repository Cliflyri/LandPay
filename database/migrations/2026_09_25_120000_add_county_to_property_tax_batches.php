<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up():void {Schema::table('property_tax_batches',fn(Blueprint $table)=>$table->string('property_county',100)->nullable());}
 public function down():void {Schema::table('property_tax_batches',fn(Blueprint $table)=>$table->dropColumn('property_county'));}
};
