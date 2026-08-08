<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_plan_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->string('role', 24);
            $table->string('responsibility', 24)->default('joint');
            $table->boolean('receives_invoices')->default(true);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('end_reason', 255)->nullable();
            $table->timestamp('contact_risk_acknowledged_at')->nullable();
            $table->string('contact_risk_acknowledgment_method', 32)->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('ended_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['payment_plan_id', 'effective_to']);
            $table->index(['client_id', 'effective_to']);
            $table->index(['payment_plan_id', 'role', 'effective_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_plan_clients');
    }
};