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
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvoiceEditService
{
    public function __construct(
        private readonly FinancialPostingService $posting,
        private readonly FinancialBalanceService $balances,
    ) {}

    /** @param array<int, array{id?:int|null,type:string,description:string,amount:int}> $items */
    public function update(Invoice $invoice, User $actor, string $issueDate, string $dueDate, array $items): Invoice
    {
        return DB::transaction(function () use ($invoice, $actor, $issueDate, $dueDate, $items): Invoice {
            $locked = Invoice::query()->with(['allItems', 'paymentPlan'])->lockForUpdate()->findOrFail($invoice->id);
            if ($locked->status === InvoiceStatus::Voided) {
                throw ValidationException::withMessages(['invoice' => 'A voided invoice cannot be edited.']);
            }

            $active = $locked->allItems->whereNull('retired_at')->keyBy('id');
            $submittedIds = collect($items)->pluck('id')->filter()->map(fn ($id) => (int) $id);
            if ($submittedIds->diff($active->keys())->isNotEmpty()) {
                throw ValidationException::withMessages(['items' => 'One or more invoice items are invalid.']);
            }

            if (collect($items)->contains(fn (array $item): bool =>
                $item['amount'] <= 0 && $item['type'] !== InvoiceItemType::Other->value
            )) {
                throw ValidationException::withMessages(['items' => 'Zero and negative line items must use the Other / adjustment type.']);
            }

            $finalTotal = (int) collect($items)->sum('amount');
            if ($finalTotal <= 0) {
                throw ValidationException::withMessages(['items' => 'The invoice total must remain greater than zero.']);
            }
            $applied = max(0, (int) $active->sum('amount') - $this->balances->invoiceBalance($locked));
            if ($finalTotal < $applied) {
                throw ValidationException::withMessages(['items' => 'The invoice total cannot be reduced below the amount already applied.']);
            }

            $existingPrincipal = (int) $active->where('item_type', InvoiceItemType::ScheduledPurchasePayment)->sum('amount');
            $finalPrincipal = (int) collect($items)->where('type', InvoiceItemType::ScheduledPurchasePayment->value)->sum('amount');
            if ($finalPrincipal > max($existingPrincipal, $this->balances->contractBalance($locked->paymentPlan))) {
                throw ValidationException::withMessages(['items' => 'Plan-payment items exceed the available contract balance.']);
            }

            $retire = $active->reject(function (InvoiceItem $item) use ($items): bool {
                return collect($items)->contains(fn (array $data) =>
                    (int) ($data['id'] ?? 0) === $item->id
                    && $this->typeFor($data['type']) === $item->item_type
                    && trim($data['description']) === $item->description
                    && $data['amount'] === (int) $item->amount
                );
            });
            $add = collect($items)->reject(function (array $data) use ($active): bool {
                $item = $active->get((int) ($data['id'] ?? 0));
                return $item
                    && $this->typeFor($data['type']) === $item->item_type
                    && trim($data['description']) === $item->description
                    && $data['amount'] === (int) $item->amount;
            })->values();

            if ($retire->isNotEmpty() || $add->isNotEmpty()) {
                $delta = (int) $add->sum('amount') - (int) $retire->sum('amount');
                $this->posting->post(
                    $locked->paymentPlan,
                    FinancialTransactionType::Adjustment,
                    abs($delta),
                    $issueDate,
                    FinancialActorType::Administrator,
                    function (FinancialTransaction $transaction) use ($locked, $retire, $add): array {
                        $effects = [];
                        foreach ($retire as $item) {
                            DB::table('invoice_items')->where('id', $item->id)->update([
                                'retired_at' => now(),
                                'late_fee_stage' => null,
                            ]);
                            if ((int) $item->amount !== 0) {
                                $effects[] = new PostingEffect(
                                    FinancialEffectType::InvoiceDue,
                                    -(int) $item->amount,
                                    $this->componentFor($item->item_type),
                                    invoiceId: $locked->id,
                                    invoiceItemId: $item->id,
                                    description: 'Invoice item removed',
                                );
                            }
                        }
                        foreach ($add as $index => $data) {
                            $type = $this->typeFor($data['type']);
                            $item = InvoiceItem::query()->create([
                                'invoice_id' => $locked->id,
                                'source_transaction_id' => $transaction->id,
                                'item_type' => $type,
                                'description' => trim($data['description']),
                                'standard_amount' => $data['amount'],
                                'amount' => $data['amount'],
                                'waived_amount' => 0,
                                'display_order' => $index + 1,
                            ]);
                            if ($data['amount'] !== 0) {
                                $effects[] = new PostingEffect(
                                    FinancialEffectType::InvoiceDue,
                                    $data['amount'],
                                    $this->componentFor($type),
                                    invoiceId: $locked->id,
                                    invoiceItemId: $item->id,
                                    description: $item->description.' due',
                                );
                            }
                        }
                        return $effects;
                    },
                    actor: $actor,
                    invoice: $locked,
                    idempotencyKey: 'invoice:edit:'.$locked->uuid.':'.Str::uuid(),
                    description: 'Invoice edited',
                    reason: 'Invoice edited by administrator',
                );
            }

            $locked->update(['issue_date' => $issueDate, 'due_date' => $dueDate]);
            $locked->refresh();
            $balance = $this->balances->invoiceBalance($locked);
            $locked->update([
                'status' => $balance <= 0
                    ? InvoiceStatus::Paid
                    : ($applied > 0 ? InvoiceStatus::PartiallyPaid : InvoiceStatus::Issued),
            ]);

            return $locked->fresh(['items', 'paymentPlan']);
        }, 3);
    }

    private function typeFor(string $type): InvoiceItemType
    {
        return InvoiceItemType::from($type);
    }

    private function componentFor(InvoiceItemType $type): FinancialEffectComponent
    {
        return match ($type) {
            InvoiceItemType::ScheduledPurchasePayment => FinancialEffectComponent::ScheduledPurchasePayment,
            InvoiceItemType::MonthlyServiceFee => FinancialEffectComponent::MonthlyServiceFee,
            InvoiceItemType::LateFeeStageOne => FinancialEffectComponent::LateFeeStageOne,
            InvoiceItemType::LateFeeStageTwo => FinancialEffectComponent::LateFeeStageTwo,
            InvoiceItemType::AdministrativeFee, InvoiceItemType::DocumentationFee => FinancialEffectComponent::AdministrativeFee,
            default => FinancialEffectComponent::Other,
        };
    }
}