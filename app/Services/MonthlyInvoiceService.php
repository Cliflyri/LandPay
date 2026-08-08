<?php

namespace App\Services;

use App\Enums\FinancialActorType;
use App\Enums\FinancialEffectComponent;
use App\Enums\FinancialEffectType;
use App\Enums\FinancialTransactionType;
use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Financial\PostingEffect;
use App\Models\FinancialTransaction;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PaymentPlan;
use App\Models\PaymentPlanBillingTerm;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MonthlyInvoiceService
{
    public function __construct(
        private readonly FinancialPostingService $posting,
        private readonly FinancialBalanceService $balances,
    ) {}

    public function issue(
        PaymentPlan $plan,
        PaymentPlanBillingTerm $terms,
        User $actor,
        string $invoiceNumber,
        DateTimeInterface|string $periodStart,
        DateTimeInterface|string $periodEnd,
        DateTimeInterface|string $issueDate,
        int $monthlyFeeWaiver = 0,
        ?string $waiverReason = null,
    ): Invoice {
        return DB::transaction(function () use ($plan, $terms, $actor, $invoiceNumber, $periodStart, $periodEnd, $issueDate, $monthlyFeeWaiver, $waiverReason) {
            $lockedPlan = PaymentPlan::query()->lockForUpdate()->findOrFail($plan->id);
            $lockedTerms = PaymentPlanBillingTerm::query()->lockForUpdate()->findOrFail($terms->id);
            if ($lockedTerms->payment_plan_id !== $lockedPlan->id) {
                throw ValidationException::withMessages(['billing_terms' => 'Billing terms do not belong to this payment plan.']);
            }
            if ($lockedPlan->status !== 'active') {
                throw ValidationException::withMessages(['payment_plan' => 'Only an active payment plan may generate a monthly invoice.']);
            }

            $existing = Invoice::query()->where('invoice_number', $invoiceNumber)->first();
            if ($existing !== null) {
                if ($existing->payment_plan_id !== $lockedPlan->id) {
                    throw ValidationException::withMessages(['invoice_number' => 'Invoice number already belongs to another payment plan.']);
                }

                return $existing->load('items');
            }

            $contractBalance = $this->balances->contractBalance($lockedPlan);
            if ($contractBalance <= 0) {
                throw ValidationException::withMessages(['payment_plan' => 'The contract balance is already paid in full.']);
            }
            $scheduledAmount = min((int) $lockedTerms->scheduled_payment_amount, $contractBalance);
            if ($scheduledAmount <= 0) {
                throw ValidationException::withMessages(['scheduled_payment_amount' => 'Scheduled payment amount must be greater than zero.']);
            }

            $standardFee = (int) $lockedTerms->monthly_service_fee;
            if ($monthlyFeeWaiver < 0 || $monthlyFeeWaiver > $standardFee) {
                throw ValidationException::withMessages(['monthly_fee_waiver' => 'Monthly fee waiver is outside the allowed amount.']);
            }
            if ($monthlyFeeWaiver > 0 && blank($waiverReason)) {
                throw ValidationException::withMessages(['waiver_reason' => 'A waiver reason is required.']);
            }
            $actualFee = $standardFee - $monthlyFeeWaiver;
            $issue = Carbon::parse($issueDate);

            $invoice = Invoice::query()->create([
                'payment_plan_id' => $lockedPlan->id,
                'invoice_number' => $invoiceNumber,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'issue_date' => $issue,
                'due_date' => $issue->copy()->addDays((int) $lockedTerms->due_days_after_issue),
                'status' => InvoiceStatus::Issued,
                'issued_at' => now(),
                'created_by_user_id' => $actor->id,
            ]);

            $this->postScheduledPayment($lockedPlan, $invoice, $actor, $scheduledAmount);
            if ($standardFee > 0) {
                $this->postMonthlyFee($lockedPlan, $invoice, $actor, $standardFee, $actualFee, $monthlyFeeWaiver, $waiverReason);
            }

            return $invoice->load('items', 'transactions.effects');
        }, 3);
    }

    private function postScheduledPayment(PaymentPlan $plan, Invoice $invoice, User $actor, int $amount): FinancialTransaction
    {
        return $this->posting->post(
            $plan,
            FinancialTransactionType::InvoiceCharge,
            $amount,
            $invoice->issue_date,
            FinancialActorType::Administrator,
            function (FinancialTransaction $transaction) use ($invoice, $amount): array {
                $item = InvoiceItem::query()->create([
                    'invoice_id' => $invoice->id,
                    'source_transaction_id' => $transaction->id,
                    'item_type' => InvoiceItemType::ScheduledPurchasePayment,
                    'description' => 'Scheduled purchase payment',
                    'standard_amount' => $amount,
                    'amount' => $amount,
                    'waived_amount' => 0,
                    'display_order' => 1,
                ]);

                return [new PostingEffect(
                    FinancialEffectType::InvoiceDue,
                    $amount,
                    FinancialEffectComponent::ScheduledPurchasePayment,
                    invoiceId: $invoice->id,
                    invoiceItemId: $item->id,
                    description: 'Scheduled purchase payment due',
                )];
            },
            actor: $actor,
            invoice: $invoice,
            idempotencyKey: "monthly-invoice:{$invoice->uuid}:scheduled-payment",
            description: 'Monthly scheduled purchase payment',
        );
    }

    private function postMonthlyFee(PaymentPlan $plan, Invoice $invoice, User $actor, int $standardFee, int $actualFee, int $waived, ?string $waiverReason): FinancialTransaction
    {
        return $this->posting->post(
            $plan,
            FinancialTransactionType::RecurringFee,
            $actualFee,
            $invoice->issue_date,
            FinancialActorType::Administrator,
            function (FinancialTransaction $transaction) use ($invoice, $actor, $standardFee, $actualFee, $waived, $waiverReason): array {
                $item = InvoiceItem::query()->create([
                    'invoice_id' => $invoice->id,
                    'source_transaction_id' => $transaction->id,
                    'item_type' => InvoiceItemType::MonthlyServiceFee,
                    'description' => $waived > 0 ? 'Monthly service fee (waiver applied)' : 'Monthly service fee',
                    'standard_amount' => $standardFee,
                    'amount' => $actualFee,
                    'waived_amount' => $waived,
                    'waiver_reason' => $waiverReason,
                    'waived_by_user_id' => $waived > 0 ? $actor->id : null,
                    'waived_at' => $waived > 0 ? now() : null,
                    'display_order' => 2,
                ]);

                return $actualFee > 0 ? [new PostingEffect(
                    FinancialEffectType::InvoiceDue,
                    $actualFee,
                    FinancialEffectComponent::MonthlyServiceFee,
                    invoiceId: $invoice->id,
                    invoiceItemId: $item->id,
                    description: 'Monthly service fee due',
                )] : [];
            },
            actor: $actor,
            invoice: $invoice,
            idempotencyKey: "monthly-invoice:{$invoice->uuid}:monthly-service-fee",
            description: 'Monthly service fee',
            metadata: ['standard_amount' => $standardFee, 'waived_amount' => $waived, 'waiver_reason' => $waiverReason],
        );
    }
}
