<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_allocations', function (Blueprint $table) {
            $table->date('billing_month')->nullable()->after('fee_assessment_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('payment_allocations', function (Blueprint $table) {
            $table->dropColumn('billing_month');
        });
    }
};