<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_defaults', function (Blueprint $table) {
            $table->id();
            $table->string('frequency', 24)->default('monthly');
            $table->unsignedTinyInteger('invoice_day')->default(3);
            $table->unsignedSmallInteger('due_days_after_issue')->default(5);
            $table->unsignedSmallInteger('grace_days')->default(0);
            $table->unsignedBigInteger('scheduled_payment_amount')->default(0);
            $table->unsignedBigInteger('monthly_service_fee')->default(0);
            $this->lateFeeColumns($table, 'stage_one', true);
            $this->lateFeeColumns($table, 'stage_two', false);
            $table->unsignedSmallInteger('default_eligibility_days')->default(60);
            $table->json('reminder_settings')->nullable();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('payment_plan_billing_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_plan_id')->constrained()->restrictOnDelete();
            $table->string('frequency', 24)->default('monthly');
            $table->unsignedTinyInteger('invoice_day');
            $table->unsignedSmallInteger('due_days_after_issue');
            $table->unsignedSmallInteger('grace_days')->default(0);
            $table->unsignedBigInteger('scheduled_payment_amount');
            $table->unsignedBigInteger('monthly_service_fee')->default(0);
            $this->lateFeeColumns($table, 'stage_one', true);
            $this->lateFeeColumns($table, 'stage_two', false);
            $table->unsignedSmallInteger('default_eligibility_days')->default(60);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('reason', 500)->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['payment_plan_id', 'effective_to']);
            $table->index(['payment_plan_id', 'effective_from']);
        });

        Schema::create('contract_status_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('payment_plan_id')->constrained()->restrictOnDelete();
            $table->string('event_type', 32);
            $table->timestamp('effective_at');
            $table->string('reason', 500)->nullable();
            $table->foreignId('administrator_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->json('system_eligibility_details')->nullable();
            $table->unsignedBigInteger('contract_balance_snapshot');
            $table->unsignedInteger('open_invoice_count')->default(0);
            $table->unsignedBigInteger('paid_in_value_snapshot')->default(0);
            $table->foreignId('related_prior_event_id')->nullable()->constrained('contract_status_events')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['payment_plan_id', 'effective_at', 'id']);
            $table->index(['event_type', 'effective_at']);
        });
    }

    private function lateFeeColumns(Blueprint $table, string $prefix, bool $enabledDefault): void
    {
        $table->boolean("{$prefix}_enabled")->default($enabledDefault);
        $table->string("{$prefix}_fee_type", 24)->nullable();
        $table->unsignedBigInteger("{$prefix}_fixed_amount")->nullable();
        $table->decimal("{$prefix}_percentage_rate", 7, 4)->nullable();
        $table->unsignedBigInteger("{$prefix}_minimum_amount")->default(0);
        $table->unsignedSmallInteger("{$prefix}_days_late")->nullable();
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_status_events');
        Schema::dropIfExists('payment_plan_billing_terms');
        Schema::dropIfExists('billing_defaults');
    }
};