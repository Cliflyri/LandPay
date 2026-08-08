<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_plans', function (Blueprint $table): void {
            $table->boolean('accelerated_testing_mode')
                ->default(false)
                ->after('automatic_invoice_email_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('payment_plans', function (Blueprint $table): void {
            $table->dropColumn('accelerated_testing_mode');
        });
    }
};