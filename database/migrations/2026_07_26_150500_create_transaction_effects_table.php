<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_effects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_transaction_id')->constrained()->restrictOnDelete();
            $table->string('effect_type', 32);
            $table->foreignId('invoice_id')->nullable()->constrained()->restrictOnDelete();
            $table->bigInteger('amount_delta');
            $table->string('component', 40);
            $table->foreignId('invoice_item_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('fee_assessment_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('description', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['financial_transaction_id', 'id']);
            $table->index(['invoice_id', 'effect_type', 'id']);
            $table->index(['effect_type', 'financial_transaction_id']);
            $table->index('invoice_item_id');
            $table->index('fee_assessment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_effects');
    }
};