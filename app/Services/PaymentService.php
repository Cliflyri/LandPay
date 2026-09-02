<?php

namespace App\Services;

use App\Enums\FinancialActorType;
use App\Enums\FinancialEffectComponent;
use App\Enums\FinancialEffectType;
use App\Enums\FinancialTransactionType;
use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Enums\OverpaymentDisposition;
use App\Enums\PaymentAllocationType;
use App\Enums\PaymentMethod;
use App\Financial\PostingEffect;
use App\Models\FinancialTransaction;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\PaymentPlan;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private readonly FinancialPostingService $posting,
        private readonly FinancialBalanceService $balances,
        private readonly FirstPaymentInvoiceService $firstPaymentInvoices,
    ) {}

    /** @return array<string, mixed> */
    public function preview(PaymentPlan $plan, int $amount, string $paymentType, ?string $overpaymentDisposition = null, int $serviceFeeAmount = 0, ?string $serviceFeeMonth = null, ?int $invoiceId = null): array
    {
        if (! in_array($plan->status, ['active', 'paused'], true)) {
            throw ValidationException::withMessages(['payment_plan' => 'Payments can only be posted to an active or paused payment plan.']);
        }
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'The payment amount must be greater than zero.']);
        }
        if (! in_array($paymentType, ['regular', 'principal_only'], true)) {
            throw ValidationException::withMessages(['payment_type' => 'Choose a valid payment type.']);
        }

        if ($serviceFeeAmount < 0 || $serviceFeeAmount > $amount || ($paymentType === 'principal_only' && $serviceFeeAmount > 0)) {
            throw ValidationException::withMessages(['service_fee_amount' => 'Choose a valid service-fee amount.']);
        }
        $contractBalance = $this->balances->contractBalance($plan);
        if ($paymentType === 'principal_only') {
            if ($amount > $contractBalance) {
                throw ValidationException::withMessages(['amount' => 'A principal-only payment cannot exceed the remaining contract balance.']);
            }

            return [
                'amount' => $amount,
                'payment_type' => $paymentType,
                'invoice_amount' => 0,
                'overpayment_amount' => 0,
                'principal_amount' => $amount,
                'credit_amount' => 0,
                'allocations' => [[
                    'type' => PaymentAllocationType::PurchaseBalance,
                    'amount' => $amount,
                    'component' => FinancialEffectComponent::PurchasePricePrincipal,
                    'label' => 'Principal-only payment',
                    'invoice_id' => null,
                    'invoice_item_id' => null,
                ]],
            ];
        }


        $remaining = $amount;
        $allocations = [];
        if ($serviceFeeAmount > 0) {
            $allocations[] = [
                'type' => PaymentAllocationType::ServiceFee,
                'amount' => $serviceFeeAmount,
                'component' => FinancialEffectComponent::MonthlyServiceFee,
                'label' => 'Monthly service fee'.($serviceFeeMonth ? ' — '.date('F Y', strtotime($serviceFeeMonth)) : ''),
                'invoice_id' => null,
                'invoice_item_id' => null,
                'billing_month' => $serviceFeeMonth,
            ];
            $remaining -= $serviceFeeAmount;
        }
        if ($invoiceId === null && $this->hasUninvoicedDueFirstPayment($plan)) {
            $allocated = min($remaining, $plan->first_payment_amount);
            $allocations[] = [
                'type' => PaymentAllocationType::InvoiceItem,
                'amount' => $allocated,
                'component' => FinancialEffectComponent::ScheduledPurchasePayment,
                'label' => 'First payment',
                'invoice_id' => null,
                'invoice_number' => 'Pending first-payment invoice',
                'invoice_item_id' => null,
                'pending_first_payment' => true,
            ];
            $remaining -= $allocated;
        }
        $invoices = Invoice::query()
            ->where('payment_plan_id', $plan->id)
            ->whereIn('status', [InvoiceStatus::Issued->value, InvoiceStatus::PartiallyPaid->value])
            ->when($invoiceId, fn ($query) => $query->whereKey($invoiceId))
            ->with('items')
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();

        foreach ($invoices as $invoice) {
            if ($remaining === 0) {
                break;
            }
            $invoiceRemaining = $this->balances->invoiceBalance($invoice);
            if ($invoiceRemaining <= 0) {
                continue;
            }
            $items = $invoice->items->sortBy(fn (InvoiceItem $item) => [$this->itemPriority($item), $item->display_order, $item->id]);
            foreach ($items as $item) {
                if ($remaining === 0 || $invoiceRemaining === 0) {
                    break;
                }
                $available = min($this->itemRemaining($item), $invoiceRemaining);
                if ($available <= 0) {
                    continue;
                }
                $allocated = min($remaining, $available);
                $allocations[] = [
                    'type' => PaymentAllocationType::InvoiceItem,
                    'amount' => $allocated,
                    'due_amount' => $available,
                    'component' => $this->componentForItem($item),
                    'label' => strcasecmp($item->description, 'plan payment') === 0 ? 'Plan Payment' : $item->description,
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'invoice_item_id' => $item->id,
                ];
                $remaining -= $allocated;
                $invoiceRemaining -= $allocated;
            }
        }

        $invoiceAmount = (int) collect($allocations)->where('type', PaymentAllocationType::InvoiceItem)->sum('amount');
        $principalAmount = collect($allocations)
            ->where('component', FinancialEffectComponent::ScheduledPurchasePayment)
            ->sum('amount');
        $overpaymentAmount = $remaining;
        $creditAmount = 0;
        if ($remaining > 0) {
            if (! in_array($overpaymentDisposition, [null, OverpaymentDisposition::Principal->value, OverpaymentDisposition::NextInvoiceCredit->value], true)) {
                throw ValidationException::withMessages(['overpayment_disposition' => 'Choose how the client instructed LandPay to handle the overpayment.']);
            }
            if ($overpaymentDisposition !== OverpaymentDisposition::NextInvoiceCredit->value) {
                $principalApplied = min($remaining, max(0, $contractBalance - $principalAmount));
                if ($principalApplied > 0) {
                    $principalAmount += $principalApplied;
                    $remaining -= $principalApplied;
                    $allocations[] = [
                        'type' => PaymentAllocationType::PurchaseBalance,
                        'amount' => $principalApplied,
                        'component' => FinancialEffectComponent::PurchasePricePrincipal,
                        'label' => 'Overpayment applied to principal',
                        'invoice_id' => null,
                        'invoice_item_id' => null,
                    ];
                }
            }
            if ($remaining > 0) {
                $creditAmount = $remaining;
                $allocations[] = [
                    'type' => PaymentAllocationType::ClientCredit,
                    'amount' => $remaining,
                    'component' => FinancialEffectComponent::UnappliedCredit,
                    'label' => 'Credit for a future invoice or refund',
                    'invoice_id' => null,
                    'invoice_item_id' => null,
                ];
            }
        }

        return [
            'amount' => $amount,
            'payment_type' => $paymentType,
            'invoice_amount' => $invoiceAmount,
            'overpayment_amount' => $overpaymentAmount,
            'principal_amount' => $principalAmount,
            'credit_amount' => $creditAmount,
            'service_fee_amount' => $serviceFeeAmount,
            'allocations' => $allocations,
        ];
    }

    public function post(
        PaymentPlan $plan,
        User $actor,
        int $amount,
        string $paymentType,
        PaymentMethod $method,
        DateTimeInterface|string $receivedDate,
        ?int $payerClientId = null,
        ?string $externalReference = null,
        ?OverpaymentDisposition $overpaymentDisposition = null,
        ?string $idempotencyKey = null,
        int $serviceFeeAmount = 0,
        ?string $serviceFeeMonth = null,
        int $processingFeeAmount = 0,
        ?int $invoiceId = null,
    ): Payment {
        return DB::transaction(function () use ($plan, $actor, $amount, $paymentType, $method, $receivedDate, $payerClientId, $externalReference, $overpaymentDisposition, $idempotencyKey, $serviceFeeAmount, $serviceFeeMonth, $processingFeeAmount, $invoiceId): Payment {
            if ($idempotencyKey !== null) {
                $existing = FinancialTransaction::query()->where('idempotency_key', $idempotencyKey)->first();
                if ($existing !== null) {
                    return Payment::query()->where('financial_transaction_id', $existing->id)->firstOrFail();
                }
            }
            $lockedPlan = PaymentPlan::query()->lockForUpdate()->findOrFail($plan->id);
            if ($this->hasUninvoicedDueFirstPayment($lockedPlan)) {
                $this->firstPaymentInvoices->issue(
                    $lockedPlan,
                    $actor,
                    $lockedPlan->first_payment_amount,
                    $lockedPlan->plan_start_date,
                    $lockedPlan->first_due_date,
                );
            }
            if ($processingFeeAmount < 0 || $processingFeeAmount >= $amount) {
                throw ValidationException::withMessages(['processing_fee' => 'Processing Fee must be less than the total payment.']);
            }
            $preview = $this->preview($lockedPlan, $amount - $processingFeeAmount, $paymentType, $overpaymentDisposition?->value, $serviceFeeAmount, $serviceFeeMonth, $invoiceId);
            if ($processingFeeAmount > 0) {
                $preview['allocations'][] = [
                    'type' => PaymentAllocationType::ProcessingFee,
                    'amount' => $processingFeeAmount,
                    'component' => FinancialEffectComponent::AdministrativeFee,
                    'label' => 'Processing Fee',
                    'invoice_id' => null,
                    'invoice_item_id' => null,
                ];
            }
            if (false && $preview['overpayment_amount'] > 0 && $overpaymentDisposition === null) {
                throw ValidationException::withMessages(['overpayment_disposition' => 'Record the client’s instruction for the overpayment before posting.']);
            }

            $effects = [];
            foreach ($preview['allocations'] as $allocation) {
                if ($allocation['type'] === PaymentAllocationType::InvoiceItem) {
                    $effects[] = new PostingEffect(
                        FinancialEffectType::InvoiceDue,
                        -$allocation['amount'],
                        $allocation['component'],
                        invoiceId: $allocation['invoice_id'],
                        invoiceItemId: $allocation['invoice_item_id'],
                        description: 'Payment applied to '.$allocation['label'],
                    );
                    if ($allocation['component'] === FinancialEffectComponent::ScheduledPurchasePayment) {
                        $effects[] = new PostingEffect(
                            FinancialEffectType::PurchaseBalance,
                            -$allocation['amount'],
                            FinancialEffectComponent::PurchasePricePrincipal,
                            description: 'Scheduled payment applied to principal',
                        );
                    } elseif ($allocation['component'] === FinancialEffectComponent::DocumentationFeePrincipal) {
                        $effects[] = new PostingEffect(
                            FinancialEffectType::PurchaseBalance,
                            -$allocation['amount'],
                            FinancialEffectComponent::DocumentationFeePrincipal,
                            description: 'Documentation fee payment applied',
                        );
                } elseif (in_array($allocation['type'], [PaymentAllocationType::ServiceFee, PaymentAllocationType::ProcessingFee], true)) {
                    }
                    // Collected directly as a non-principal fee; no receivable or principal balance changes.
                } elseif ($allocation['type'] === PaymentAllocationType::PurchaseBalance) {
                    $effects[] = new PostingEffect(FinancialEffectType::PurchaseBalance, -$allocation['amount'], $allocation['component'], description: $allocation['label']);
                } else {
                    $effects[] = new PostingEffect(FinancialEffectType::ClientCredit, $allocation['amount'], $allocation['component'], description: $allocation['label']);
                }
            }

            $transaction = $this->posting->post(
                $lockedPlan,
                FinancialTransactionType::Payment,
                $amount,
                $receivedDate,
                FinancialActorType::Administrator,
                $effects,
                actor: $actor,
                description: $paymentType === 'principal_only' ? 'Principal-only payment' : 'Payment received',
                metadata: ['payment_type' => $paymentType, 'processing_fee_amount' => $processingFeeAmount],
                idempotencyKey: $idempotencyKey,
            );
            $payment = Payment::query()->create([
                'financial_transaction_id' => $transaction->id,
                'payer_client_id' => $payerClientId,
                'received_date' => $receivedDate,
                'payment_method' => $method,
                'external_reference' => filled($externalReference) ? trim($externalReference) : null,
                'gross_amount' => $amount,
                'current_invoice_amount' => $preview['invoice_amount'],
                'overpayment_amount' => $preview['overpayment_amount'],
                'overpayment_disposition' => $preview['overpayment_amount'] > 0 ? ($overpaymentDisposition ?? OverpaymentDisposition::Principal) : null,
                'decision_source' => $preview['overpayment_amount'] > 0 ? ($overpaymentDisposition === null ? 'system_default' : 'administrator_recorded') : null,
                'decision_selected_at' => $preview['overpayment_amount'] > 0 ? now() : null,
                'instruction_recorded_by_user_id' => $preview['overpayment_amount'] > 0 ? $actor->id : null,
            ]);
            foreach ($preview['allocations'] as $index => $allocation) {
                PaymentAllocation::query()->create([
                    'payment_id' => $payment->id,
                    'allocation_type' => $allocation['type'],
                    'invoice_id' => $allocation['invoice_id'],
                    'invoice_item_id' => $allocation['invoice_item_id'],
                    'billing_month' => $allocation['billing_month'] ?? null,
                    'amount' => $allocation['amount'],
                    'display_order' => $index + 1,
                ]);
            }
            $this->syncInvoiceStatuses(collect($preview['allocations'])->pluck('invoice_id')->filter()->unique()->all());

            return $payment->load(['financialTransaction.effects', 'allocations.invoice', 'allocations.invoiceItem']);
        }, 3);
    }

    public function reverse(Payment $payment, User $actor, string $reason): FinancialTransaction
    {
        return DB::transaction(function () use ($payment, $actor, $reason): FinancialTransaction {
            $original = FinancialTransaction::query()->with('effects')->lockForUpdate()->findOrFail($payment->financial_transaction_id);
            if (FinancialTransaction::query()->where('reversal_of_transaction_id', $original->id)->exists()) {
                throw ValidationException::withMessages(['payment' => 'This payment has already been reversed.']);
            }
            $effects = $original->effects->map(fn ($effect) => new PostingEffect(
                $effect->effect_type,
                -$effect->amount_delta,
                $effect->component,
                invoiceId: $effect->invoice_id,
                invoiceItemId: $effect->invoice_item_id,
                feeAssessmentId: $effect->fee_assessment_id,
                description: 'Reversal: '.$effect->description,
            ))->all();
            $reversal = $this->posting->post(
                $original->paymentPlan,
                FinancialTransactionType::Reversal,
                $original->gross_amount,
                now()->toDateString(),
                FinancialActorType::Administrator,
                $effects,
                actor: $actor,
                description: 'Payment reversal',
                reason: trim($reason),
                reversalOf: $original,
            );
            $this->syncInvoiceStatuses($original->effects->pluck('invoice_id')->filter()->unique()->all());

            return $reversal;
        }, 3);
    }

    private function itemRemaining(InvoiceItem $item): int
    {
        $allocated = (int) DB::table('payment_allocations')
            ->join('payments', 'payments.id', '=', 'payment_allocations.payment_id')
            ->join('financial_transactions', 'financial_transactions.id', '=', 'payments.financial_transaction_id')
            ->where('payment_allocations.invoice_item_id', $item->id)
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('financial_transactions as reversals')->whereColumn('reversals.reversal_of_transaction_id', 'financial_transactions.id'))
            ->sum('payment_allocations.amount');

        return max(0, $item->amount - $allocated);
    }

    private function itemPriority(InvoiceItem $item): int
    {
        return $item->item_type === InvoiceItemType::ScheduledPurchasePayment ? 2 : 1;
    }

    private function componentForItem(InvoiceItem $item): FinancialEffectComponent
    {
        return match ($item->item_type) {
            InvoiceItemType::DocumentationFee => FinancialEffectComponent::DocumentationFeePrincipal,
            default => FinancialEffectComponent::tryFrom($item->item_type->value) ?? FinancialEffectComponent::Other,
        };
    }

    public function uninvoicedDueFirstPaymentAmount(PaymentPlan $plan): int
    {
        return $this->hasUninvoicedDueFirstPayment($plan) ? (int) $plan->first_payment_amount : 0;
    }

    private function hasUninvoicedDueFirstPayment(PaymentPlan $plan): bool
    {
        return $plan->first_payment_amount !== null
            && $plan->first_due_date !== null
            && now()->startOfDay()->greaterThanOrEqualTo($plan->first_due_date)
            && ! Invoice::query()
                ->where('payment_plan_id', $plan->id)
                ->where('status', '!=', InvoiceStatus::Voided->value)
                ->where('invoice_number', 'like', 'FP-%')
                ->exists();
    }

    /** @param array<int, int> $invoiceIds */
    private function syncInvoiceStatuses(array $invoiceIds): void
    {
        foreach (Invoice::query()->whereIn('id', $invoiceIds)->get() as $invoice) {
            $balance = $this->balances->invoiceBalance($invoice);
            $original = (int) $invoice->items()->sum('amount');
            $invoice->update(['status' => $balance <= 0 ? InvoiceStatus::Paid : ($balance < $original ? InvoiceStatus::PartiallyPaid : InvoiceStatus::Issued)]);
        }
    }
}
