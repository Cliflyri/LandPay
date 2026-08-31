<?php

namespace App\Services;

use App\Enums\FinancialActorType;
use App\Enums\FinancialEffectComponent;
use App\Enums\FinancialEffectType;
use App\Enums\FinancialTransactionType;
use App\Enums\InvoiceItemType;
use App\Enums\OverpaymentDisposition;
use App\Enums\PaymentMethod;
use App\Financial\PostingEffect;
use App\Models\AdminNotice;
use App\Models\AppSetting;
use App\Models\ClientPaymentIntent;
use App\Models\FinancialTransaction;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class SquareCardPaymentService
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly FinancialPostingService $posting,
        private readonly FinancialBalanceService $balances,
    ) {}

    public function pay(ClientPaymentIntent $intent, string $sourceId): ClientPaymentIntent
    {
        $secret = AppSetting::encryptedValueFor('square_api_secret');
        if (blank($secret)) {
            throw ValidationException::withMessages(['method' => 'Square is not connected.']);
        }

        $feeInvoice = $intent->processing_fee_amount > 0 ? $this->feeInvoice($intent) : null;
        $sandbox = AppSetting::valueFor('square_environment', 'sandbox') !== 'live';
        $url = ($sandbox ? 'https://connect.squareupsandbox.com' : 'https://connect.squareup.com').'/v2/payments';
        $response = Http::withToken($secret)->withHeaders(['Square-Version' => '2026-07-15'])->post($url, [
            'source_id' => $sourceId,
            'idempotency_key' => $intent->uuid,
            'amount_money' => ['amount' => $intent->amount, 'currency' => 'USD'],
            'location_id' => AppSetting::valueFor('square_public_id', ''),
            'reference_id' => $intent->uuid,
            'note' => 'LandPay plan '.$intent->paymentPlan->plan_number,
            'autocomplete' => true,
        ]);
        $payment = $response->json('payment');
        if (! $response->successful() || ($payment['status'] ?? null) !== 'COMPLETED') {
            $errors = collect($response->json('errors', []))->map(fn ($error) => collect($error)->only(['category', 'code', 'detail', 'field'])->all())->values()->all();
            Log::warning('Square card payment failed.', ['intent_uuid' => $intent->uuid, 'http_status' => $response->status(), 'errors' => $errors]);
            $intent->update(['status' => 'failed']);
            throw ValidationException::withMessages(['method' => collect($errors)->pluck('detail')->filter()->first() ?: 'Square could not complete this card payment.']);
        }

        $intent->update([
            'provider_payment_id' => $payment['id'],
            'card_type' => strtoupper((string) ($payment['card_details']['card']['card_type'] ?? $intent->card_type)),
            'status' => 'checkout_pending',
        ]);

        try {
            return DB::transaction(function () use ($intent, $feeInvoice, $payment): ClientPaymentIntent {
                $locked = ClientPaymentIntent::query()->lockForUpdate()->findOrFail($intent->id);
                if ($locked->status === 'received') {
                    return $locked;
                }
                if ($locked->processing_fee_amount > 0 && $feeInvoice !== null) {
                    $this->postFee($locked, $feeInvoice);
                }
                $actor = User::query()->where('status', 'active')->oldest()->firstOrFail();
                $posted = $this->payments->post(
                    $locked->paymentPlan,
                    $actor,
                    $locked->amount,
                    'regular',
                    PaymentMethod::Card,
                    now()->toDateString(),
                    $locked->client_id,
                    'square:'.$payment['id'],
                    $locked->overpayment_disposition ? OverpaymentDisposition::from($locked->overpayment_disposition) : null,
                    'provider:square:'.$payment['id'],
                    processingFeeAmount: $feeInvoice === null ? (int) $locked->processing_fee_amount : 0,
                    invoiceId: $locked->invoice_id,
                );
                $locked->update(['status' => 'received', 'payment_id' => $posted->id, 'received_at' => now()]);
                $clientName = trim($locked->client->first_name.' '.$locked->client->last_name);
                AdminNotice::create(['type' => 'online_payment_received', 'client_id' => $locked->client_id, 'client_payment_intent_id' => $locked->id, 'title' => 'Online payment received', 'message' => $clientName.' paid '.Money::format($locked->amount).' by Square on '.$posted->received_date->format('M j, Y').'. Payment posted successfully.']);

                return $locked->fresh();
            }, 3);
        } catch (Throwable $exception) {
            $intent->update(['status' => 'review_required']);
            AdminNotice::create(['type' => 'provider_payment_exception', 'client_id' => $intent->client_id, 'client_payment_intent_id' => $intent->id, 'title' => 'Online payment requires review', 'message' => 'Square charged '.Money::format($intent->amount).' for checkout '.$intent->uuid.', but LandPay could not post it automatically.']);
            report($exception);

            return $intent->fresh();
        }
    }

    private function feeInvoice(ClientPaymentIntent $intent): ?Invoice
    {
        if ($intent->invoice_id) return $intent->invoice;
        return $intent->paymentPlan->invoices()->orderBy('due_date')->orderBy('id')->get()
            ->first(fn (Invoice $invoice) => $this->balances->invoiceBalance($invoice) > 0);
    }

    private function postFee(ClientPaymentIntent $intent, Invoice $invoice): void
    {
        $amount = (int) $intent->processing_fee_amount;
        $this->posting->post(
            $intent->paymentPlan,
            FinancialTransactionType::InvoiceCharge,
            $amount,
            now()->toDateString(),
            FinancialActorType::System,
            function (FinancialTransaction $transaction) use ($invoice, $amount): array {
                $item = InvoiceItem::query()->create([
                    'invoice_id' => $invoice->id,
                    'source_transaction_id' => $transaction->id,
                    'item_type' => InvoiceItemType::AdministrativeFee,
                    'description' => 'Processing Fee',
                    'standard_amount' => $amount,
                    'amount' => $amount,
                    'waived_amount' => 0,
                    'display_order' => ((int) $invoice->allItems()->max('display_order')) + 1,
                ]);

                return [new PostingEffect(FinancialEffectType::InvoiceDue, $amount, FinancialEffectComponent::AdministrativeFee, invoiceId: $invoice->id, invoiceItemId: $item->id, description: 'Processing Fee due')];
            },
            invoice: $invoice,
            idempotencyKey: 'square-processing-fee:'.$intent->uuid,
            description: 'Processing Fee',
            metadata: ['client_payment_intent_id' => $intent->id],
        );
    }
}
