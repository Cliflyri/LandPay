<?php

namespace App\Services;

use App\Mail\InvoiceReminderMail;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceReminder;
use App\Models\PaymentPlanClient;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class InvoiceReminderService
{
    public function __construct(
        private readonly FinancialBalanceService $balances,
        private readonly EmailTemplateService $templates,
    ) {}

    public function send(Invoice $invoice, ?User $actor, bool $automated = false, ?\Illuminate\Support\Carbon $triggerDate = null, ?string $triggerType = null): InvoiceReminder
    {
        $invoice->loadMissing('paymentPlan.memberships.client');
        $balance = $this->balances->invoiceBalance($invoice);
        if ($balance <= 0) {
            throw ValidationException::withMessages(['invoice' => 'A reminder cannot be sent for an invoice with no balance due.']);
        }
        $membership = $this->recipientMembership($invoice);
        $client = $membership?->client;
        if ($client === null || blank($client->email) || filter_var($client->email, FILTER_VALIDATE_EMAIL) === false) {
            throw ValidationException::withMessages(['recipient' => 'No valid invoice-recipient email is configured for this payment plan.']);
        }
        $rendered = $this->templates->render('payment-reminder', $invoice, $client, $balance);
        $reminder = InvoiceReminder::query()->create([
            'invoice_id' => $invoice->id,
            'payment_plan_id' => $invoice->payment_plan_id,
            'recipient_client_id' => $client->id,
            'recipient_email' => strtolower(trim($client->email)),
            'balance_snapshot' => $balance,
            'status' => 'pending',
            'sent_by_user_id' => $actor?->id,
            'automated' => $automated,
            'trigger_date' => $triggerDate,
            'trigger_type' => $triggerType,
        ]);
        try {
            Mail::to($reminder->recipient_email)->send(new InvoiceReminderMail($invoice, $balance, $this->clientName($client), $rendered['subject'], $rendered['body']));
            $reminder->update(['status' => 'sent', 'sent_at' => now()]);
        } catch (Throwable $exception) {
            $reminder->update(['status' => 'failed', 'failed_at' => now(), 'failure_message' => str($exception->getMessage())->limit(500)]);
            throw ValidationException::withMessages(['reminder' => 'The reminder could not be delivered. Review the mail configuration and try again.']);
        }

        return $reminder;
    }

    public function recipientMembership(Invoice $invoice): ?PaymentPlanClient
    {
        $memberships = $invoice->paymentPlan->memberships->whereNull('effective_to');

        return $memberships->first(fn ($membership) => $membership->receives_invoices && $membership->role === 'primary')
            ?? $memberships->firstWhere('receives_invoices', true);
    }

    private function clientName(Client $client): string
    {
        return $client->organization_name ?: trim($client->first_name.' '.$client->last_name) ?: 'Client';
    }
}
