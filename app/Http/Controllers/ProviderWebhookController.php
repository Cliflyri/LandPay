<?php

namespace App\Http\Controllers;

use App\Enums\OverpaymentDisposition;
use App\Enums\PaymentMethod;
use App\Models\AdminNotice;
use App\Models\AppSetting;
use App\Models\ClientPaymentIntent;
use App\Models\User;
use App\Services\PaymentService;
use App\Support\Money;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProviderWebhookController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    public function __invoke(Request $request, string $provider): Response
    {
        abort_unless(in_array($provider, ['square', 'stripe'], true), 404);
        $body = $request->getContent();
        $secret = AppSetting::encryptedValueFor($provider.'_webhook_secret');
        abort_if(blank($secret), 503);
        abort_unless($provider === 'square' ? $this->validSquare($request, $body, $secret) : $this->validStripe($request, $body, $secret), 401);

        $payload = $request->json()->all();
        if ($provider === 'square' && $this->squareAnomaly($payload)) {
            return response('ok', 200);
        }

        $data = $provider === 'square' ? $this->squareData($payload) : $this->stripeData($payload);
        if ($data === null) return response('ignored', 200);
        $intent = ClientPaymentIntent::query()->where('provider_checkout_id', $data['checkout_id'])->first();
        if (! $intent) return response('ignored', 200);
        if ($intent->status === 'received' || filled($intent->provider_payment_id)) return response('ok', 200);
        if ($data['amount'] !== $intent->amount || $data['currency'] !== 'USD') {
            $intent->update(['status' => 'review_required']);
            AdminNotice::create(['type' => 'provider_payment_exception', 'client_id' => $intent->client_id, 'client_payment_intent_id' => $intent->id, 'title' => 'Online payment requires review', 'message' => 'Provider amount or currency did not match LandPay checkout '.$intent->uuid.'.']);
            return response('review', 200);
        }

        $actor = User::query()->where('status', 'active')->oldest()->firstOrFail();
        $payment = $this->payments->post($intent->paymentPlan, $actor, $intent->amount, 'regular', PaymentMethod::Card, now()->toDateString(), $intent->client_id, $provider.':'.$data['payment_id'], $intent->overpayment_disposition ? OverpaymentDisposition::from($intent->overpayment_disposition) : null, 'provider:'.$provider.':'.$data['payment_id']);
        $intent->update(['status' => 'received', 'provider_payment_id' => $data['payment_id'], 'payment_id' => $payment->id, 'received_at' => now()]);
        $clientName = trim($intent->client->first_name.' '.$intent->client->last_name);
        AdminNotice::create(['type' => 'online_payment_received', 'client_id' => $intent->client_id, 'client_payment_intent_id' => $intent->id, 'title' => 'Online payment received', 'message' => $clientName.' paid '.Money::format($intent->amount).' by '.ucfirst($provider).' on '.$payment->received_date->format('M j, Y').'. Payment posted successfully.']);

        return response('ok', 200);
    }

    private function squareAnomaly(array $payload): bool
    {
        $type = (string) ($payload['type'] ?? '');
        $eventId = (string) ($payload['event_id'] ?? '');
        $object = $payload['data']['object'] ?? [];

        if (in_array($type, ['refund.created', 'refund.updated'], true)) {
            $event = $object['refund'] ?? null;
            $paymentId = (string) ($event['payment_id'] ?? '');
            $status = strtoupper((string) ($event['status'] ?? 'UNKNOWN'));
            $amount = (int) ($event['amount_money']['amount'] ?? 0);
            $currency = (string) ($event['amount_money']['currency'] ?? 'USD');
            $title = 'Square refund requires review';
            $message = 'Square reports a '.$status.' refund of '.Money::format($amount).' '.$currency.'. Review the linked payment and manually reconcile Square with LandPay.';
        } elseif (in_array($type, ['dispute.created', 'dispute.state.updated'], true)) {
            $event = $object['dispute'] ?? null;
            $paymentId = (string) ($event['disputed_payment']['payment_id'] ?? '');
            $status = strtoupper((string) ($event['state'] ?? 'UNKNOWN'));
            $amount = (int) ($event['amount_money']['amount'] ?? 0);
            $currency = (string) ($event['amount_money']['currency'] ?? 'USD');
            $title = 'Square payment dispute requires review';
            $message = 'Square reports a '.$status.' dispute for '.Money::format($amount).' '.$currency.'. Review the linked payment and the dispute in Square.';
        } elseif ($type === 'payment.updated' && in_array(strtoupper((string) ($object['payment']['status'] ?? '')), ['CANCELED', 'FAILED'], true)) {
            $event = $object['payment'];
            $paymentId = (string) ($event['id'] ?? '');
            $status = strtoupper((string) $event['status']);
            $amount = (int) ($event['amount_money']['amount'] ?? 0);
            $currency = (string) ($event['amount_money']['currency'] ?? 'USD');
            $title = 'Square payment status requires review';
            $message = 'Square reports the linked payment as '.$status.' for '.Money::format($amount).' '.$currency.'. Review Square and LandPay for consistency.';
        } else {
            return false;
        }

        if ($eventId === '' || $paymentId === '') return true;
        $intent = ClientPaymentIntent::query()->where('provider', 'square')->where('provider_payment_id', $paymentId)->first();
        if (! $intent) return true;

        AdminNotice::query()->firstOrCreate(
            ['provider_event_id' => 'square:'.$eventId],
            [
                'provider_event_type' => $type,
                'type' => 'square_payment_anomaly',
                'client_id' => $intent->client_id,
                'client_payment_intent_id' => $intent->id,
                'title' => $title,
                'message' => $message.' Square payment ID: '.$paymentId.'.',
            ],
        );

        return true;
    }

    private function validSquare(Request $request, string $body, string $secret): bool
    {
        $expected = base64_encode(hash_hmac('sha256', url('/webhooks/square').$body, $secret, true));
        return hash_equals($expected, (string) $request->header('x-square-hmacsha256-signature'));
    }

    private function validStripe(Request $request, string $body, string $secret): bool
    {
        $parts = [];
        foreach (explode(',', (string) $request->header('stripe-signature')) as $part) {
            [$key, $value] = array_pad(explode('=', $part, 2), 2, '');
            $parts[$key] = $value;
        }
        if (empty($parts['t']) || empty($parts['v1']) || abs(time() - (int) $parts['t']) > 300) return false;
        return hash_equals(hash_hmac('sha256', $parts['t'].'.'.$body, $secret), $parts['v1']);
    }

    private function squareData(array $payload): ?array
    {
        $payment = $payload['data']['object']['payment'] ?? null;
        if (($payload['type'] ?? null) !== 'payment.updated' || ($payment['status'] ?? null) !== 'COMPLETED') return null;
        return ['checkout_id' => $payment['order_id'] ?? '', 'payment_id' => $payment['id'] ?? '', 'amount' => (int) ($payment['amount_money']['amount'] ?? 0), 'currency' => $payment['amount_money']['currency'] ?? ''];
    }

    private function stripeData(array $payload): ?array
    {
        $session = $payload['data']['object'] ?? null;
        if (($payload['type'] ?? null) !== 'checkout.session.completed' || ($session['payment_status'] ?? null) !== 'paid') return null;
        return ['checkout_id' => $session['id'] ?? '', 'payment_id' => $session['payment_intent'] ?? ($session['id'] ?? ''), 'amount' => (int) ($session['amount_total'] ?? 0), 'currency' => strtoupper($session['currency'] ?? '')];
    }
}
