<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('secure_message_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('secure_message_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->foreignId('edited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }
    public function down(): void { Schema::dropIfExists('secure_message_revisions'); }
};
