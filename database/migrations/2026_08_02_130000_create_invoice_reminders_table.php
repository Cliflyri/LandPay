<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_reminders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('recipient_client_id')->nullable()->constrained('clients')->restrictOnDelete();
            $table->string('recipient_email', 254);
            $table->unsignedBigInteger('balance_snapshot');
            $table->string('status', 24)->default('pending');
            $table->foreignId('sent_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('failure_message', 500)->nullable();
            $table->timestamps();
            $table->index(['invoice_id', 'created_at']);
            $table->index(['payment_plan_id', 'sent_at']);
            $table->index(['recipient_email', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_reminders');
    }
};
