<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_plans', function (Blueprint $table): void {
            $table->unsignedBigInteger('contract_down_payment')->default(0)->after('documentation_fee_waiver_reason');
            $table->date('first_scheduled_invoice_date')->nullable()->index()->after('contract_down_payment');
            $table->string('property_county', 100)->nullable()->after('asset_description');
            $table->unsignedBigInteger('hoa_fee')->default(0)->after('contract_down_payment');
            $table->string('hoa_term', 50)->nullable()->after('hoa_fee');
            $table->boolean('govdeals')->default(false)->after('hoa_term');
        });

        Schema::create('contract_documents', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('payment_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('name');
            $table->string('template_name');
            $table->string('disk', 30)->default('local');
            $table->string('path');
            $table->string('mime', 150);
            $table->unsignedBigInteger('size');
            $table->timestamp('expires_at')->index();
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamp('deleted_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_documents');
        Schema::table('payment_plans', fn (Blueprint $table) => $table->dropColumn([
            'contract_down_payment', 'first_scheduled_invoice_date', 'property_county', 'hoa_fee', 'hoa_term', 'govdeals',
        ]));
    }
};
