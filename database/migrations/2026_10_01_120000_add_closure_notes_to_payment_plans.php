<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('payment_plans', fn (Blueprint $table) => $table->text('closure_notes')->nullable());
    }
    public function down(): void {
        Schema::table('payment_plans', fn (Blueprint $table) => $table->dropColumn('closure_notes'));
    }
};
