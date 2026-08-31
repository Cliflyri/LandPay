<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\AppSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class TemplatedInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Invoice $invoice,
        public readonly string $renderedSubject,
        public readonly string $renderedBody,
        public readonly string $deliveryFormat = 'inline',
        public readonly ?int $balance = null,
        public readonly ?string $secureUrl = null,
        public readonly bool $magicLinkEmbedded = false,
    ) {}

    public function envelope(): Envelope
    {
        $replyTo = AppSetting::valueFor('reply_to_email');
        return new Envelope(replyTo: $replyTo ? [new Address($replyTo)] : [], subject: $this->renderedSubject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.templated-invoice', text: 'emails.invoice-text');
    }

    public function attachments(): array
    {
        if (! in_array($this->deliveryFormat, ['pdf', 'both'], true)) {
            return [];
        }
        $invoice = $this->invoice->loadMissing('items', 'paymentPlan.memberships.client');
        $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $invoice, 'balance' => $this->balance]);

        return [Attachment::fromData(fn () => $pdf->output(), $invoice->invoice_number.'.pdf')->withMime('application/pdf')];
    }
}
