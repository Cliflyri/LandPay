<?php

namespace App\Services;

use App\Enums\FinancialEffectComponent;
use App\Enums\FinancialEffectType;
use App\Enums\FinancialTransactionType;
use App\Models\Invoice;
use App\Models\PaymentPlan;
use App\Models\TransactionEffect;

class FinancialBalanceService
{
    public function contractBalance(PaymentPlan|int $plan): int
    {
        return $this->planEffectSum($plan, FinancialEffectType::PurchaseBalance);
    }

    public function clientCredit(PaymentPlan|int $plan): int
    {
        return $this->planEffectSum($plan, FinancialEffectType::ClientCredit);
    }

    public function invoiceBalance(Invoice|int $invoice): int
    {
        $invoiceId = $invoice instanceof Invoice ? $invoice->id : $invoice;

        return (int) TransactionEffect::query()
            ->where('effect_type', FinancialEffectType::InvoiceDue->value)
            ->where('invoice_id', $invoiceId)
            ->sum('amount_delta');
    }

    public function invoiceCreditApplied(Invoice|int $invoice): int
    {
        $invoiceId = $invoice instanceof Invoice ? $invoice->id : $invoice;
        $amount = (int) TransactionEffect::query()
            ->join('financial_transactions', 'financial_transactions.id', '=', 'transaction_effects.financial_transaction_id')
            ->where('financial_transactions.invoice_id', $invoiceId)
            ->where('financial_transactions.type', FinancialTransactionType::CreditApplication->value)
            ->where('transaction_effects.effect_type', FinancialEffectType::ClientCredit->value)
            ->sum('transaction_effects.amount_delta');
        return max(0, -$amount);
    }

    public function administratorPaidInValue(PaymentPlan|int $plan): int
    {
        $planId = $plan instanceof PaymentPlan ? $plan->id : $plan;
        $paymentDelta = (int) TransactionEffect::query()
            ->join('financial_transactions', 'financial_transactions.id', '=', 'transaction_effects.financial_transaction_id')
            ->where('financial_transactions.payment_plan_id', $planId)
            ->where('transaction_effects.effect_type', FinancialEffectType::PurchaseBalance->value)
            ->where('transaction_effects.component', FinancialEffectComponent::PurchasePricePrincipal->value)
            ->whereIn('financial_transactions.type', [
                FinancialTransactionType::Payment->value,
                FinancialTransactionType::CreditApplication->value,
                FinancialTransactionType::Reversal->value,
            ])
            ->sum('transaction_effects.amount_delta');
        $previousPaidDelta = (int) TransactionEffect::query()
            ->join('financial_transactions', 'financial_transactions.id', '=', 'transaction_effects.financial_transaction_id')
            ->where('financial_transactions.payment_plan_id', $planId)
            ->where('transaction_effects.effect_type', FinancialEffectType::PurchaseBalance->value)
            ->where('transaction_effects.component', FinancialEffectComponent::OpeningPrincipalCredit->value)
            ->sum('transaction_effects.amount_delta');

        return max(0, -($paymentDelta + $previousPaidDelta));
    }

    private function planEffectSum(PaymentPlan|int $plan, FinancialEffectType $type): int
    {
        $planId = $plan instanceof PaymentPlan ? $plan->id : $plan;

        return (int) TransactionEffect::query()
            ->join('financial_transactions', 'financial_transactions.id', '=', 'transaction_effects.financial_transaction_id')
            ->where('financial_transactions.payment_plan_id', $planId)
            ->where('transaction_effects.effect_type', $type->value)
            ->sum('transaction_effects.amount_delta');
    }
}
