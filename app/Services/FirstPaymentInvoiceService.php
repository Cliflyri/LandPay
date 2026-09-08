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
use Illuminate\Validation\ValidationException;

class FirstPaymentInvoiceService
{
    public function __construct(private readonly FinancialPostingService $posting) {}

    public function issue(PaymentPlan $plan, User $actor, int $amount, DateTimeInterface|string $issueDate, DateTimeInterface|string $dueDate, int $documentationFee = 0): Invoice
    {
        if ($amount < 0 || $amount + max(0, $documentationFee) <= 0) {
            throw ValidationException::withMessages(['first_payment_amount' => 'The first invoice must include a down payment or documentation fee.']);
        }

        return DB::transaction(function () use ($plan, $actor, $amount, $issueDate, $dueDate, $documentationFee): Invoice {
            $lockedPlan = PaymentPlan::query()->lockForUpdate()->findOrFail($plan->id);
            if ($lockedPlan->status !== 'active') {
                throw ValidationException::withMessages(['payment_plan' => 'The payment plan must be active before its first-payment invoice is issued.']);
            }

            $active = Invoice::query()
                ->where('payment_plan_id', $lockedPlan->id)
                ->where('status', '!=', InvoiceStatus::Voided->value)
                ->where('invoice_number', 'like', 'FP-%')
                ->with('items')
                ->lockForUpdate()
                ->first();
            if ($active !== null) {
                return $active;
            }

            $baseNumber = 'FP-'.substr(preg_replace('/[^A-Za-z0-9-]/', '-', $lockedPlan->plan_number), 0, 42);
            $invoiceNumber = $baseNumber;
            if (Invoice::query()->where('invoice_number', $invoiceNumber)->exists()) {
                $sequence = 2;
                do {
                    $invoiceNumber = $baseNumber.'-'.$sequence++;
                } while (Invoice::query()->where('invoice_number', $invoiceNumber)->exists());
            }

            $issue = Carbon::parse($issueDate);
            $due = Carbon::parse($dueDate);
            if ($due->lt($issue)) {
                throw ValidationException::withMessages(['first_payment_due_date' => 'The first payment due date cannot be before the issue date.']);
            }
            $terms=PaymentPlanBillingTerm::query()->where('payment_plan_id',$lockedPlan->id)->whereDate('effective_from','<=',$issue)->where(fn($q)=>$q->whereNull('effective_to')->orWhereDate('effective_to','>=',$issue))->latest('effective_from')->first();

            $invoice = Invoice::query()->create([
                'payment_plan_id' => $lockedPlan->id,
                'payment_plan_billing_term_id' => $terms?->id,
                'invoice_number' => $invoiceNumber,
                'issue_date' => $issue,
                'due_date' => $due,
                'status' => InvoiceStatus::Issued,
                'issued_at' => now(),
                'created_by_user_id' => $actor->id,
            ]);

            $total = $amount + max(0, $documentationFee);
            $this->posting->post(
                $lockedPlan,
                FinancialTransactionType::InvoiceCharge,
                $total,
                $issue,
                FinancialActorType::Administrator,
                function (FinancialTransaction $transaction) use ($invoice, $amount, $documentationFee): array {
                    $effects = [];
                    if ($amount > 0) {
                        $item = InvoiceItem::query()->create([
                            'invoice_id' => $invoice->id,
                            'source_transaction_id' => $transaction->id,
                            'item_type' => InvoiceItemType::ScheduledPurchasePayment,
                            'description' => 'Down payment',
                            'standard_amount' => $amount,
                            'amount' => $amount,
                            'waived_amount' => 0,
                            'display_order' => 1,
                        ]);
                        $effects[] = new PostingEffect(
                            FinancialEffectType::InvoiceDue,
                            $amount,
                            FinancialEffectComponent::ScheduledPurchasePayment,
                            invoiceId: $invoice->id,
                            invoiceItemId: $item->id,
                            description: 'Down payment due',
                        );
                    }
                    if ($documentationFee > 0) {
                        $fee = InvoiceItem::query()->create([
                            'invoice_id' => $invoice->id,
                            'source_transaction_id' => $transaction->id,
                            'item_type' => InvoiceItemType::DocumentationFee,
                            'description' => 'Documentation fee',
                            'standard_amount' => $documentationFee,
                            'amount' => $documentationFee,
                            'waived_amount' => 0,
                            'display_order' => 2,
                        ]);
                        $effects[] = new PostingEffect(
                            FinancialEffectType::InvoiceDue,
                            $documentationFee,
                            FinancialEffectComponent::DocumentationFeePrincipal,
                            invoiceId: $invoice->id,
                            invoiceItemId: $fee->id,
                            description: 'Documentation fee due',
                        );
                    }

                    return $effects;
                },
                actor: $actor,
                invoice: $invoice,
                idempotencyKey: "first-payment-invoice:{$lockedPlan->uuid}:{$invoiceNumber}",
                description: 'First payment invoice',
            );

            return $invoice->load('items');
        }, 3);
    }
}
