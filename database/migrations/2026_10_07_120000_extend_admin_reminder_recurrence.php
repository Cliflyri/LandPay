<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void{
  Schema::table('admin_reminders',function(Blueprint $t):void{
   $t->unsignedTinyInteger('day_of_week')->nullable();
   $t->unsignedTinyInteger('month_of_year')->nullable();
  });
 }
 public function down():void{
  Schema::table('admin_reminders',fn(Blueprint $t)=>$t->dropColumn(['day_of_week','month_of_year']));
 }
};
