<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_plans', function (Blueprint $table): void {
            $table->boolean('automated_reminders_enabled')->default(true)->after('status');
        });
        Schema::table('invoice_reminders', function (Blueprint $table): void {
            $table->boolean('automated')->default(false)->after('status');
            $table->date('trigger_date')->nullable()->after('automated');
            $table->string('trigger_type', 32)->nullable()->after('trigger_date');
            $table->foreignId('sent_by_user_id')->nullable()->change();
            $table->index(['automated', 'trigger_date', 'status']);
            $table->unique(['invoice_id', 'trigger_date', 'trigger_type'], 'invoice_reminder_trigger_unique');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_reminders', function (Blueprint $table): void {
            $table->dropUnique('invoice_reminder_trigger_unique');
            $table->dropIndex(['automated', 'trigger_date', 'status']);
            $table->foreignId('sent_by_user_id')->nullable(false)->change();
            $table->dropColumn(['automated', 'trigger_date', 'trigger_type']);
        });
        Schema::table('payment_plans', function (Blueprint $table): void {
            $table->dropColumn('automated_reminders_enabled');
        });
    }
};
