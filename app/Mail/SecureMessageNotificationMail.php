<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SecureMessageNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly string $secureMessageUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'You have a new secure message from LandPay');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.secure-message-notification',
            text: 'emails.secure-message-notification-text',
        );
    }
}
