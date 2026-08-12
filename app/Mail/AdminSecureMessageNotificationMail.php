<?php
namespace App\Mail;
use Illuminate\Bus\Queueable;use Illuminate\Mail\Mailable;use Illuminate\Mail\Mailables\Content;use Illuminate\Mail\Mailables\Envelope;use Illuminate\Queue\SerializesModels;
class AdminSecureMessageNotificationMail extends Mailable{use Queueable,SerializesModels;public function __construct(public readonly string $secureMessagesUrl){}public function envelope():Envelope{return new Envelope(subject:'A secure client message is waiting');}public function content():Content{return new Content(view:'emails.admin-secure-message-notification',text:'emails.admin-secure-message-notification-text');}}
