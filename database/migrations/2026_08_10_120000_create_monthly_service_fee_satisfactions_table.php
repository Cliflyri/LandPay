<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A failed first run may have created the table before MySQL rejected its generated index name.
        Schema::dropIfExists('monthly_service_fee_satisfactions');

        Schema::create('monthly_service_fee_satisfactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_plan_id')->constrained()->restrictOnDelete();
            $table->date('billing_month');
            $table->string('note', 500);
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->foreignId('revoked_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->unique(['payment_plan_id', 'billing_month'], 'service_fee_satisfaction_plan_month_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_service_fee_satisfactions');
    }
};