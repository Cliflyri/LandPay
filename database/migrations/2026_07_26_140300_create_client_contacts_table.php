<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_contacts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_plan_id')->nullable()->constrained()->restrictOnDelete();
            $table->boolean('is_general_contact')->default(false);
            $table->boolean('is_emergency_contact')->default(false);
            $table->boolean('is_continuity_contact')->default(false);
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('relationship', 100)->nullable();
            $table->string('email', 254)->nullable();
            $table->string('primary_phone', 32)->nullable();
            $table->string('secondary_phone', 32)->nullable();
            $table->string('address_line_1', 150)->nullable();
            $table->string('address_line_2', 150)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state_region', 100)->nullable();
            $table->string('postal_code', 24)->nullable();
            $table->char('country_code', 2)->default('US');
            $table->string('preferred_contact_method', 24)->nullable();
            $table->unsignedSmallInteger('priority')->default(1);
            $table->string('permission_scope', 24)->default('contact_only');
            $table->string('status', 24)->default('active');
            $table->date('effective_from');
            $table->timestamp('ended_at')->nullable();
            $table->string('end_reason', 255)->nullable();
            $table->unsignedBigInteger('replaced_by_contact_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->foreign('replaced_by_contact_id')->references('id')->on('client_contacts')->restrictOnDelete();
            $table->index(['client_id', 'status', 'priority']);
            $table->index(['payment_plan_id', 'status', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_contacts');
    }
};