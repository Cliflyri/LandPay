<?php
namespace App\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\{Content,Envelope};
use Illuminate\Queue\SerializesModels;
class ClientAnnouncementMail extends Mailable{
 use Queueable,SerializesModels;
 public function __construct(public readonly string $companyName,public readonly string $portalUrl){}
 public function envelope():Envelope{return new Envelope(subject:'New client announcement from '.$this->companyName);}
 public function content():Content{return new Content(view:'emails.client-announcement',text:'emails.client-announcement-text');}
}
