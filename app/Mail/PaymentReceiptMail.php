<?php

namespace App\Mail;

use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Payment $payment,
        public readonly string $renderedSubject,
        public readonly string $renderedBody,
        public readonly int $contractBalance,
        public readonly bool $reversal = false,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->renderedSubject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.payment-receipt', text: 'emails.templated-text');
    }

    public function attachments(): array
    {
        if ($this->reversal) return [];
        $payment = $this->payment->loadMissing('financialTransaction.paymentPlan', 'allocations.invoice', 'allocations.invoiceItem', 'payer');
        $pdf = Pdf::loadView('pdf.payment-receipt', ['payment' => $payment, 'contractBalance' => $this->contractBalance]);
        return [Attachment::fromData(fn () => $pdf->output(), 'receipt-'.$payment->financialTransaction->uuid.'.pdf')->withMime('application/pdf')];
    }
}
