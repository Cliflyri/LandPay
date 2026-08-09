<?php

namespace App\Services;

use App\Enums\FinancialActorType;
use App\Enums\FinancialEffectComponent;
use App\Enums\FinancialEffectType;
use App\Enums\FinancialTransactionType;
use App\Financial\PostingEffect;
use App\Models\FinancialTransaction;
use App\Models\PaymentPlan;
use App\Models\TransactionEffect;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Validation\ValidationException;

class OpeningPrincipalCreditService
{
    public function __construct(private readonly FinancialPostingService $posting, private readonly FinancialBalanceService $balances) {}

    public function amount(PaymentPlan|int $plan): int
    {
        $planId = $plan instanceof PaymentPlan ? $plan->id : $plan;
        $delta = (int) TransactionEffect::query()
            ->join('financial_transactions', 'financial_transactions.id', '=', 'transaction_effects.financial_transaction_id')
            ->where('financial_transactions.payment_plan_id', $planId)
            ->where('transaction_effects.effect_type', FinancialEffectType::PurchaseBalance->value)
            ->where('transaction_effects.component', FinancialEffectComponent::OpeningPrincipalCredit->value)
            ->sum('transaction_effects.amount_delta');
        return max(0, -$delta);
    }

    public function post(PaymentPlan $plan, User $actor, int $amount, DateTimeInterface|string $effectiveDate): ?FinancialTransaction
    {
        if ($amount <= 0) return null;
        return $this->posting->post($plan, FinancialTransactionType::OpeningPrincipalCredit, $amount, $effectiveDate, FinancialActorType::Administrator,
            [new PostingEffect(FinancialEffectType::PurchaseBalance, -$amount, FinancialEffectComponent::OpeningPrincipalCredit, description: 'Amount previously paid in')],
            actor: $actor, idempotencyKey: "opening-principal-credit:{$plan->uuid}", description: 'Amount previously paid in', metadata: ['opening_principal_credit' => $amount]);
    }

    public function amend(PaymentPlan $plan, User $actor, int $newAmount, DateTimeInterface|string $effectiveDate, string $reason): ?FinancialTransaction
    {
        $current = $this->amount($plan);
        $difference = $newAmount - $current;
        if ($difference === 0) return null;
        if ($difference > $this->balances->contractBalance($plan)) {
            throw ValidationException::withMessages(['previous_principal_paid' => 'This adjustment cannot exceed the remaining contract balance or create a customer credit.']);
        }
        return $this->posting->post($plan, FinancialTransactionType::Adjustment, abs($difference), $effectiveDate, FinancialActorType::Administrator,
            [new PostingEffect(FinancialEffectType::PurchaseBalance, -$difference, FinancialEffectComponent::OpeningPrincipalCredit, description: 'Amount previously paid in adjustment')],
            actor: $actor, reason: $reason, description: 'Amount previously paid in adjustment', metadata: ['previous_amount' => $current, 'new_amount' => $newAmount]);
    }
}
