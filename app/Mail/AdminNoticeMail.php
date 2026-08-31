<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminNoticeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $noticeSubject,
        public readonly string $noticeMessage,
        public readonly ?string $adminUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->noticeSubject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.admin-notice');
    }
}
