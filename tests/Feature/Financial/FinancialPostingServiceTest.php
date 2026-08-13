<?php

namespace Tests\Feature\Financial;

use App\Enums\FinancialActorType;
use App\Enums\FinancialEffectComponent;
use App\Enums\FinancialEffectType;
use App\Enums\FinancialTransactionType;
use App\Enums\InvoiceItemType;
use App\Financial\PostingEffect;
use App\Models\FinancialTransaction;
use App\Models\PaymentPlan;
use App\Models\PaymentPlanBillingTerm;
use App\Models\User;
use App\Services\ContractOpeningService;
use App\Services\FinancialBalanceService;
use App\Services\FinancialPostingService;
use App\Services\MonthlyInvoiceService;
use App\Services\OpeningPrincipalCreditService;
use App\Services\MonthlyServiceFeeHistoryService;
use App\Services\LateFeeAssessmentService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use LogicException;
use Tests\TestCase;

class FinancialPostingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_balance_preserves_principal_components_and_waiver_history(): void
    {
        [$user, $plan] = $this->draftPlan();
        $transaction = app(ContractOpeningService::class)->open(
            $plan, $user, 2_000_000, 50_000, 10_000, '2026-07-26', 'Promotional waiver',
        );

        $this->assertSame(2_040_000, app(FinancialBalanceService::class)->contractBalance($plan));
        $this->assertSame(0, app(FinancialBalanceService::class)->administratorPaidInValue($plan));
        $this->assertSame(2_040_000, $plan->fresh()->original_purchase_balance);
        $this->assertSame(2, $transaction->effects->count());
        $this->assertSame(40_000, $transaction->effects->firstWhere('component', FinancialEffectComponent::DocumentationFeePrincipal)->amount_delta);
        $this->assertSame(10_000, $transaction->metadata['documentation_fee_waived']);
        $this->assertSame('Promotional waiver', $transaction->metadata['documentation_fee_waiver_reason']);
    }

    public function test_opening_balance_is_idempotent(): void
    {
        [$user, $plan] = $this->draftPlan();
        $service = app(ContractOpeningService::class);
        $first = $service->open($plan, $user, 1_000_000, 25_000, 0, '2026-07-26');
        $second = $service->open($plan->fresh(), $user, 1_000_000, 25_000, 0, '2026-07-26');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, FinancialTransaction::query()->where('type', FinancialTransactionType::OpeningPurchaseBalance)->count());
        $this->assertSame(1_025_000, app(FinancialBalanceService::class)->contractBalance($plan));
    }

    public function test_monthly_invoice_separates_scheduled_payment_and_service_fee(): void
    {
        [$user, $plan, $terms] = $this->activeOpenedPlan();
        $openingBalance = app(FinancialBalanceService::class)->contractBalance($plan);

        $invoice = app(MonthlyInvoiceService::class)->issue(
            $plan, $terms, $user, 'INV-1001', '2026-08-01', '2026-08-31', '2026-08-03',
        );

        $this->assertSame(52_500, app(FinancialBalanceService::class)->invoiceBalance($invoice));
        $this->assertSame($openingBalance, app(FinancialBalanceService::class)->contractBalance($plan));
        $this->assertSame('2026-08-08', $invoice->due_date->toDateString());
        $this->assertSame(2, $invoice->items->count());
        $this->assertDatabaseHas('invoice_items', ['invoice_id' => $invoice->id, 'item_type' => 'scheduled_purchase_payment', 'amount' => 50_000]);
        $this->assertDatabaseHas('invoice_items', ['invoice_id' => $invoice->id, 'item_type' => 'monthly_service_fee', 'amount' => 2_500]);
        $feeSummary = app(MonthlyServiceFeeHistoryService::class)->summaryForMonth($plan, now()->setDate(2026, 8, 15));
        $this->assertSame(2_500, $feeSummary['assessed']);
        $this->assertSame(0, $feeSummary['total']);
        $this->assertSame(2_500, $feeSummary['remaining']);
    }

    public function test_full_monthly_fee_waiver_remains_visible_without_amount_due(): void
    {
        [$user, $plan, $terms] = $this->activeOpenedPlan();
        $invoice = app(MonthlyInvoiceService::class)->issue(
            $plan, $terms, $user, 'INV-1002', '2026-08-01', '2026-08-31', '2026-08-03', 2_500, 'Courtesy waiver',
        );

        $this->assertSame(50_000, app(FinancialBalanceService::class)->invoiceBalance($invoice));
        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'item_type' => 'monthly_service_fee',
            'standard_amount' => 2_500,
            'amount' => 0,
            'waived_amount' => 2_500,
            'waiver_reason' => 'Courtesy waiver',
        ]);
        $feeSummary = app(MonthlyServiceFeeHistoryService::class)->summaryForMonth($plan, now()->setDate(2026, 8, 15));
        $this->assertSame(0, $feeSummary['assessed']);
        $this->assertSame(0, $feeSummary['remaining']);
    }

    public function test_final_scheduled_principal_accounts_for_open_invoices(): void
    {
        [$user, $plan, $terms] = $this->activeOpenedPlan();
        app(MonthlyInvoiceService::class)->issue($plan, $terms, $user, 'INV-FIRST', '2026-08-01', '2026-08-31', '2026-08-03');
        app(FinancialPostingService::class)->post(
            $plan, FinancialTransactionType::Payment, 1_975_000, '2026-08-15', FinancialActorType::Administrator,
            [new PostingEffect(FinancialEffectType::PurchaseBalance, -1_975_000, FinancialEffectComponent::PurchasePricePrincipal)],
            actor: $user, description: 'Principal reduction for final invoice test',
        );

        $invoice = app(MonthlyInvoiceService::class)->issue($plan, $terms, $user, 'INV-FINAL', '2026-09-01', '2026-09-30', '2026-09-03');

        $this->assertDatabaseHas('invoice_items', ['invoice_id' => $invoice->id, 'item_type' => 'scheduled_purchase_payment', 'amount' => 25_000]);
        $this->assertDatabaseHas('invoice_items', ['invoice_id' => $invoice->id, 'item_type' => 'monthly_service_fee', 'amount' => 2_500]);
    }

    public function test_account_credit_is_applied_to_fees_then_principal_on_next_invoice(): void
    {
        [$user, $plan, $terms] = $this->activeOpenedPlan();
        app(FinancialPostingService::class)->post(
            $plan, FinancialTransactionType::Payment, 60_000, '2026-08-01', FinancialActorType::Administrator,
            [new PostingEffect(FinancialEffectType::ClientCredit, 60_000, FinancialEffectComponent::UnappliedCredit)],
            actor: $user, description: 'Existing customer credit',
        );

        $invoice = app(MonthlyInvoiceService::class)->issue($plan, $terms, $user, 'INV-CREDIT', '2026-08-01', '2026-08-31', '2026-08-03');

        $this->assertSame(0, app(FinancialBalanceService::class)->invoiceBalance($invoice));
        $this->assertSame(7_500, app(FinancialBalanceService::class)->clientCredit($plan));
        $this->assertSame(2_000_000, app(FinancialBalanceService::class)->contractBalance($plan));
        $this->assertSame('paid', $invoice->fresh()->status->value);
        $this->assertDatabaseHas('financial_transactions', ['invoice_id' => $invoice->id, 'type' => FinancialTransactionType::CreditApplication->value, 'gross_amount' => 52_500]);
    }

    public function test_amount_previously_paid_in_is_auditable_and_never_creates_credit(): void
    {
        [$user, $plan] = $this->draftPlan();
        app(ContractOpeningService::class)->open($plan, $user, 100_000, 5_000, 0, '2026-08-01');
        $service = app(OpeningPrincipalCreditService::class);

        $service->post($plan, $user, 30_000, '2026-08-01');
        $this->assertSame(30_000, $service->amount($plan));
        $this->assertSame(75_000, app(FinancialBalanceService::class)->contractBalance($plan));
        $this->assertSame(30_000, app(FinancialBalanceService::class)->administratorPaidInValue($plan));
        $this->assertSame(0, app(FinancialBalanceService::class)->clientCredit($plan));

        $service->amend($plan, $user, 40_000, '2026-08-02', 'Correct clerical error');
        $this->assertSame(65_000, app(FinancialBalanceService::class)->contractBalance($plan));
        $service->amend($plan, $user, 20_000, '2026-08-03', 'Correct clerical error');
        $this->assertSame(85_000, app(FinancialBalanceService::class)->contractBalance($plan));

        $this->expectException(ValidationException::class);
        $service->amend($plan, $user, 110_000, '2026-08-04', 'Invalid excess adjustment');
    }

    public function test_invalid_invoice_posting_rolls_back_every_record(): void
    {
        [$user, $plan, $terms] = $this->activeOpenedPlan();

        try {
            app(MonthlyInvoiceService::class)->issue(
                $plan, $terms, $user, 'INV-ROLLBACK', '2026-08-01', '2026-08-31', '2026-08-03', 3_000, 'Invalid',
            );
            $this->fail('Expected waiver validation failure.');
        } catch (ValidationException) {
            $this->assertDatabaseMissing('invoices', ['invoice_number' => 'INV-ROLLBACK']);
        }
    }

    public function test_balance_floor_failure_rolls_back_transaction_header(): void
    {
        [$user, $plan] = $this->draftPlan();

        try {
            app(FinancialPostingService::class)->post(
                $plan,
                FinancialTransactionType::Refund,
                100,
                '2026-07-26',
                FinancialActorType::Administrator,
                [new PostingEffect(FinancialEffectType::ClientCredit, -100, FinancialEffectComponent::Refund)],
                actor: $user,
                reason: 'Invalid refund test',
                idempotencyKey: 'invalid-refund-test',
            );
            $this->fail('Expected negative-credit validation failure.');
        } catch (ValidationException) {
            $this->assertDatabaseMissing('financial_transactions', ['idempotency_key' => 'invalid-refund-test']);
        }
    }

    public function test_posted_financial_records_are_append_only(): void
    {
        [$user, $plan, $terms] = $this->activeOpenedPlan();
        $invoice = app(MonthlyInvoiceService::class)->issue(
            $plan, $terms, $user, 'INV-IMMUTABLE', '2026-08-01', '2026-08-31', '2026-08-03',
        );
        $transaction = $invoice->transactions->first();

        try {
            $transaction->update(['description' => 'Changed']);
            $this->fail('Expected transaction update to be blocked.');
        } catch (LogicException) {
            $this->assertNotSame('Changed', $transaction->fresh()->description);
        }

        $this->expectException(LogicException::class);
        $invoice->items->first()->delete();
    }

    public function test_late_fee_is_added_after_grace_and_is_not_restored_after_admin_removal(): void
    {
        [$user, $plan, $terms] = $this->activeOpenedPlan();
        $terms->update(['grace_days' => 3, 'stage_one_days_late' => 4]);
        $invoice = app(MonthlyInvoiceService::class)->issue($plan, $terms, $user, 'INV-LATE', '2026-08-01', '2026-08-31', '2026-08-03');
        $service = app(LateFeeAssessmentService::class);

        $this->assertFalse($service->assess($invoice, 1, Carbon::parse('2026-08-11')));
        $this->assertTrue($service->assess($invoice, 1, Carbon::parse('2026-08-12')));
        $this->assertDatabaseHas('invoice_items', ['invoice_id' => $invoice->id, 'item_type' => 'late_fee_stage_1', 'description' => 'Late Fee added 8/12/26', 'amount' => 2_500]);

        $fee = $invoice->fresh('allItems')->allItems->firstWhere('item_type', InvoiceItemType::LateFeeStageOne);
        DB::table('invoice_items')->where('id', $fee->id)->update(['retired_at' => now(), 'late_fee_stage' => null]);
        $this->assertFalse($service->assess($invoice->fresh('allItems'), 1, Carbon::parse('2026-08-13')));
    }

    private function activeOpenedPlan(): array
    {
        [$user, $plan] = $this->draftPlan();
        app(ContractOpeningService::class)->open($plan, $user, 2_000_000, 50_000, 0, '2026-07-26');
        $plan->update(['status' => 'active', 'activated_at' => now()]);
        $terms = PaymentPlanBillingTerm::query()->create([
            'payment_plan_id' => $plan->id,
            'frequency' => 'monthly',
            'invoice_day' => 3,
            'due_days_after_issue' => 5,
            'grace_days' => 0,
            'scheduled_payment_amount' => 50_000,
            'monthly_service_fee' => 2_500,
            'stage_one_enabled' => true,
            'stage_one_fee_type' => 'fixed',
            'stage_one_fixed_amount' => 2_500,
            'stage_one_minimum_amount' => 0,
            'stage_one_days_late' => 1,
            'stage_two_enabled' => true,
            'stage_two_fee_type' => 'fixed',
            'stage_two_fixed_amount' => 5_000,
            'stage_two_minimum_amount' => 0,
            'stage_two_days_late' => 30,
            'default_eligibility_days' => 60,
            'effective_from' => '2026-07-26',
            'created_by_user_id' => $user->id,
        ]);

        return [$user, $plan->fresh(), $terms];
    }

    private function draftPlan(): array
    {
        $user = User::factory()->create();
        $plan = PaymentPlan::query()->create([
            'plan_number' => 'LP-'.uniqid(),
            'title' => 'Test purchase',
            'original_purchase_balance' => 1,
            'customary_monthly_payment' => 50_000,
            'monthly_due_day' => 3,
            'first_due_date' => '2026-08-08',
            'plan_start_date' => '2026-07-26',
            'status' => 'draft',
            'created_by_user_id' => $user->id,
            'updated_by_user_id' => $user->id,
        ]);

        return [$user, $plan];
    }
}
