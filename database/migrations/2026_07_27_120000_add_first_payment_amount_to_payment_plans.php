<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_plans', function (Blueprint $table): void {
            $table->unsignedBigInteger('first_payment_amount')->nullable()->after('original_purchase_balance');
            $table->date('first_due_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('payment_plans', function (Blueprint $table): void {
            $table->date('first_due_date')->nullable(false)->change();
            $table->dropColumn('first_payment_amount');
        });
    }
};