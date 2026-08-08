<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_plans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('plan_number', 40)->unique();
            $table->string('title', 180);
            $table->text('asset_description')->nullable();
            $table->unsignedBigInteger('original_purchase_balance');
            $table->unsignedBigInteger('customary_monthly_payment');
            $table->unsignedBigInteger('monthly_service_fee')->default(0);
            $table->unsignedTinyInteger('monthly_due_day')->index();
            $table->date('first_due_date')->index();
            $table->date('plan_start_date');
            $table->date('maturity_date')->nullable();
            $table->unsignedSmallInteger('grace_period_days')->default(0);
            $table->string('status', 24)->default('draft')->index();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_plans');
    }
};