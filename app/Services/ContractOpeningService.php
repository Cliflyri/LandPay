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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContractOpeningService
{
    public function __construct(private readonly FinancialPostingService $posting) {}

    public function open(
        PaymentPlan $plan,
        User $actor,
        int $purchasePrice,
        int $documentationFeeStandard,
        int $documentationFeeWaived,
        DateTimeInterface|string $effectiveDate,
        ?string $waiverReason = null,
    ): FinancialTransaction {
        if ($purchasePrice <= 0) {
            throw ValidationException::withMessages(['purchase_price' => 'Purchase price must be greater than zero.']);
        }
        if ($documentationFeeStandard < 0 || $documentationFeeWaived < 0 || $documentationFeeWaived > $documentationFeeStandard) {
            throw ValidationException::withMessages(['documentation_fee' => 'Documentation fee waiver must be between zero and the standard fee.']);
        }
        if ($documentationFeeWaived > 0 && blank($waiverReason)) {
            throw ValidationException::withMessages(['waiver_reason' => 'A waiver reason is required.']);
        }

        return DB::transaction(function () use ($plan, $actor, $purchasePrice, $documentationFeeStandard, $documentationFeeWaived, $effectiveDate, $waiverReason) {
            $lockedPlan = PaymentPlan::query()->lockForUpdate()->findOrFail($plan->id);
            if ($lockedPlan->status !== 'draft') {
                throw ValidationException::withMessages(['payment_plan' => 'Only a draft payment plan can receive its opening contract balance.']);
            }

            $documentationFeeCharged = $documentationFeeStandard - $documentationFeeWaived;
            $combinedBalance = $purchasePrice + $documentationFeeCharged;
            $lockedPlan->update(['original_purchase_balance' => $combinedBalance]);

            $effects = [
                new PostingEffect(
                    FinancialEffectType::PurchaseBalance,
                    $purchasePrice,
                    FinancialEffectComponent::PurchasePricePrincipal,
                    description: 'Opening purchase-price principal',
                ),
            ];
            if ($documentationFeeCharged > 0) {
                $effects[] = new PostingEffect(
                    FinancialEffectType::PurchaseBalance,
                    $documentationFeeCharged,
                    FinancialEffectComponent::DocumentationFeePrincipal,
                    description: 'Opening documentation-fee principal',
                );
            }

            return $this->posting->post(
                $lockedPlan,
                FinancialTransactionType::OpeningPurchaseBalance,
                $combinedBalance,
                $effectiveDate,
                FinancialActorType::Administrator,
                $effects,
                actor: $actor,
                idempotencyKey: "opening-contract-balance:{$lockedPlan->uuid}",
                description: 'Opening contract balance',
                metadata: [
                    'purchase_price' => $purchasePrice,
                    'documentation_fee_standard' => $documentationFeeStandard,
                    'documentation_fee_charged' => $documentationFeeCharged,
                    'documentation_fee_waived' => $documentationFeeWaived,
                    'documentation_fee_waiver_reason' => $waiverReason,
                    'documentation_fee_waived_by_user_id' => $documentationFeeWaived > 0 ? $actor->id : null,
                    'documentation_fee_waived_at' => $documentationFeeWaived > 0 ? now()->toIso8601String() : null,
                ],
            );
        }, 3);
    }
}
