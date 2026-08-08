<?php

namespace App\Services;

use App\Enums\FinancialActorType;
use App\Enums\FinancialEffectComponent;
use App\Enums\FinancialEffectType;
use App\Enums\FinancialTransactionType;
use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Financial\PostingEffect;
use App\Models\FinancialTransaction;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PaymentPlan;
use App\Models\PaymentPlanBillingTerm;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ManualInvoiceService
{
    public function __construct(
        private readonly FinancialPostingService $posting,
        private readonly FinancialBalanceService $balances,
    ) {}

    /** @param array<int, array{type: string, description: string, amount: int}> $items */
    public function issue(PaymentPlan $plan, User $actor, DateTimeInterface|string $issueDate, array $items): Invoice
    {
        return DB::transaction(function () use ($plan, $actor, $issueDate, $items): Invoice {
            $lockedPlan = PaymentPlan::query()->lockForUpdate()->findOrFail($plan->id);
            if (! in_array($lockedPlan->status, ['active', 'paused'], true)) {
                throw ValidationException::withMessages(['payment_plan' => 'Only an active or paused payment plan may receive an invoice.']);
            }

            $principal = collect($items)->where('type', 'principal')->sum('amount');
            if ($principal > $this->balances->contractBalance($lockedPlan)) {
                throw ValidationException::withMessages(['items' => 'Plan-payment line items cannot exceed the remaining contract balance.']);
            }

            $terms = PaymentPlanBillingTerm::query()
                ->where('payment_plan_id', $lockedPlan->id)
                ->whereDate('effective_from', '<=', Carbon::parse($issueDate))
                ->where(fn ($query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', Carbon::parse($issueDate)))
                ->latest('effective_from')
                ->first() ?? $lockedPlan->currentBillingTerms()->firstOrFail();
            $issue = Carbon::parse($issueDate);
            do {
                $invoiceNumber = 'M'.$lockedPlan->id.'-'.$issue->format('ymd').'-'.Str::upper(Str::random(2));
            } while (Invoice::query()->where('invoice_number', $invoiceNumber)->exists());

            $invoice = Invoice::query()->create([
                'payment_plan_id' => $lockedPlan->id,
                'invoice_number' => $invoiceNumber,
                'period_start' => null,
                'period_end' => null,
                'issue_date' => $issue,
                'due_date' => $issue->copy()->addDays((int) $terms->due_days_after_issue),
                'status' => InvoiceStatus::Issued,
                'issued_at' => now(),
                'created_by_user_id' => $actor->id,
                'generation_source' => 'manual',
            ]);

            foreach (array_values($items) as $index => $data) {
                [$itemType, $component] = match ($data['type']) {
                    'principal' => [InvoiceItemType::ScheduledPurchasePayment, FinancialEffectComponent::ScheduledPurchasePayment],
                    'fee' => [InvoiceItemType::AdministrativeFee, FinancialEffectComponent::AdministrativeFee],
                    default => [InvoiceItemType::Other, FinancialEffectComponent::Other],
                };
                $this->posting->post(
                    $lockedPlan,
                    FinancialTransactionType::InvoiceCharge,
                    $data['amount'],
                    $issue,
                    FinancialActorType::Administrator,
                    function (FinancialTransaction $transaction) use ($invoice, $data, $itemType, $component, $index): array {
                        $item = InvoiceItem::query()->create([
                            'invoice_id' => $invoice->id,
                            'source_transaction_id' => $transaction->id,
                            'item_type' => $itemType,
                            'description' => trim($data['description']),
                            'standard_amount' => $data['amount'],
                            'amount' => $data['amount'],
                            'waived_amount' => 0,
                            'display_order' => $index + 1,
                        ]);

                        return [new PostingEffect(
                            FinancialEffectType::InvoiceDue,
                            $data['amount'],
                            $component,
                            invoiceId: $invoice->id,
                            invoiceItemId: $item->id,
                            description: $item->description.' due',
                        )];
                    },
                    actor: $actor,
                    invoice: $invoice,
                    idempotencyKey: "manual-invoice:{$invoice->uuid}:item:{$index}",
                    description: trim($data['description']),
                );
            }

            return $invoice->load('items', 'transactions.effects');
        }, 3);
    }
}
