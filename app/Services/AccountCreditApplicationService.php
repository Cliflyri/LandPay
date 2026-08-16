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

    public function applyToOpenInvoices(PaymentPlan $plan, User $actor): int
    {
        return DB::transaction(function () use ($plan, $actor): int {
            $lockedPlan = PaymentPlan::query()->lockForUpdate()->findOrFail($plan->id);
            $applied = 0;
            $invoices = Invoice::query()->where('payment_plan_id', $lockedPlan->id)
                ->whereIn('status', [InvoiceStatus::Issued->value, InvoiceStatus::PartiallyPaid->value])
                ->orderBy('due_date')->orderBy('id')->lockForUpdate()->get();
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