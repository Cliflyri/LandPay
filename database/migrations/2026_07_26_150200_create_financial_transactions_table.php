<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('payment_plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('type', 40);
            $table->unsignedBigInteger('gross_amount');
            $table->date('effective_date');
            $table->timestamp('posted_at');
            $table->string('description', 500)->nullable();
            $table->string('reason', 500)->nullable();
            $table->string('actor_type', 24);
            $table->foreignId('posted_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('posted_by_client_id')->nullable()->constrained('clients')->restrictOnDelete();
            $table->foreignId('authorized_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('authorized_at')->nullable();
            $table->foreignId('reversal_of_transaction_id')->nullable()->unique()->constrained('financial_transactions')->restrictOnDelete();
            $table->string('idempotency_key', 100)->nullable()->unique();
            $table->string('source_reference', 150)->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['payment_plan_id', 'effective_date', 'id']);
            $table->index(['invoice_id', 'effective_date', 'id']);
            $table->index(['type', 'posted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
    }
};