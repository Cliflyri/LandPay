<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_plan_clients', function (Blueprint $table): void {
            $table->string('contact_risk_acknowledgment_method', 64)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('payment_plan_clients', function (Blueprint $table): void {
            $table->string('contact_risk_acknowledgment_method', 32)->nullable()->change();
        });
    }
};
