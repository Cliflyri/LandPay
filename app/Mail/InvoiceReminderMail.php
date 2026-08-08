<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\AppSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class InvoiceReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Invoice $invoice,
        public readonly int $balance,
        public readonly string $clientName,
        public readonly ?string $renderedSubject = null,
        public readonly ?string $renderedBody = null,
    ) {}

    public function envelope(): Envelope
    {
        $replyTo = AppSetting::valueFor('reply_to_email');
        return new Envelope(replyTo: $replyTo ? [new Address($replyTo)] : [], subject: $this->renderedSubject ?? 'Payment reminder for invoice '.$this->invoice->invoice_number);
    }

    public function content(): Content
    {
        return $this->renderedBody === null
            ? new Content(view: 'emails.invoice-reminder')
            : new Content(view: 'emails.templated-message', text: 'emails.templated-text');
    }
}
