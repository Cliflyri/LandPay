<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_fee_rules', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('payment_plan_id')->constrained()->restrictOnDelete();
            $table->string('name', 150);
            $table->unsignedBigInteger('amount');
            $table->string('frequency', 24)->default('monthly');
            $table->unsignedTinyInteger('due_day');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('status', 24)->default('active');
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['payment_plan_id', 'status', 'effective_from']);
        });

        Schema::create('fee_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('recurring_fee_rule_id')->constrained()->restrictOnDelete();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->foreignId('invoice_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('financial_transaction_id')->unique()->constrained()->restrictOnDelete();
            $table->string('period_key', 20);
            $table->date('effective_date');
            $table->unsignedBigInteger('amount');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['payment_plan_id', 'recurring_fee_rule_id', 'period_key'], 'fee_assessments_plan_rule_period_unique');
            $table->index(['invoice_id', 'effective_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_assessments');
        Schema::dropIfExists('recurring_fee_rules');
    }
};