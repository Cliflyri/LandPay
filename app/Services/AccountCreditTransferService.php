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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountCreditTransferService
{
    public function destinations(PaymentPlan $source): Builder
    {
        $current = fn ($q) => $q->whereDate('effective_from', '<=', today())
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', today()));
        $clients = $source->memberships()->where($current)->pluck('client_id');
        return PaymentPlan::query()->whereKeyNot($source->id)
            ->whereHas('memberships', fn ($q) => $q->whereIn('client_id', $clients)->where($current))->orderBy('plan_number');
    }

    public function transfer(PaymentPlan $source, int $destinationId, int $amount, User $actor, string $token): void
    {
        DB::transaction(function () use ($source, $destinationId, $amount, $actor, $token) {
            $plans = PaymentPlan::whereIn('id', [$source->id, $destinationId])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $source = $plans->get($source->id);
            if (!$source || !$this->destinations($source)->whereKey($destinationId)->exists()) {
                throw ValidationException::withMessages(['destination_plan_id' => 'Choose another current plan belonging to the same client.']);
            }
            $destination = $plans->get($destinationId);
            $key = 'credit-transfer:'.$token;
            $existing = FinancialTransaction::where('idempotency_key', $key.':out')->first();
            if ($existing) {
                if ($existing->payment_plan_id !== $source->id || $existing->gross_amount !== $amount
                    || (int) ($existing->metadata['destination_plan_id'] ?? 0) !== $destinationId) {
                    throw ValidationException::withMessages(['amount' => 'This transfer reference has already been used.']);
                }
                return;
            }
            if ($amount <= 0 || $amount > app(FinancialBalanceService::class)->clientCredit($source)) {
                throw ValidationException::withMessages(['amount' => 'Enter a positive amount no greater than available unapplied credit.']);
            }
            $metadata = ['transfer_reference' => $token, 'source_plan_id' => $source->id, 'destination_plan_id' => $destinationId];
            foreach ([[$source, -$amount, 'out', 'Unapplied credit transferred to '.$destination->plan_number],
                      [$destination, $amount, 'in', 'Unapplied credit received from '.$source->plan_number]] as [$plan, $delta, $direction, $description]) {
                app(FinancialPostingService::class)->post(
                    $plan, FinancialTransactionType::CreditTransfer, $amount, today(), FinancialActorType::Administrator,
                    [new PostingEffect(FinancialEffectType::ClientCredit, $delta, FinancialEffectComponent::UnappliedCredit)],
                    actor: $actor, idempotencyKey: $key.':'.$direction, description: $description, metadata: $metadata,
                );
            }
        }, 3);
    }
}