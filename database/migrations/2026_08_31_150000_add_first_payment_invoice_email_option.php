<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_plans', function (Blueprint $table): void {
            $table->boolean('first_payment_invoice_email_on_activation')->default(false)
                ->after('first_payment_invoice_on_activation');
        });
    }

    public function down(): void
    {
        Schema::table('payment_plans', function (Blueprint $table): void {
            $table->dropColumn('first_payment_invoice_email_on_activation');
        });
    }
};
