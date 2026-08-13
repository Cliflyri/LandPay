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
use App\Models\TransactionEffect;
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

    public function uninvoicedPrincipal(PaymentPlan|int $plan): int
    {
        $planId = $plan instanceof PaymentPlan ? $plan->id : $plan;
        $outstanding = (int) TransactionEffect::query()
            ->join('invoices', 'invoices.id', '=', 'transaction_effects.invoice_id')
            ->where('invoices.payment_plan_id', $planId)
            ->where('invoices.status', '!=', InvoiceStatus::Voided->value)
            ->where('transaction_effects.effect_type', FinancialEffectType::InvoiceDue->value)
            ->where('transaction_effects.component', FinancialEffectComponent::ScheduledPurchasePayment->value)
            ->sum('transaction_effects.amount_delta');
        return max(0, $this->balances->contractBalance($planId) - max(0, $outstanding));
    }

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
        bool $automated = false,
    ): Invoice {
        return DB::transaction(function () use ($plan, $terms, $actor, $invoiceNumber, $periodStart, $periodEnd, $issueDate, $monthlyFeeWaiver, $waiverReason, $automated) {
            $lockedPlan = PaymentPlan::query()->lockForUpdate()->findOrFail($plan->id);
            $lockedTerms = PaymentPlanBillingTerm::query()->lockForUpdate()->findOrFail($terms->id);
            if ($lockedTerms->payment_plan_id !== $lockedPlan->id) {
                throw ValidationException::withMessages(['billing_terms' => 'Billing terms do not belong to this payment plan.']);
            }
            if (! in_array($lockedPlan->status, ['active', 'paused'], true) || ($automated && $lockedPlan->status !== 'active')) {
                throw ValidationException::withMessages(['payment_plan' => 'This payment plan is not eligible to generate an invoice.']);
            }

            $generationSource = $automated ? 'system' : 'administrator';
            $existing = Invoice::query()
                ->where('payment_plan_id', $lockedPlan->id)
                ->whereDate('period_start', Carbon::parse($periodStart))
                ->whereDate('period_end', Carbon::parse($periodEnd))
                ->where('generation_source', $generationSource)
                ->where('status', '!=', InvoiceStatus::Voided->value)
                ->whereHas('items', fn ($query) => $query->where('item_type', InvoiceItemType::ScheduledPurchasePayment->value))
                ->lockForUpdate()
                ->first();
            if ($existing !== null) {
                return $existing->load('items');
            }
            $invoiceNumber = $this->nextAvailableInvoiceNumber($invoiceNumber);

            $uninvoicedPrincipal = $this->uninvoicedPrincipal($lockedPlan);
            if ($uninvoicedPrincipal <= 0) {
                throw ValidationException::withMessages(['payment_plan' => 'The remaining contract principal is already billed or paid.']);
            }
            $scheduledAmount = min((int) $lockedTerms->scheduled_payment_amount, $uninvoicedPrincipal);
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
                'payment_plan_billing_term_id' => $lockedTerms->id,
                'invoice_number' => $invoiceNumber,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'issue_date' => $issue,
                'due_date' => $issue->copy()->addDays((int) $lockedTerms->due_days_after_issue),
                'status' => InvoiceStatus::Issued,
                'issued_at' => now(),
                'created_by_user_id' => $actor->id,
                'generation_source' => $automated ? 'system' : 'administrator',
            ]);

            $this->postScheduledPayment($lockedPlan, $invoice, $actor, $scheduledAmount, $automated);
            if ($standardFee > 0) {
                $this->postMonthlyFee($lockedPlan, $invoice, $actor, $standardFee, $actualFee, $monthlyFeeWaiver, $waiverReason, $automated);
            }
            $this->applyAvailableCredit($lockedPlan, $invoice, $actor, $automated);

            return $invoice->load('items', 'transactions.effects');
        }, 3);
    }

    private function nextAvailableInvoiceNumber(string $baseNumber): string
    {
        $invoiceNumber = $baseNumber;
        if (! Invoice::query()->where('invoice_number', $invoiceNumber)->exists()) {
            return $invoiceNumber;
        }

        $sequence = 2;
        do {
            $invoiceNumber = $baseNumber.'-'.$sequence++;
        } while (Invoice::query()->where('invoice_number', $invoiceNumber)->exists());

        return $invoiceNumber;
    }

    private function postScheduledPayment(PaymentPlan $plan, Invoice $invoice, User $actor, int $amount, bool $automated): FinancialTransaction
    {
        return $this->posting->post(
            $plan,
            FinancialTransactionType::InvoiceCharge,
            $amount,
            $invoice->issue_date,
            $automated ? FinancialActorType::System : FinancialActorType::Administrator,
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
            actor: $automated ? null : $actor,
            invoice: $invoice,
            idempotencyKey: "monthly-invoice:{$invoice->uuid}:scheduled-payment",
            description: 'Monthly scheduled purchase payment',
        );
    }

    private function postMonthlyFee(PaymentPlan $plan, Invoice $invoice, User $actor, int $standardFee, int $actualFee, int $waived, ?string $waiverReason, bool $automated): FinancialTransaction
    {
        return $this->posting->post(
            $plan,
            FinancialTransactionType::RecurringFee,
            $actualFee,
            $invoice->issue_date,
            $automated ? FinancialActorType::System : FinancialActorType::Administrator,
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
            actor: $automated ? null : $actor,
            invoice: $invoice,
            idempotencyKey: "monthly-invoice:{$invoice->uuid}:monthly-service-fee",
            description: 'Monthly service fee',
            metadata: ['standard_amount' => $standardFee, 'waived_amount' => $waived, 'waiver_reason' => $waiverReason],
        );
    }

    private function applyAvailableCredit(PaymentPlan $plan, Invoice $invoice, User $actor, bool $automated): void
    {
        $available = min($this->balances->clientCredit($plan), $this->balances->invoiceBalance($invoice));
        if ($available <= 0) {
            return;
        }

        $effects = [new PostingEffect(FinancialEffectType::ClientCredit, -$available, FinancialEffectComponent::UnappliedCredit, description: 'Account credit applied to invoice '.$invoice->invoice_number)];
        $remaining = $available;
        $items = $invoice->items()->orderByRaw("item_type = ? asc", [InvoiceItemType::ScheduledPurchasePayment->value])->orderBy('display_order')->get();
        foreach ($items as $item) {
            if ($remaining <= 0 || $item->amount <= 0) {
                continue;
            }
            $applied = min($remaining, (int) $item->amount);
            $component = FinancialEffectComponent::tryFrom($item->item_type->value) ?? FinancialEffectComponent::Other;
            $effects[] = new PostingEffect(FinancialEffectType::InvoiceDue, -$applied, $component, invoiceId: $invoice->id, invoiceItemId: $item->id, description: 'Account credit applied');
            if ($item->item_type === InvoiceItemType::ScheduledPurchasePayment) {
                $effects[] = new PostingEffect(FinancialEffectType::PurchaseBalance, -$applied, FinancialEffectComponent::PurchasePricePrincipal, description: 'Account credit applied to principal');
            }
            $remaining -= $applied;
        }

        $this->posting->post(
            $plan,
            FinancialTransactionType::CreditApplication,
            $available,
            $invoice->issue_date,
            $automated ? FinancialActorType::System : FinancialActorType::Administrator,
            $effects,
            actor: $automated ? null : $actor,
            invoice: $invoice,
            idempotencyKey: "monthly-invoice:{$invoice->uuid}:account-credit",
            description: 'Account credit applied to invoice',
        );

        $invoice->update(['status' => $this->balances->invoiceBalance($invoice) <= 0 ? InvoiceStatus::Paid : InvoiceStatus::PartiallyPaid]);
    }
}
