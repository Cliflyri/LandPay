<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_closings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_plan_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status', 24)->default('review');
            $table->unsignedInteger('version')->default(0);
            $table->timestamp('eligible_at')->nullable();
            $table->boolean('show_hold_notice')->default(true);
            $table->text('client_note')->nullable();
            $table->json('details')->nullable();
            $table->string('details_status', 24)->default('needed');
            $table->string('extras_status', 24)->default('needed');
            $table->string('forms_status', 24)->default('needed');
            $table->string('extras_choice', 24)->nullable();
            $table->text('extras_comments')->nullable();
            $table->text('admin_notes')->nullable();
            $table->text('vesting_notes')->nullable();
            $table->json('signing_instructions')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('paperwork_accepted_at')->nullable();
            $table->date('ready_on')->nullable();
            $table->date('submitted_on')->nullable();
            $table->date('recorded_on')->nullable();
            $table->string('recording_reference')->nullable();
            $table->timestamps();
        });
        Schema::create('closing_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_closing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100);
            $table->json('context')->nullable();
            $table->timestamps();
        });
        Schema::create('closing_notice_dismissals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_closing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['plan_closing_id', 'user_id']);
        });
        Schema::create('closing_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_closing_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 24);
            $table->string('name');
            $table->string('path');
            $table->timestamps();
        });
        Schema::table('secure_message_threads', function (Blueprint $table) {
            $table->foreignId('plan_closing_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unique(['plan_closing_id', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::table('secure_message_threads', function (Blueprint $table) {
            $table->dropForeign(['plan_closing_id']);
            $table->dropUnique(['plan_closing_id', 'client_id']);
            $table->dropColumn('plan_closing_id');
        });
        Schema::dropIfExists('closing_documents');
        Schema::dropIfExists('closing_notice_dismissals');
        Schema::dropIfExists('closing_events');
        Schema::dropIfExists('plan_closings');
    }
};
