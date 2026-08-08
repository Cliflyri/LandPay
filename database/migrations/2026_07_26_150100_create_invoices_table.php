<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('payment_plan_id')->constrained()->restrictOnDelete();
            $table->string('invoice_number', 50)->unique();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->date('issue_date');
            $table->date('due_date');
            $table->string('status', 32)->default('draft');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('operationally_closed_at')->nullable();
            $table->timestamp('reopened_at')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['payment_plan_id', 'due_date']);
            $table->index(['payment_plan_id', 'status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};