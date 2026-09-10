<?php
namespace App\Services;
use App\Models\{AppSetting,Client,Invoice,InvoiceReminder,SmsDelivery,User};
use App\Support\Money;
use Illuminate\Validation\ValidationException;
class InvoiceSmsService {
 public function __construct(private readonly TwilioConfigurationService $configuration,private readonly PhoneNumberService $phones,private readonly InvoiceReminderService $reminders,private readonly FinancialBalanceService $balances,private readonly SmsDeliveryService $deliveries){}
 public function recipient(Invoice $invoice):?Client{$invoice->loadMissing('paymentPlan.memberships.client');return $this->reminders->recipientMembership($invoice)?->client;}
 public function eligible(?Client $client):bool{if(!$client||!$this->configuration->values()['enabled'])return false;$client->loadMissing('smsPreference');$p=$client->smsPreference;$current=$this->phones->e164($client->primary_phone,$client->country_code?:'US');if(!($p?->enabled&&!$p->stopped_at&&$current))return false;if($p->sms_phone_e164!==$current)$p->update(['sms_phone_e164'=>$current]);return true;}
 public function sendInvoiceCreated(Invoice $invoice,?User $actor=null):?SmsDelivery{return $this->send($invoice,'invoice_created','invoice-created:'.$invoice->uuid,$actor);}
 public function sendReminder(Invoice $invoice,?User $actor=null,?InvoiceReminder $reminder=null,?string $key=null):?SmsDelivery{return $this->send($invoice,$reminder?->automated?'automated_reminder':'manual_reminder',$key??'manual-reminder:'.str()->uuid(),$actor,$reminder);}
 private function send(Invoice $invoice,string $type,string $key,?User $actor,?InvoiceReminder $reminder=null):?SmsDelivery{$client=$this->recipient($invoice);if(!$this->eligible($client)){if($actor)throw ValidationException::withMessages(['sms'=>'This client is not currently eligible for text messages.']);return null;}$company=AppSetting::valueFor('company_name',config('app.name','LandPay'));$balance=Money::format($this->balances->invoiceBalance($invoice));$body=$type==='invoice_created'?"{$company}: Invoice {$invoice->invoice_number} for {$balance} is due {$invoice->due_date->format('M j, Y')}. Log in: ".route('portal.login'):"{$company}: Reminder - invoice {$invoice->invoice_number} has {$balance} due {$invoice->due_date->format('M j, Y')}. Log in: ".route('portal.login');return $this->deliveries->send($client->smsPreference->sms_phone_e164,$body,$type,$key,$actor,$client,$invoice,$reminder);}
}
