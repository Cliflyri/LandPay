<?php

namespace App\Services;

use App\Mail\TemplatedInvoiceMail;
use App\Models\EmailDelivery;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class InvoiceEmailService
{
    public function __construct(
        private readonly FinancialBalanceService $balances,
        private readonly InvoiceReminderService $recipients,
        private readonly EmailTemplateService $templates,
        private readonly InvoiceAccessLinkService $links,
    ) {}

    public function send(Invoice $invoice, User $actor, string $format = 'both'): EmailDelivery
    {
        $balance = $this->balances->invoiceBalance($invoice);
        $invoice->loadMissing('paymentPlan.memberships.client', 'items');
        $membership = $this->recipients->recipientMembership($invoice);
        $client = $membership?->client;
        if ($client === null || blank($client->email) || filter_var($client->email, FILTER_VALIDATE_EMAIL) === false) {
            throw ValidationException::withMessages(['recipient' => 'No valid invoice-recipient email is configured for this payment plan.']);
        }
        if (! in_array($format, ['inline', 'pdf', 'both'], true)) {
            throw ValidationException::withMessages(['delivery_format' => 'Choose inline, PDF attachment, or both.']);
        }
        $secureUrl = $this->links->url($this->links->activeOrCreate($invoice, $client, $actor));
        $rendered = $this->templates->render('invoice-email', $invoice, $client, $balance, $secureUrl);
        $delivery = EmailDelivery::query()->create([
            'invoice_id' => $invoice->id,
            'payment_plan_id' => $invoice->payment_plan_id,
            'recipient_client_id' => $client->id,
            'sent_by_user_id' => $actor->id,
            'template_slug' => 'invoice-email',
            'recipient_email' => strtolower(trim($client->email)),
            'subject_snapshot' => $rendered['subject'],
            'body_snapshot' => $rendered['body'],
            'delivery_format' => $format,
            'status' => 'pending',
        ]);
        try {
            Mail::to($delivery->recipient_email)->send(new TemplatedInvoiceMail($invoice, $rendered['subject'], $rendered['body'], $format, $balance, $secureUrl, $rendered['uses_magic_invoice_link']));
            $delivery->update(['status' => 'sent', 'sent_at' => now()]);
        } catch (Throwable $exception) {
            $delivery->update(['status' => 'failed', 'failed_at' => now(), 'failure_message' => str($exception->getMessage())->limit(500)]);
            throw ValidationException::withMessages(['email' => 'The invoice email could not be delivered. Review the mail configuration and try again.']);
        }
        return $delivery;
    }
}
