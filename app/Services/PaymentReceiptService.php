<?php

namespace App\Services;

use App\Mail\PaymentReceiptMail;
use App\Models\AppSetting;
use App\Models\EmailDelivery;
use App\Models\Payment;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class PaymentReceiptService
{
    public function __construct(private readonly FinancialBalanceService $balances, private readonly EmailTemplateService $templates) {}

    public function send(Payment $payment, User $actor, bool $reversal = false): EmailDelivery
    {
        $payment->loadMissing('financialTransaction.paymentPlan.memberships.client', 'allocations.invoice', 'allocations.invoiceItem', 'payer');
        $transaction = $payment->financialTransaction;
        $plan = $transaction->paymentPlan;
        if (! $reversal && $transaction->reversedBy()->exists()) {
            throw ValidationException::withMessages(['receipt' => 'A valid receipt cannot be emailed for a reversed payment.']);
        }
        $membership = $plan->memberships->whereNull('effective_to')->first(fn ($item) => $item->receives_invoices && $item->role === 'primary')
            ?? $plan->memberships->whereNull('effective_to')->firstWhere('receives_invoices', true);
        $client = $membership?->client;
        if ($client === null || blank($client->email) || ! filter_var($client->email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages(['recipient' => 'No valid receipt-recipient email is configured for this payment plan.']);
        }
        $contractBalance = $this->balances->contractBalance($plan);
        $variables = [
            'client_name' => $client->organization_name ?: trim($client->first_name.' '.$client->last_name) ?: 'Client',
            'invoice_number' => $payment->allocations->pluck('invoice.invoice_number')->filter()->unique()->join(', ') ?: 'Not applicable',
            'amount_due' => Money::format($payment->allocations->pluck('invoice')->filter()->unique('id')->sum(fn ($invoice) => max(0, $this->balances->invoiceBalance($invoice)))),
            'due_date' => 'Not applicable', 'issue_date' => 'Not applicable',
            'plan_number' => $plan->plan_number, 'plan_description' => $plan->title,
            'payment_amount' => Money::format($payment->gross_amount),
            'payment_date' => $payment->received_date->format('F j, Y'),
            'payment_method' => str($payment->payment_method->value)->replace('_', ' ')->title()->toString(),
            'payment_reference' => $payment->external_reference ?: $transaction->uuid,
            'remaining_contract_balance' => Money::format($contractBalance),
            'company_name' => AppSetting::valueFor('company_name', config('app.name', 'LandPay')),
            'company_email' => AppSetting::valueFor('company_email', ''),
            'company_phone' => AppSetting::valueFor('company_phone', ''),
        ];
        $slug = $reversal ? 'payment-reversal' : 'payment-receipt';
        $rendered = $this->templates->renderVariables($slug, $variables);
        $delivery = EmailDelivery::query()->create([
            'payment_id' => $payment->id, 'payment_plan_id' => $plan->id, 'recipient_client_id' => $client->id,
            'sent_by_user_id' => $actor->id, 'template_slug' => $slug, 'recipient_email' => strtolower(trim($client->email)),
            'subject_snapshot' => $rendered['subject'], 'body_snapshot' => $rendered['body'],
            'delivery_format' => $reversal ? 'inline' : 'both', 'status' => 'pending',
        ]);
        try {
            Mail::to($delivery->recipient_email)->send(new PaymentReceiptMail($payment, $rendered['subject'], $rendered['body'], $contractBalance, $reversal));
            $delivery->update(['status' => 'sent', 'sent_at' => now()]);
        } catch (Throwable $exception) {
            $delivery->update(['status' => 'failed', 'failed_at' => now(), 'failure_message' => str($exception->getMessage())->limit(500)]);
            throw ValidationException::withMessages(['receipt' => 'The payment was saved, but the email could not be delivered. You can resend it from the receipt page.']);
        }
        return $delivery;
    }
}
