<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_deliveries', function (Blueprint $table): void {
            $table->foreignId('payment_id')->nullable()->after('invoice_id')->constrained()->restrictOnDelete();
            $table->index(['payment_id', 'template_slug', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::table('email_deliveries', function (Blueprint $table): void {
            $table->dropIndex(['payment_id', 'template_slug', 'sent_at']);
            $table->dropConstrainedForeignId('payment_id');
        });
    }
};
