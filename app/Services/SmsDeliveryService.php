<?php

namespace App\Services;

use App\Models\{Client, Invoice, InvoiceReminder, SmsDelivery, User};
use Illuminate\Validation\ValidationException;
use Throwable;

class SmsDeliveryService
{
    public function __construct(private readonly TwilioSmsTransport $transport) {}

    public function send(string $phone, string $body, string $type, string $key, ?User $actor = null, ?Client $client = null, ?Invoice $invoice = null, ?InvoiceReminder $reminder = null): SmsDelivery
    {
        $delivery = SmsDelivery::query()->firstOrCreate(['idempotency_key' => $key], [
            'invoice_id' => $invoice?->id, 'invoice_reminder_id' => $reminder?->id,
            'payment_plan_id' => $invoice?->payment_plan_id, 'recipient_client_id' => $client?->id,
            'message_type' => $type, 'recipient_phone' => $phone, 'message_snapshot' => $body,
            'sent_by_user_id' => $actor?->id, 'status' => 'pending',
        ]);
        if (! $delivery->wasRecentlyCreated && in_array($delivery->status, ['pending', 'sent', 'delivered'], true)) return $delivery;
        try {
            $result = $this->transport->send($phone, $body);
            $delivery->update(['twilio_message_sid' => $result['sid'], 'status' => 'sent', 'sent_at' => now(), 'failed_at' => null, 'failure_message' => null]);
        } catch (Throwable $e) {
            $delivery->update(['status' => 'failed', 'failed_at' => now(), 'failure_message' => str($e->getMessage())->limit(500)]);
            report($e);
            throw ValidationException::withMessages(['sms' => 'The text message could not be delivered: '.str($e->getMessage())->limit(200)]);
        }
        return $delivery->fresh();
    }
}
