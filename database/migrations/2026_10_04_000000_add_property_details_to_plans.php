<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::table('payment_plans',function(Blueprint $table){
  $table->decimal('property_latitude',10,7)->nullable();
  $table->decimal('property_longitude',10,7)->nullable();
  $table->text('property_notes')->nullable();
  $table->json('property_photos')->nullable();
 });}
 public function down(): void { Schema::table('payment_plans',fn(Blueprint $table)=>$table->dropColumn(['property_latitude','property_longitude','property_notes','property_photos']));}
};
