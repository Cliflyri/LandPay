<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_payment_intents', function (Blueprint $table): void {
            $table->timestamp('cancelled_at')->nullable()->after('received_at');
        });
    }

    public function down(): void
    {
        Schema::table('client_payment_intents', fn (Blueprint $table) => $table->dropColumn('cancelled_at'));
    }
};
