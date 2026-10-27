<?php

namespace App\Services;

use App\Enums\FinancialActorType;
use App\Enums\FinancialEffectComponent;
use App\Enums\FinancialEffectType;
use App\Enums\FinancialTransactionType;
use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Financial\PostingEffect;
use App\Models\Invoice;
use App\Models\PaymentPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AccountCreditApplicationService
{
    public function __construct(private readonly FinancialPostingService $posting, private readonly FinancialBalanceService $balances) {}

    public function preview(PaymentPlan $plan, ?\Illuminate\Support\Collection $invoices = null): array
    {
        $available = max(0, $this->balances->clientCredit($plan));
        $remaining = $available;
        $rows = [];
        $items = [];
        $invoices ??= $this->openInvoices($plan)->get();
        foreach ($invoices as $invoice) {
            $balance = max(0, $this->balances->invoiceBalance($invoice));
            if ($balance === 0) continue;
            $applied = min($remaining, $balance);
            $rows[] = [
                'id' => $invoice->id, 'number' => $invoice->invoice_number,
                'due_date' => $invoice->due_date?->toDateString(),
                'balance' => $balance, 'applied' => $applied, 'remaining' => $balance - $applied,
            ];
            $items[$invoice->id] = $invoice->items()->orderBy('id')->get(['id', 'item_type', 'amount', 'display_order'])->toArray();
            $remaining -= $applied;
        }
        $preview = ['plan_id' => $plan->id, 'available' => $available, 'rows' => $rows,
            'applied' => $available - $remaining, 'remaining' => $remaining];
        $preview['token'] = hash_hmac('sha256', json_encode([$preview, $items], JSON_THROW_ON_ERROR), config('app.key'));
        return $preview;
    }

    private function openInvoices(PaymentPlan $plan): \Illuminate\Database\Eloquent\Builder
    {
        return Invoice::query()->where('payment_plan_id', $plan->id)
            ->whereIn('status', [InvoiceStatus::Issued->value, InvoiceStatus::PartiallyPaid->value])
            ->orderBy('due_date')->orderBy('id');
    }
    public function applyToOpenInvoices(PaymentPlan $plan, User $actor, string $previewToken): int
    {
        return DB::transaction(function () use ($plan, $actor, $previewToken): int {
            $lockedPlan = PaymentPlan::query()->lockForUpdate()->findOrFail($plan->id);
            $applied = 0;
            $invoices = $this->openInvoices($lockedPlan)->lockForUpdate()->get();
            if (!hash_equals($this->preview($lockedPlan, $invoices)['token'], $previewToken)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'credit_preview' => 'Credit or invoices changed. Open Apply to open invoices again to review the refreshed allocation.',
                ]);
            }
            foreach ($invoices as $invoice) {
                if ($this->balances->clientCredit($lockedPlan) <= 0) break;
                $applied += $this->applyToInvoice($lockedPlan, $invoice, $actor);
            }
            return $applied;
        }, 3);
    }

    public function applyToInvoice(PaymentPlan $plan, Invoice $invoice, ?User $actor, bool $automated = false, ?string $idempotencyKey = null): int
    {
        $available = min($this->balances->clientCredit($plan), $this->balances->invoiceBalance($invoice));
        if ($available <= 0) return 0;
        $effects = [new PostingEffect(FinancialEffectType::ClientCredit, -$available, FinancialEffectComponent::UnappliedCredit, description: 'Account credit applied to invoice '.$invoice->invoice_number)];
        $remaining = $available;
        $items = $invoice->items()->orderByRaw("item_type = ? asc", [InvoiceItemType::ScheduledPurchasePayment->value])->orderBy('display_order')->get();
        foreach ($items as $item) {
            if ($remaining <= 0 || $item->amount <= 0) continue;
            $applied = min($remaining, (int) $item->amount);
            $component = FinancialEffectComponent::tryFrom($item->item_type->value) ?? FinancialEffectComponent::Other;
            $effects[] = new PostingEffect(FinancialEffectType::InvoiceDue, -$applied, $component, invoiceId: $invoice->id, invoiceItemId: $item->id, description: 'Account credit applied');
            if ($item->item_type === InvoiceItemType::ScheduledPurchasePayment) $effects[] = new PostingEffect(FinancialEffectType::PurchaseBalance, -$applied, FinancialEffectComponent::PurchasePricePrincipal, description: 'Account credit applied to principal');
            $remaining -= $applied;
        }
        $this->posting->post($plan, FinancialTransactionType::CreditApplication, $available, $invoice->issue_date, $automated ? FinancialActorType::System : FinancialActorType::Administrator, $effects, actor: $automated ? null : $actor, invoice: $invoice, idempotencyKey: $idempotencyKey, description: 'Account credit applied to invoice');
        $invoice->update(['status' => $this->balances->invoiceBalance($invoice) <= 0 ? InvoiceStatus::Paid : InvoiceStatus::PartiallyPaid]);
        return $available;
    }
}