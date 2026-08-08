<?php

namespace App\Services;

use App\Enums\FinancialActorType;
use App\Enums\FinancialEffectComponent;
use App\Enums\FinancialEffectType;
use App\Enums\FinancialTransactionType;
use App\Enums\InvoiceStatus;
use App\Financial\PostingEffect;
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

    public function void(Invoice $invoice, User $actor, string $reason): Invoice
    {
        return DB::transaction(function () use ($invoice, $actor, $reason): Invoice {
            $lockedInvoice = Invoice::query()->with('items')->lockForUpdate()->findOrFail($invoice->id);

            if ($lockedInvoice->status === InvoiceStatus::Voided) {
                return $lockedInvoice;
            }

            $balance = $this->balances->invoiceBalance($lockedInvoice);
            $originalAmount = (int) $lockedInvoice->items->sum('amount');

            if ($balance <= 0) {
                throw ValidationException::withMessages(['invoice' => 'Only an invoice with an outstanding balance can be deleted.']);
            }
            if ($balance !== $originalAmount) {
                throw ValidationException::withMessages(['invoice' => 'A partially paid invoice cannot be deleted because it already has payment history.']);
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
                reason: $reason,
            );

            $lockedInvoice->update([
                'status' => InvoiceStatus::Voided,
                'operationally_closed_at' => now(),
            ]);

            return $lockedInvoice->fresh(['items', 'transactions.effects']);
        }, 3);
    }
}
