<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('email_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('name', 120);
            $table->string('subject', 255);
            $table->longText('body_html');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('email_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('payment_plan_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('recipient_client_id')->nullable()->constrained('clients')->restrictOnDelete();
            $table->foreignId('sent_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('template_slug', 80);
            $table->string('recipient_email', 254);
            $table->string('subject_snapshot', 255);
            $table->longText('body_snapshot');
            $table->string('delivery_format', 20)->default('inline');
            $table->string('status', 24)->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('failure_message', 500)->nullable();
            $table->timestamps();
            $table->index(['invoice_id', 'created_at']);
            $table->index(['template_slug', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_deliveries');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('app_settings');
    }
};
