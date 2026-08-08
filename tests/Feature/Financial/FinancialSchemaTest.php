<?php

namespace Tests\Feature\Financial;

use App\Models\PaymentPlan;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class FinancialSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_approved_financial_tables_and_critical_columns_exist(): void
    {
        foreach ([
            'billing_defaults',
            'payment_plan_billing_terms',
            'contract_status_events',
            'invoices',
            'financial_transactions',
            'invoice_items',
            'recurring_fee_rules',
            'fee_assessments',
            'transaction_effects',
            'payments',
            'payment_allocations',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }

        $this->assertTrue(Schema::hasColumns('contract_status_events', [
            'contract_balance_snapshot', 'paid_in_value_snapshot', 'idempotency_key',
        ]));
        $this->assertTrue(Schema::hasColumns('invoice_items', [
            'standard_amount', 'amount', 'waived_amount', 'late_fee_stage',
        ]));
        $this->assertTrue(Schema::hasColumns('payments', [
            'overpayment_amount', 'overpayment_disposition', 'decision_source',
        ]));
    }

    public function test_each_invoice_allows_at_most_one_item_per_late_fee_stage(): void
    {
        [$invoiceId, $transactionId] = $this->invoiceAndTransaction();
        $this->insertItem($invoiceId, $transactionId, 'late_fee_stage_1');

        $this->expectException(QueryException::class);
        $this->insertItem($invoiceId, $transactionId, 'late_fee_stage_1');
    }

    public function test_multiple_non_late_items_are_allowed_on_one_invoice(): void
    {
        [$invoiceId, $transactionId] = $this->invoiceAndTransaction();
        $this->insertItem($invoiceId, $transactionId, null);
        $this->insertItem($invoiceId, $transactionId, null);

        $this->assertSame(2, DB::table('invoice_items')->where('invoice_id', $invoiceId)->count());
    }

    private function invoiceAndTransaction(): array
    {
        $user = User::factory()->create();
        $plan = PaymentPlan::query()->create([
            'plan_number' => 'LP-SCHEMA-1',
            'title' => 'Schema test',
            'original_purchase_balance' => 100000,
            'customary_monthly_payment' => 10000,
            'monthly_due_day' => 3,
            'first_due_date' => '2026-08-08',
            'plan_start_date' => '2026-07-26',
            'created_by_user_id' => $user->id,
            'updated_by_user_id' => $user->id,
        ]);
        $invoiceId = DB::table('invoices')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'payment_plan_id' => $plan->id,
            'invoice_number' => 'INV-SCHEMA-1',
            'issue_date' => '2026-08-03',
            'due_date' => '2026-08-08',
            'status' => 'issued',
            'created_by_user_id' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $transactionId = DB::table('financial_transactions')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'payment_plan_id' => $plan->id,
            'invoice_id' => $invoiceId,
            'type' => 'invoice_charge',
            'gross_amount' => 2500,
            'effective_date' => '2026-08-09',
            'posted_at' => now(),
            'actor_type' => 'system',
            'created_at' => now(),
        ]);

        return [$invoiceId, $transactionId];
    }

    private function insertItem(int $invoiceId, int $transactionId, ?string $stage): void
    {
        DB::table('invoice_items')->insert([
            'invoice_id' => $invoiceId,
            'source_transaction_id' => $transactionId,
            'item_type' => $stage ?? 'monthly_service_fee',
            'late_fee_stage' => $stage,
            'description' => $stage ? 'Late fee' : 'Monthly service fee',
            'standard_amount' => 2500,
            'amount' => 2500,
            'waived_amount' => 0,
            'display_order' => 1,
            'created_at' => now(),
        ]);
    }
}