<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_plans', function (Blueprint $table): void {
            $table->string('apn', 80)->nullable()->after('plan_number')->index();
        });

        DB::table('payment_plans')->whereNull('apn')->update(['apn' => DB::raw('plan_number')]);
    }

    public function down(): void
    {
        Schema::table('payment_plans', function (Blueprint $table): void {
            $table->dropColumn('apn');
        });
    }
};
