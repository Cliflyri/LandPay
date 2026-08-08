<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->foreignId('source_transaction_id')->constrained('financial_transactions')->restrictOnDelete();
            $table->string('item_type', 40);
            $table->string('late_fee_stage', 16)->nullable();
            $table->string('description', 500);
            $table->unsignedBigInteger('standard_amount');
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('waived_amount')->default(0);
            $table->string('waiver_reason', 500)->nullable();
            $table->foreignId('waived_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('waived_at')->nullable();
            $table->unsignedSmallInteger('display_order')->default(1);
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['invoice_id', 'late_fee_stage']);
            $table->index(['invoice_id', 'display_order']);
            $table->index(['invoice_id', 'item_type']);
            $table->index('source_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};