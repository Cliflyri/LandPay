<?php

namespace App\Services;

use App\Enums\FinancialActorType;
use App\Enums\FinancialEffectComponent;
use App\Enums\FinancialEffectType;
use App\Enums\FinancialTransactionType;
use App\Financial\PostingEffect;
use App\Models\FinancialTransaction;
use App\Models\Invoice;
use App\Models\PaymentPlan;
use App\Models\TransactionEffect;
use App\Models\User;
use Closure;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinancialPostingService
{
    public function __construct(private readonly FinancialBalanceService $balances) {}

    /**
     * @param  array<int, PostingEffect>|Closure(FinancialTransaction): array<int, PostingEffect>  $effects
     * @param  array<string, mixed>|null  $metadata
     */
    public function post(
        PaymentPlan $plan,
        FinancialTransactionType $type,
        int $grossAmount,
        DateTimeInterface|string $effectiveDate,
        FinancialActorType $actorType,
        array|Closure $effects,
        ?User $actor = null,
        ?Invoice $invoice = null,
        ?string $idempotencyKey = null,
        ?string $description = null,
        ?string $reason = null,
        ?array $metadata = null,
        ?FinancialTransaction $reversalOf = null,
    ): FinancialTransaction {
        return DB::transaction(function () use ($plan, $type, $grossAmount, $effectiveDate, $actorType, $effects, $actor, $invoice, $idempotencyKey, $description, $reason, $metadata, $reversalOf) {
            $lockedPlan = PaymentPlan::query()->lockForUpdate()->findOrFail($plan->id);
            $this->validateHeader($lockedPlan, $type, $grossAmount, $actorType, $actor, $invoice, $reason);
            if (($type === FinancialTransactionType::Reversal) !== ($reversalOf !== null) || ($reversalOf !== null && $reversalOf->payment_plan_id !== $lockedPlan->id)) { throw ValidationException::withMessages(['reversal' => 'A reversal must reference one transaction on this payment plan.']); }

            if ($idempotencyKey !== null) {
                $existing = FinancialTransaction::query()->where('idempotency_key', $idempotencyKey)->first();
                if ($existing !== null) {
                    if ($existing->payment_plan_id !== $lockedPlan->id || $existing->type !== $type || $existing->gross_amount !== $grossAmount) {
                        throw ValidationException::withMessages(['idempotency_key' => 'The idempotency key was already used for a different financial posting.']);
                    }

                    return $existing->load('effects');
                }
            }

            $transaction = FinancialTransaction::query()->create([
                'payment_plan_id' => $lockedPlan->id,
                'invoice_id' => $invoice?->id,
                'type' => $type,
                'gross_amount' => $grossAmount,
                'effective_date' => $effectiveDate,
                'posted_at' => now(),
                'description' => $description,
                'reason' => $reason,
                'actor_type' => $actorType,
                'posted_by_user_id' => $actorType === FinancialActorType::Administrator ? $actor?->id : null,
                'reversal_of_transaction_id' => $reversalOf?->id,
                'idempotency_key' => $idempotencyKey,
                'metadata' => $metadata,
            ]);

            $resolvedEffects = $effects instanceof Closure ? $effects($transaction) : $effects;
            $this->validateEffects($lockedPlan, $type, $grossAmount, $invoice, $resolvedEffects);

            foreach ($resolvedEffects as $effect) {
                TransactionEffect::query()->create([
                    'financial_transaction_id' => $transaction->id,
                    'effect_type' => $effect->type,
                    'invoice_id' => $effect->invoiceId,
                    'amount_delta' => $effect->amountDelta,
                    'component' => $effect->component,
                    'invoice_item_id' => $effect->invoiceItemId,
                    'fee_assessment_id' => $effect->feeAssessmentId,
                    'description' => $effect->description,
                ]);
            }

            return $transaction->load('effects');
        }, 3);
    }

    private function validateHeader(PaymentPlan $plan, FinancialTransactionType $type, int $grossAmount, FinancialActorType $actorType, ?User $actor, ?Invoice $invoice, ?string $reason): void
    {
        if ($grossAmount < 0) {
            throw ValidationException::withMessages(['gross_amount' => 'Gross amount cannot be negative.']);
        }
        if ($actorType === FinancialActorType::Administrator && $actor === null) {
            throw ValidationException::withMessages(['actor' => 'An administrator actor is required.']);
        }
        if ($invoice !== null && $invoice->payment_plan_id !== $plan->id) {
            throw ValidationException::withMessages(['invoice' => 'The invoice does not belong to this payment plan.']);
        }
        if (in_array($type, [FinancialTransactionType::Adjustment, FinancialTransactionType::Reversal, FinancialTransactionType::Refund, FinancialTransactionType::WriteOff], true) && blank($reason)) {
            throw ValidationException::withMessages(['reason' => 'A reason is required for this transaction type.']);
        }
    }

    /** @param array<int, PostingEffect> $effects */
    private function validateEffects(PaymentPlan $plan, FinancialTransactionType $type, int $grossAmount, ?Invoice $invoice, array $effects): void
    {
        if ($effects === [] && ! ($type === FinancialTransactionType::RecurringFee && $grossAmount === 0)) {
            throw ValidationException::withMessages(['effects' => 'At least one financial effect is required.']);
        }

        $purchaseDelta = 0;
        $creditDelta = 0;
        $invoiceDeltas = [];

        foreach ($effects as $effect) {
            if (! $effect instanceof PostingEffect || $effect->amountDelta === 0) {
                throw ValidationException::withMessages(['effects' => 'Every effect must be a nonzero PostingEffect.']);
            }
            $this->validateEffectForType($type, $effect);

            if ($effect->type === FinancialEffectType::InvoiceDue) {
                if ($effect->invoiceId === null || ($invoice !== null && $effect->invoiceId !== $invoice->id)) {
                    throw ValidationException::withMessages(['effects' => 'Invoice effects must reference the related invoice.']);
                }
                $invoiceDeltas[$effect->invoiceId] = ($invoiceDeltas[$effect->invoiceId] ?? 0) + $effect->amountDelta;
            } elseif ($effect->invoiceId !== null) {
                throw ValidationException::withMessages(['effects' => 'Only invoice-due effects may reference an invoice.']);
            }

            $purchaseDelta += $effect->type === FinancialEffectType::PurchaseBalance ? $effect->amountDelta : 0;
            $creditDelta += $effect->type === FinancialEffectType::ClientCredit ? $effect->amountDelta : 0;
        }

        if ($this->balances->contractBalance($plan) + $purchaseDelta < 0) {
            throw ValidationException::withMessages(['effects' => 'Posting would make the contract balance negative.']);
        }
        if ($this->balances->clientCredit($plan) + $creditDelta < 0) {
            throw ValidationException::withMessages(['effects' => 'Posting would make client credit negative.']);
        }
        foreach ($invoiceDeltas as $invoiceId => $delta) {
            if ($this->balances->invoiceBalance((int) $invoiceId) + $delta < 0) {
                throw ValidationException::withMessages(['effects' => 'Posting would make an invoice balance negative.']);
            }
        }
    }

    private function validateEffectForType(FinancialTransactionType $type, PostingEffect $effect): void
    {
        $valid = match ($type) {
            FinancialTransactionType::OpeningPurchaseBalance => $effect->type === FinancialEffectType::PurchaseBalance
                && $effect->amountDelta > 0
                && in_array($effect->component, [FinancialEffectComponent::PurchasePricePrincipal, FinancialEffectComponent::DocumentationFeePrincipal], true),
            FinancialTransactionType::InvoiceCharge => $effect->type === FinancialEffectType::InvoiceDue && $effect->amountDelta > 0,
            FinancialTransactionType::RecurringFee => $effect->type === FinancialEffectType::InvoiceDue && $effect->amountDelta > 0,
            FinancialTransactionType::Payment => ($effect->type === FinancialEffectType::InvoiceDue && $effect->amountDelta < 0)
                || ($effect->type === FinancialEffectType::PurchaseBalance && $effect->amountDelta < 0)
                || ($effect->type === FinancialEffectType::ClientCredit && $effect->amountDelta > 0),
            FinancialTransactionType::CreditApplication => $effect->amountDelta < 0,
            FinancialTransactionType::Refund => $effect->type === FinancialEffectType::ClientCredit && $effect->amountDelta < 0,
            FinancialTransactionType::WriteOff => in_array($effect->type, [FinancialEffectType::InvoiceDue, FinancialEffectType::PurchaseBalance], true) && $effect->amountDelta < 0,
            FinancialTransactionType::Adjustment, FinancialTransactionType::Reversal => true,
        };

        if (! $valid) {
            throw ValidationException::withMessages(['effects' => "Effect is not permitted for {$type->value}."]);
        }
    }
}
