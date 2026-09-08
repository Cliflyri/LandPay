<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_plans', function (Blueprint $table): void {
            $table->boolean('first_payment_invoice_on_activation')->default(false)->after('first_payment_amount');
        });

        Schema::table('admin_notices', function (Blueprint $table): void {
            $table->foreignId('payment_plan_id')->nullable()->after('client_id')->constrained()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('admin_notices', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('payment_plan_id');
        });

        Schema::table('payment_plans', function (Blueprint $table): void {
            $table->dropColumn('first_payment_invoice_on_activation');
        });
    }
};
