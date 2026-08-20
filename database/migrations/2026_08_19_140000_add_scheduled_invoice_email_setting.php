<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_plans', function (Blueprint $table): void {
            $table->boolean('scheduled_invoice_email_enabled')->default(true)->after('automated_reminders_enabled');
        });

        DB::table('payment_plans')->update([
            'scheduled_invoice_email_enabled' => DB::raw('automated_reminders_enabled'),
        ]);
    }

    public function down(): void
    {
        Schema::table('payment_plans', function (Blueprint $table): void {
            $table->dropColumn('scheduled_invoice_email_enabled');
        });
    }
};
