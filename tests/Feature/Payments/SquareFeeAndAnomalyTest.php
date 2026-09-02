<?php

namespace Tests\Feature\Payments;

use App\Enums\OverpaymentDisposition;
use App\Enums\PaymentAllocationType;
use App\Enums\PaymentMethod;
use App\Models\AdminNotice;
use App\Models\AppSetting;
use App\Models\Client;
use App\Models\ClientPaymentIntent;
use App\Models\PaymentPlan;
use App\Models\PaymentPlanClient;
use App\Models\PortalAccount;
use App\Models\User;
use App\Services\ContractOpeningService;
use App\Services\FinancialBalanceService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SquareFeeAndAnomalyTest extends TestCase
{
    use RefreshDatabase;

    public function test_uninvoiced_processing_fee_is_a_non_principal_payment_allocation_and_reverses_with_payment(): void
    {
        [$admin, $client, $plan] = $this->records();
        $payment = app(PaymentService::class)->post(
            $plan,
            $admin,
            10_320,
            'regular',
            PaymentMethod::Card,
            '2026-08-30',
            $client->id,
            'square:payment-1',
            OverpaymentDisposition::Principal,
            'provider:square:payment-1',
            processingFeeAmount: 320,
        );

        $allocations = $payment->allocations()->orderBy('display_order')->get();
        $this->assertSame([PaymentAllocationType::PurchaseBalance, PaymentAllocationType::ProcessingFee], $allocations->pluck('allocation_type')->all());
        $this->assertSame([10_000, 320], $allocations->pluck('amount')->all());
        $this->assertSame(10_320, $payment->gross_amount);
        $this->assertSame(90_000, app(FinancialBalanceService::class)->contractBalance($plan));

        $this->assertSame(0, app(FinancialBalanceService::class)->clientCredit($plan));
        app(PaymentService::class)->reverse($payment, $admin, 'Square refund reconciled manually.');
        $this->assertSame(100_000, app(FinancialBalanceService::class)->contractBalance($plan));
    }

    public function test_square_checkout_posts_extra_payment_with_fee_when_no_invoice_is_open(): void
    {
        [$admin, $client, $plan, $account] = $this->records(withAccount: true);
        AppSetting::putMany(['square_environment' => 'sandbox', 'square_public_id' => 'LOCATION']);
        AppSetting::putEncrypted('square_api_secret', 'square-secret');
        Http::fake(['connect.squareupsandbox.com/v2/payments' => Http::response(['payment' => ['id' => 'square-payment-1', 'status' => 'COMPLETED', 'card_details' => ['card' => ['card_type' => 'CREDIT']]]], 200)]);
        $intent = ClientPaymentIntent::create([
            'payment_plan_id' => $plan->id,
            'client_id' => $client->id,
            'portal_account_id' => $account->id,
            'method' => 'card',
            'amount' => 10_320,
            'base_amount' => 10_000,
            'processing_fee_amount' => 320,
            'card_type' => 'CREDIT',
            'payment_type' => 'regular',
            'overpayment_disposition' => 'principal',
            'status' => 'announced',
            'provider' => 'square',
        ]);

        $result = app(\App\Services\SquareCardPaymentService::class)->pay($intent, 'token');
        $this->assertSame('received', $result->status);
        $this->assertSame([10_000, 320], $result->payment->allocations()->orderBy('display_order')->pluck('amount')->all());
        $this->assertSame(90_000, app(FinancialBalanceService::class)->contractBalance($plan));
        $this->assertSame(0, app(FinancialBalanceService::class)->clientCredit($plan));
        Http::assertSent(fn ($request) => $request['amount_money']['amount'] === 10_320);
    }

    public function test_square_refund_and_dispute_events_create_deduplicated_review_notices(): void
    {
        [$admin, $client, $plan, $account] = $this->records(withAccount: true);
        AppSetting::putEncrypted('square_webhook_secret', 'square-secret');
        $intent = ClientPaymentIntent::create([
            'payment_plan_id' => $plan->id,
            'client_id' => $client->id,
            'portal_account_id' => $account->id,
            'method' => 'card',
            'amount' => 10_320,
            'base_amount' => 10_000,
            'processing_fee_amount' => 320,
            'payment_type' => 'regular',
            'status' => 'received',
            'provider' => 'square',
            'provider_payment_id' => 'square-payment-1',
        ]);

        $refund = ['event_id' => 'refund-event-1', 'type' => 'refund.updated', 'data' => ['object' => ['refund' => ['payment_id' => 'square-payment-1', 'status' => 'COMPLETED', 'amount_money' => ['amount' => 10_320, 'currency' => 'USD']]]]];
        $this->squareWebhook($refund)->assertOk();
        $this->squareWebhook($refund)->assertOk();

        $dispute = ['event_id' => 'dispute-event-1', 'type' => 'dispute.created', 'data' => ['object' => ['dispute' => ['disputed_payment' => ['payment_id' => 'square-payment-1'], 'state' => 'EVIDENCE_REQUIRED', 'amount_money' => ['amount' => 10_320, 'currency' => 'USD']]]]];
        $this->squareWebhook($dispute)->assertOk();

        $this->assertSame(2, AdminNotice::query()->where('client_payment_intent_id', $intent->id)->count());
        $this->assertDatabaseHas('admin_notices', ['provider_event_id' => 'square:refund-event-1', 'title' => 'Square refund requires review']);
        $this->assertDatabaseHas('admin_notices', ['provider_event_id' => 'square:dispute-event-1', 'title' => 'Square payment dispute requires review']);
    }

    private function squareWebhook(array $payload)
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = base64_encode(hash_hmac('sha256', url('/webhooks/square').$body, 'square-secret', true));

        return $this->call('POST', route('webhooks.provider', 'square'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SQUARE_HMACSHA256_SIGNATURE' => $signature,
        ], $body);
    }

    private function records(bool $withAccount = false): array
    {
        $admin = User::factory()->create(['status' => 'active']);
        $client = Client::create(['client_type' => 'individual', 'first_name' => 'Paying', 'last_name' => 'Client', 'email' => 'payer@example.com', 'country_code' => 'US', 'created_by_user_id' => $admin->id, 'updated_by_user_id' => $admin->id]);
        $plan = PaymentPlan::create(['plan_number' => 'LP-FEE', 'title' => 'Payment plan', 'original_purchase_balance' => 1, 'customary_monthly_payment' => 10_000, 'monthly_due_day' => 1, 'first_due_date' => '2026-08-06', 'plan_start_date' => '2026-08-01', 'status' => 'draft', 'created_by_user_id' => $admin->id, 'updated_by_user_id' => $admin->id]);
        app(ContractOpeningService::class)->open($plan, $admin, 100_000, 0, 0, '2026-08-01');
        $plan->update(['status' => 'active', 'activated_at' => now()]);
        PaymentPlanClient::create(['payment_plan_id' => $plan->id, 'client_id' => $client->id, 'role' => 'primary', 'responsibility' => 'joint', 'receives_invoices' => true, 'effective_from' => '2026-08-01', 'contact_risk_acknowledged_at' => now(), 'contact_risk_acknowledgment_method' => 'admin_contract_acceptance', 'created_by_user_id' => $admin->id]);

        if (! $withAccount) return [$admin, $client, $plan];

        $account = PortalAccount::create(['client_id' => $client->id, 'email' => $client->email, 'password' => 'password', 'enabled' => true]);
        return [$admin, $client, $plan, $account];
    }
}
