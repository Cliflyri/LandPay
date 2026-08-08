<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_transaction_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('payer_client_id')->nullable()->constrained('clients')->restrictOnDelete();
            $table->date('received_date');
            $table->string('payment_method', 32);
            $table->string('external_reference', 150)->nullable();
            $table->unsignedBigInteger('gross_amount');
            $table->unsignedBigInteger('current_invoice_amount');
            $table->unsignedBigInteger('overpayment_amount')->default(0);
            $table->string('overpayment_disposition', 32)->nullable();
            $table->string('decision_source', 32)->nullable();
            $table->timestamp('decision_selected_at')->nullable();
            $table->foreignId('instruction_recorded_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['payer_client_id', 'received_date']);
            $table->index(['payment_method', 'external_reference']);
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->string('allocation_type', 32);
            $table->foreignId('invoice_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('invoice_item_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('fee_assessment_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->unsignedSmallInteger('display_order')->default(1);
            $table->timestamp('created_at')->useCurrent();
            $table->index(['payment_id', 'display_order']);
            $table->index('invoice_id');
            $table->index('invoice_item_id');
            $table->index('fee_assessment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
    }
};