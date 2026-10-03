<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('improvements', function (Blueprint $table) {
            $table->id(); $table->uuid('uuid')->unique();
            $table->foreignId('payment_plan_id')->constrained();
            $table->foreignId('client_id')->constrained();
            $table->string('title',150); $table->timestamps();
        });
        Schema::create('improvement_updates', function (Blueprint $table) {
            $table->id(); $table->foreignId('improvement_id')->constrained();
            $table->text('body')->nullable(); $table->json('photos')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->foreignId('received_by_user_id')->nullable()->constrained('users');
            $table->timestamps();
        });
        Schema::table('admin_notices', function (Blueprint $table) {
            $table->foreignId('improvement_update_id')->nullable()->constrained()->nullOnDelete();
        });
    }
    public function down(): void {
        Schema::table('admin_notices', fn (Blueprint $table) => $table->dropConstrainedForeignId('improvement_update_id'));
        Schema::dropIfExists('improvement_updates'); Schema::dropIfExists('improvements');
    }
};
