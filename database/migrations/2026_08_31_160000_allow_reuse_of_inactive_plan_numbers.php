<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_plans', function (Blueprint $table): void {
            $table->dropUnique(['plan_number']);
            $table->index('plan_number');
        });
    }

    public function down(): void
    {
        Schema::table('payment_plans', function (Blueprint $table): void {
            $table->dropIndex(['plan_number']);
            $table->unique('plan_number');
        });
    }
};
