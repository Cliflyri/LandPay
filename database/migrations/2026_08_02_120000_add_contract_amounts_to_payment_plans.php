<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_plans', function (Blueprint $table): void {
            $table->unsignedBigInteger('purchase_price')->nullable()->after('asset_description');
            $table->unsignedBigInteger('documentation_fee_standard')->nullable()->after('purchase_price');
            $table->unsignedBigInteger('documentation_fee_waived')->default(0)->after('documentation_fee_standard');
            $table->string('documentation_fee_waiver_reason', 500)->nullable()->after('documentation_fee_waived');
        });

        DB::table('financial_transactions')
            ->where('type', 'opening_purchase_balance')
            ->orderBy('id')
            ->get(['payment_plan_id', 'metadata'])
            ->each(function ($transaction): void {
                $metadata = is_array($transaction->metadata) ? $transaction->metadata : json_decode((string) $transaction->metadata, true);
                if (! is_array($metadata) || ! isset($metadata['purchase_price'])) {
                    return;
                }
                DB::table('payment_plans')->where('id', $transaction->payment_plan_id)->update([
                    'purchase_price' => (int) $metadata['purchase_price'],
                    'documentation_fee_standard' => (int) ($metadata['documentation_fee_standard'] ?? 0),
                    'documentation_fee_waived' => (int) ($metadata['documentation_fee_waived'] ?? 0),
                    'documentation_fee_waiver_reason' => $metadata['documentation_fee_waiver_reason'] ?? null,
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('payment_plans', function (Blueprint $table): void {
            $table->dropColumn(['purchase_price', 'documentation_fee_standard', 'documentation_fee_waived', 'documentation_fee_waiver_reason']);
        });
    }
};
