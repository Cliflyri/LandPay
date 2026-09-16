<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('client_sms_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(false)->index();
            $table->string('sms_phone_e164', 16)->nullable()->index();
            $table->timestamp('opted_in_at')->nullable();
            $table->string('opt_in_source', 32)->nullable();
            $table->timestamp('opted_out_at')->nullable();
            $table->string('opt_out_source', 32)->nullable();
            $table->timestamp('stopped_at')->nullable();
            $table->timestamps();
        });

        Schema::create('client_sms_consent_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->boolean('enabled');
            $table->string('source', 32);
            $table->string('phone_snapshot', 16)->nullable();
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('portal_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();
            $table->index(['client_id', 'created_at']);
        });

        Schema::create('sms_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('invoice_reminder_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('payment_plan_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('recipient_client_id')->nullable()->constrained('clients')->restrictOnDelete();
            $table->string('message_type', 40)->index();
            $table->string('recipient_phone', 16);
            $table->text('message_snapshot');
            $table->string('idempotency_key', 191)->unique();
            $table->string('twilio_message_sid', 64)->nullable()->unique();
            $table->string('status', 24)->default('pending')->index();
            $table->foreignId('sent_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('failure_message', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_deliveries');
        Schema::dropIfExists('client_sms_consent_events');
        Schema::dropIfExists('client_sms_preferences');
    }
};
