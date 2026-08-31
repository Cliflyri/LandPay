<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoice_access_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->text('token_encrypted');
            $table->timestamp('expires_at')->index();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('client_payment_intents', function (Blueprint $table): void {
            $table->foreignId('invoice_id')->nullable()->after('payment_plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('portal_account_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('client_payment_intents', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('invoice_id');
            $table->foreignId('portal_account_id')->nullable(false)->change();
        });
        Schema::dropIfExists('invoice_access_links');
    }
};
