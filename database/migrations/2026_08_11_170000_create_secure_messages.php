<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('secure_message_threads', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject', 150);
            $table->string('category', 30)->default('general');
            $table->timestamp('starred_at')->nullable()->index();
            $table->timestamp('latest_message_at')->nullable()->index();
            $table->timestamp('notification_last_sent_at')->nullable();
            $table->string('notification_status', 20)->nullable();
            $table->unsignedInteger('reminder_count')->default(0);
            $table->timestamps();
        });

        Schema::create('secure_messages', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('secure_message_thread_id')->constrained()->cascadeOnDelete();
            $table->string('sender_type', 20);
            $table->foreignId('sender_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sender_client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->text('body');
            $table->string('attachment_disk', 30)->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->string('attachment_mime', 100)->nullable();
            $table->unsignedBigInteger('attachment_size')->nullable();
            $table->timestamp('client_viewed_at')->nullable();
            $table->timestamp('admin_viewed_at')->nullable();
            $table->timestamp('attachment_downloaded_at')->nullable();
            $table->timestamps();
            $table->index(['secure_message_thread_id', 'sender_type']);
        });

        Schema::table('admin_notices', fn (Blueprint $table) =>
            $table->foreignId('secure_message_thread_id')->nullable()->constrained()->restrictOnDelete()
        );
    }

    public function down(): void
    {
        Schema::table('admin_notices', fn (Blueprint $table) => $table->dropConstrainedForeignId('secure_message_thread_id'));
        Schema::dropIfExists('secure_messages');
        Schema::dropIfExists('secure_message_threads');
    }
};
