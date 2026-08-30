<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_payment_intents', function (Blueprint $table): void {
            $table->unsignedBigInteger('base_amount')->nullable()->after('amount');
            $table->unsignedBigInteger('processing_fee_amount')->default(0)->after('base_amount');
            $table->string('card_type', 20)->nullable()->after('processing_fee_amount');
        });
    }

    public function down(): void
    {
        Schema::table('client_payment_intents', fn (Blueprint $table) => $table->dropColumn(['base_amount', 'processing_fee_amount', 'card_type']));
    }
};
