<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropForeign(['replaces_invoice_id']);
            $table->dropIndex(['replaces_invoice_id', 'status']);
            $table->dropColumn('replaces_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->foreignId('replaces_invoice_id')->nullable()->after('payment_plan_id')->constrained('invoices')->restrictOnDelete();
            $table->index(['replaces_invoice_id', 'status']);
        });
    }
};
