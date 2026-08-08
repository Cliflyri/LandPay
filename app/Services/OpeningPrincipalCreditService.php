<?php

namespace App\Services;

use App\Enums\FinancialActorType;
use App\Enums\FinancialEffectComponent;
use App\Enums\FinancialEffectType;
use App\Enums\FinancialTransactionType;
use App\Financial\PostingEffect;
use App\Models\FinancialTransaction;
use App\Models\PaymentPlan;
use App\Models\User;
use DateTimeInterface;

class OpeningPrincipalCreditService
{
    public function __construct(
        private readonly FinancialPostingService $posting,
    ) {}

    public function post(
        PaymentPlan $plan,
        User $actor,
        int $amount,
        DateTimeInterface|string $effectiveDate,
    ): ?FinancialTransaction {
        if ($amount <= 0) {
            return null;
        }

        return $this->posting->post(
            $plan,
            FinancialTransactionType::OpeningPrincipalCredit,
            $amount,
            $effectiveDate,
            FinancialActorType::Administrator,
            [
                new PostingEffect(
                    FinancialEffectType::PurchaseBalance,
                    -$amount,
                    FinancialEffectComponent::OpeningPrincipalCredit,
                    description: 'Principal previously paid before LandPay',
                ),
            ],
            actor: $actor,
            idempotencyKey: "opening-principal-credit:{$plan->uuid}",
            description: 'Principal previously paid before LandPay',
            metadata: [
                'opening_principal_credit' => $amount,
            ],
        );
    }
}