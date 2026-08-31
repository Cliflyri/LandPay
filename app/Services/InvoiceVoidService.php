<?php

namespace App\Services;

use App\Enums\FinancialActorType;
use App\Enums\FinancialEffectComponent;
use App\Enums\FinancialEffectType;
use App\Enums\FinancialTransactionType;
use App\Enums\InvoiceStatus;
use App\Financial\PostingEffect;
use App\Models\FinancialTransaction;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceVoidService
{
    public function __construct(
        private readonly FinancialPostingService $posting,
        private readonly FinancialBalanceService $balances,
    ) {}

    public function canVoid(Invoice $invoice): bool
    {
        return $this->voidContext($invoice) !== null;
    }

    public function void(Invoice $invoice, User $actor, string $reason): Invoice
    {
        return DB::transaction(function () use ($invoice, $actor, $reason): Invoice {
            $lockedInvoice = Invoice::query()->with('items')->lockForUpdate()->findOrFail($invoice->id);

            if ($lockedInvoice->status === InvoiceStatus::Voided) return $lockedInvoice;

            $context = $this->voidContext($lockedInvoice, true);
            if ($context === null) {
                throw ValidationException::withMessages(['invoice' => 'Only an unpaid invoice or an invoice reduced solely by account credit can be deleted.']);
            }

            foreach ($context['creditApplications'] as $application) {
                $effects = $application->effects->map(fn ($effect) => new PostingEffect(
                    $effect->effect_type,
                    -$effect->amount_delta,
                    $effect->component,
                    invoiceId: $effect->invoice_id,
                    invoiceItemId: $effect->invoice_item_id,
                    feeAssessmentId: $effect->fee_assessment_id,
                    description: 'Reversal: '.$effect->description,
                ))->all();

                $this->posting->post(
                    $lockedInvoice->paymentPlan,
                    FinancialTransactionType::Reversal,
                    $application->gross_amount,
                    now()->toDateString(),
                    FinancialActorType::Administrator,
                    $effects,
                    actor: $actor,
                    invoice: $lockedInvoice,
                    idempotencyKey: "invoice:void:{$lockedInvoice->uuid}:credit:{$application->id}",
                    description: 'Account credit restored for deleted invoice',
                    reason: trim($reason),
                    reversalOf: $application,
                );
            }

            $balance = $this->balances->invoiceBalance($lockedInvoice);
            if ($balance !== $context['originalAmount']) {
                throw ValidationException::withMessages(['invoice' => 'The invoice history changed while it was being deleted.']);
            }

            $this->posting->post(
                $lockedInvoice->paymentPlan,
                FinancialTransactionType::Adjustment,
                $balance,
                now()->toDateString(),
                FinancialActorType::Administrator,
                [new PostingEffect(
                    FinancialEffectType::InvoiceDue,
                    -$balance,
                    FinancialEffectComponent::Other,
                    invoiceId: $lockedInvoice->id,
                    description: 'Invoice obligation removed',
                )],
                actor: $actor,
                invoice: $lockedInvoice,
                idempotencyKey: "invoice:void:{$lockedInvoice->uuid}",
                description: 'Invoice deleted by administrator',
                reason: trim($reason),
            );

            $lockedInvoice->update(['status' => InvoiceStatus::Voided, 'operationally_closed_at' => now()]);

            return $lockedInvoice->fresh(['items', 'transactions.effects']);
        }, 3);
    }

    private function voidContext(Invoice $invoice, bool $lock = false): ?array
    {
        if ($invoice->status === InvoiceStatus::Voided) return null;

        $query = FinancialTransaction::query()
            ->with('effects')
            ->where('invoice_id', $invoice->id)
            ->whereDoesntHave('reversedBy');
        if ($lock) $query->lockForUpdate();
        $transactions = $query->get();

        $allowed = [
            FinancialTransactionType::InvoiceCharge,
            FinancialTransactionType::RecurringFee,
            FinancialTransactionType::CreditApplication,
        ];
        if ($transactions->contains(fn ($transaction) => ! in_array($transaction->type, $allowed, true))) return null;

        $hasPayments = DB::table('payment_allocations')
            ->join('payments', 'payments.id', '=', 'payment_allocations.payment_id')
            ->join('financial_transactions', 'financial_transactions.id', '=', 'payments.financial_transaction_id')
            ->where('payment_allocations.invoice_id', $invoice->id)
            ->whereNotExists(fn ($reversals) => $reversals->selectRaw('1')->from('financial_transactions as reversals')->whereColumn('reversals.reversal_of_transaction_id', 'financial_transactions.id'))
            ->exists();
        if ($hasPayments) return null;

        $creditApplications = $transactions->where('type', FinancialTransactionType::CreditApplication)->values();
        $creditApplied = -(int) $creditApplications->flatMap->effects
            ->where('effect_type', FinancialEffectType::InvoiceDue)
            ->sum('amount_delta');
        $originalAmount = (int) $invoice->items()->sum('amount');
        $balance = $this->balances->invoiceBalance($invoice);

        if ($originalAmount <= 0 || $balance < 0 || $creditApplied < 0 || $balance + $creditApplied !== $originalAmount) return null;

        return compact('creditApplications', 'originalAmount');
    }
}
