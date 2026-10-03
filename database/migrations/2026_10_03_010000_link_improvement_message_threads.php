<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('secure_message_threads', function (Blueprint $table) {
            $table->foreignId('improvement_id')->nullable()->unique()->constrained()->restrictOnDelete();
        });
    }
    public function down(): void {
        Schema::table('secure_message_threads', function (Blueprint $table) {
            $table->dropForeign(['improvement_id']);
            $table->dropUnique(['improvement_id']);
            $table->dropColumn('improvement_id');
        });
    }
};
