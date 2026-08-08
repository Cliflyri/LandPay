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
            $table->boolean('automatic_invoice_email_enabled')->default(false);
        });
        Schema::table('invoices', function (Blueprint $table): void {
            $table->string('generation_source', 24)->default('administrator')->index();
        });
        Schema::create('payment_plan_pauses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_plan_id')->constrained()->restrictOnDelete();
            $table->date('pause_date');
            $table->date('planned_resume_date')->nullable();
            $table->date('resume_date')->nullable();
            $table->string('reason', 500);
            $table->string('resume_note', 500)->nullable();
            $table->foreignId('paused_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('resumed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resumed_at')->nullable();
            $table->timestamps();
            $table->index(['payment_plan_id', 'pause_date', 'resume_date']);
        });

        DB::table('payment_plans')->where('status', 'suspended')->update(['status' => 'paused']);
    }

    public function down(): void
    {
        DB::table('payment_plans')->where('status', 'paused')->update(['status' => 'suspended']);
        Schema::dropIfExists('payment_plan_pauses');
        Schema::table('invoices', fn (Blueprint $table) => $table->dropColumn('generation_source'));
        Schema::table('payment_plans', fn (Blueprint $table) => $table->dropColumn('automatic_invoice_email_enabled'));
    }
};
