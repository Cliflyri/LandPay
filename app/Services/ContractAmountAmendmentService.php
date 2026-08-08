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

class ContractAmountAmendmentService
{
    public function __construct(private readonly FinancialPostingService $posting) {}

    public function amend(
        PaymentPlan $plan,
        User $actor,
        int $purchasePrice,
        int $documentationFeeStandard,
        int $documentationFeeWaived,
        DateTimeInterface|string $effectiveDate,
        string $reason,
        ?string $waiverReason = null,
    ): ?FinancialTransaction {
        if ($purchasePrice <= 0) {
            throw ValidationException::withMessages(['purchase_price' => 'Purchase price must be greater than zero.']);
        }
        if ($documentationFeeStandard < 0 || $documentationFeeWaived < 0 || $documentationFeeWaived > $documentationFeeStandard) {
            throw ValidationException::withMessages(['documentation_fee_waived' => 'The waived amount cannot exceed the documentation fee.']);
        }
        if ($documentationFeeWaived > 0 && blank($waiverReason)) {
            throw ValidationException::withMessages(['documentation_fee_waiver_reason' => 'Enter a reason for the documentation-fee waiver.']);
        }

        return DB::transaction(function () use ($plan, $actor, $purchasePrice, $documentationFeeStandard, $documentationFeeWaived, $effectiveDate, $reason, $waiverReason): ?FinancialTransaction {
            $lockedPlan = PaymentPlan::query()->lockForUpdate()->findOrFail($plan->id);
            $before = [
                'purchase_price' => (int) $lockedPlan->purchase_price,
                'documentation_fee_standard' => (int) $lockedPlan->documentation_fee_standard,
                'documentation_fee_waived' => (int) $lockedPlan->documentation_fee_waived,
                'documentation_fee_waiver_reason' => $lockedPlan->documentation_fee_waiver_reason,
            ];
            $after = [
                'purchase_price' => $purchasePrice,
                'documentation_fee_standard' => $documentationFeeStandard,
                'documentation_fee_waived' => $documentationFeeWaived,
                'documentation_fee_waiver_reason' => $documentationFeeWaived > 0 ? trim((string) $waiverReason) : null,
            ];
            $purchaseDelta = $purchasePrice - $before['purchase_price'];
            $oldDocumentationCharged = $before['documentation_fee_standard'] - $before['documentation_fee_waived'];
            $newDocumentationCharged = $documentationFeeStandard - $documentationFeeWaived;
            $documentationDelta = $newDocumentationCharged - $oldDocumentationCharged;
            $effects = [];
            if ($purchaseDelta !== 0) {
                $effects[] = new PostingEffect(FinancialEffectType::PurchaseBalance, $purchaseDelta, FinancialEffectComponent::PurchasePricePrincipal, description: 'Purchase-price amendment');
            }
            if ($documentationDelta !== 0) {
                $effects[] = new PostingEffect(FinancialEffectType::PurchaseBalance, $documentationDelta, FinancialEffectComponent::DocumentationFeePrincipal, description: 'Documentation-fee amendment');
            }
            $transaction = $effects === [] ? null : $this->posting->post(
                $lockedPlan,
                FinancialTransactionType::Adjustment,
                abs($purchaseDelta) + abs($documentationDelta),
                $effectiveDate,
                FinancialActorType::Administrator,
                $effects,
                actor: $actor,
                description: 'Contract amount amendment',
                reason: trim($reason),
                metadata: ['contract_amounts_before' => $before, 'contract_amounts_after' => $after],
            );
            $lockedPlan->update($after + ['original_purchase_balance' => $purchasePrice + $newDocumentationCharged]);

            return $transaction;
        }, 3);
    }
}
