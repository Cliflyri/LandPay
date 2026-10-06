<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ClosingNotificationMail extends Mailable
{
    public function __construct(public readonly string $planNumber, public readonly string $nextStep = 'Please review your next steps.') {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your LandPay closing information');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.closing-notification');
    }
}
