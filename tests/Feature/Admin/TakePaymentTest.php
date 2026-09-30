<?php

namespace Tests\Feature\Admin;

use App\Mail\PaymentReceiptMail;
use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\ClientPaymentIntent;
use App\Models\EmailDelivery;
use App\Models\Payment;
use App\Models\PaymentPlan;
use App\Models\PortalAccount;
use App\Models\User;
use App\Services\ContractOpeningService;
use App\Services\FinancialBalanceService;
use App\Services\FirstPaymentInvoiceService;
use App\Services\PaymentPlanMembershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TakePaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_open_checkout_and_selection_reuses_the_card_form(): void
    {
        [$admin, $client, $plan, $invoice] = $this->records();
        $this->get(route('admin.take-payment.create'))->assertRedirect(route('admin.login'));
        $account = PortalAccount::create(['client_id' => $client->id, 'email' => $client->email, 'password' => 'password', 'enabled' => true]);
        $this->actingAs($account, 'client')->get(route('admin.take-payment.create'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.take-payment.store'), [])->assertRedirect(route('admin.login'));
        $this->actingAs($admin, 'web')->get(route('admin.actions.index'))->assertOk()->assertSee('Charge Customer');
        $this->get(route('admin.take-payment.create'))->assertOk()->assertSee('payment-client-search')->assertSee('Paying');
        $this->get(route('admin.take-payment.create', ['client_id' => $client->id, 'plan' => $plan->id, 'invoice' => $invoice->id]))
            ->assertOk()->assertSee('Charge Customer')->assertSee('square-card-container')
            ->assertSee('value="120.00"', false)->assertSee(route('admin.take-payment.store'), false)
            ->assertHeader('Content-Security-Policy');
    }

    public function test_charge_targets_invoice_records_admin_and_does_not_charge_again_on_resubmit(): void
    {
        [$admin, $client, $plan, $invoice] = $this->records();
        $data = $this->checkout($admin, $client, $plan, $invoice);
        $this->post(route('admin.take-payment.store'), $data)->assertSessionHasNoErrors();
        $intent = ClientPaymentIntent::query()->sole();
        $this->assertSame('received', $intent->status);
        $payment = $intent->payment;
        $this->assertSame($admin->id, $payment->financialTransaction->posted_by_user_id);
        $this->assertSame($client->id, $payment->payer_client_id);
        $this->assertSame(10320, $payment->gross_amount);
        $this->assertSame(2000, app(FinancialBalanceService::class)->invoiceBalance($invoice));
        $this->assertSame([$invoice->id], $payment->allocations()->whereNotNull('invoice_id')->distinct()->pluck('invoice_id')->all());
        $this->assertDatabaseHas('audit_logs', ['actor_user_id' => $admin->id, 'event' => 'payment.admin_phone_initiated', 'auditable_id' => $intent->id]);
        $this->get(route('admin.take-payment.show', $intent))->assertOk()->assertSee('Payment successful')->assertSee('payer@example.com')->assertSee('Email receipt');
        $this->post(route('admin.take-payment.store'), $data)->assertRedirect(route('admin.take-payment.show', $intent));
        Http::assertSentCount(1);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('client_payment_intents', 1);
        $this->assertDatabaseCount('email_deliveries', 0);
    }

    public function test_plan_payment_uses_existing_overpayment_allocation(): void
    {
        [$admin, $client, $plan, $invoice] = $this->records();
        $data = $this->checkout($admin, $client, $plan, $invoice);
        $data['invoice_id'] = null;
        $data['amount'] = '150.00';
        $data['overpayment_disposition'] = 'principal';
        $this->post(route('admin.take-payment.store'), $data)->assertSessionHasNoErrors();
        $this->assertSame('received', ClientPaymentIntent::query()->sole()->status);
        $this->assertSame(0, app(FinancialBalanceService::class)->invoiceBalance($invoice));
        $this->assertSame(85000, app(FinancialBalanceService::class)->contractBalance($plan));
        $this->assertSame(3000, Payment::query()->sole()->overpayment_amount);
    }

    public function test_invalid_client_plan_invoice_and_disabled_cards_cannot_be_charged(): void
    {
        [$admin, $client, $plan, $invoice] = $this->records();
        $data = $this->checkout($admin, $client, $plan, $invoice);
        $other = $plan->replicate(['uuid']);
        $other->plan_number = 'OTHER';
        $other->save();
        $otherInvoice = app(FirstPaymentInvoiceService::class)->issue($other, $admin, 1000, today(), today()->addDays(5));
        $this->post(route('admin.take-payment.store'), array_replace($data, ['payment_plan_id' => $other->id]))->assertNotFound();
        $this->post(route('admin.take-payment.store'), array_replace($data, ['invoice_id' => $otherInvoice->id]))->assertNotFound();
        $this->post(route('admin.take-payment.store'), array_replace($data, ['client_id' => 999]))->assertSessionHasErrors('client_id');
        AppSetting::putMany(['payment_card_enabled' => '0']);
        $this->post(route('admin.take-payment.store'), $data)->assertStatus(422);
        Http::assertNothingSent();
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_decline_and_unknown_result_do_not_create_another_charge_on_resubmit(): void
    {
        [$admin, $client, $plan, $invoice] = $this->records();
        $data = $this->checkout($admin, $client, $plan, $invoice);
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['*' => Http::response(['errors' => [['detail' => 'Card declined']]], 400)]);
        $this->post(route('admin.take-payment.store'), $data)->assertSessionHasErrors('method');
        $intent = ClientPaymentIntent::query()->sole();
        $this->assertSame('failed', $intent->status);
        $this->assertDatabaseCount('payments', 0);
        $this->post(route('admin.take-payment.store'), $data)->assertRedirect(route('admin.take-payment.show', $intent));
        Http::assertSentCount(1);

        $data = $this->checkout($admin, $client, $plan, $invoice);
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('Timeout'));
        $this->post(route('admin.take-payment.store'), $data)->assertRedirect();
        $pending = ClientPaymentIntent::query()->latest('id')->firstOrFail();
        $this->assertSame('review_required', $pending->status);
        $this->get(route('admin.take-payment.show', $pending))->assertOk()->assertSee('The card may already have been charged');
        $this->post(route('admin.take-payment.store'), $data)->assertRedirect(route('admin.take-payment.show', $pending));
        $this->assertDatabaseCount('client_payment_intents', 2);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_receipts_allow_alternate_and_additional_recipients_without_changing_client(): void
    {
        Mail::fake();
        [$admin, $client, $plan, $invoice] = $this->records();
        $this->post(route('admin.take-payment.store'), $this->checkout($admin, $client, $plan, $invoice))->assertSessionHasNoErrors();
        $intent = ClientPaymentIntent::query()->sole();
        $url = route('admin.take-payment.receipt', $intent);
        $this->post($url, ['recipients' => 'payer@example.com, helper@example.com, HELPER@example.com'])->assertSessionHas('success');
        Mail::assertSent(PaymentReceiptMail::class, 2);
        $this->assertDatabaseCount('email_deliveries', 2);
        $this->assertDatabaseHas('email_deliveries', ['payment_id' => $intent->payment_id, 'recipient_email' => 'helper@example.com', 'recipient_client_id' => null, 'status' => 'sent']);
        $this->assertSame('payer@example.com', $client->fresh()->email);
        $this->post($url, ['recipients' => 'bad-email'])->assertSessionHasErrors('emails.0');
        $this->assertDatabaseCount('payments', 1);
        Http::assertSentCount(1);
    }

    public function test_receipt_failure_can_be_retried_without_recharging(): void
    {
        [$admin, $client, $plan, $invoice] = $this->records();
        $this->post(route('admin.take-payment.store'), $this->checkout($admin, $client, $plan, $invoice))->assertSessionHasNoErrors();
        $intent = ClientPaymentIntent::query()->sole();
        Mail::shouldReceive('to')->once()->with('helper@example.com')->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new \RuntimeException('Mail unavailable'));
        $this->post(route('admin.take-payment.receipt', $intent), ['recipients' => 'helper@example.com'])->assertSessionHas('warning');
        $this->assertSame('failed', EmailDelivery::query()->sole()->status);
        Mail::swap(new \Illuminate\Mail\MailManager($this->app));
        Mail::fake();
        $this->post(route('admin.take-payment.receipt', $intent), ['recipients' => 'helper@example.com'])->assertSessionHas('success');
        $this->assertSame(1, EmailDelivery::query()->where('status', 'sent')->count());
        $this->assertDatabaseCount('payments', 1);
        Http::assertSentCount(1);
    }

    private function checkout($admin, $client, $plan, $invoice): array
    {
        $view = $this->actingAs($admin, 'web')->get(route('admin.take-payment.create', ['client_id' => $client->id, 'plan' => $plan->id]))->assertOk();
        return ['checkout_key' => $view->viewData('checkoutKey'), 'client_id' => $client->id, 'payment_plan_id' => $plan->id, 'invoice_id' => $invoice->id, 'amount' => '100.00', 'square_source_id' => 'test-token', 'square_card_type' => 'CREDIT'];
    }

    private function records(): array
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-01'));
        User::factory()->create(['status' => 'active']);
        $admin = User::factory()->create(['status' => 'active']);
        AppSetting::putMany(['card_provider' => 'square', 'payment_card_enabled' => '1', 'square_checkout_experience' => 'landpay', 'square_application_id' => 'APP', 'square_environment' => 'sandbox', 'square_public_id' => 'LOCATION', 'square_processing_fee_enabled' => '1', 'square_processing_fee_percent' => '2.9', 'square_processing_fee_amount' => '30']);
        AppSetting::putEncrypted('square_api_secret', 'square-secret');
        Http::preventStrayRequests();
        Http::fake(['connect.squareupsandbox.com/v2/payments' => Http::response(['payment' => ['id' => 'square-admin-payment', 'status' => 'COMPLETED', 'card_details' => ['card' => ['card_type' => 'CREDIT']]]], 200)]);
        $client = Client::create(['client_type' => 'individual', 'first_name' => 'Paying', 'last_name' => 'Client', 'email' => 'payer@example.com', 'country_code' => 'US', 'created_by_user_id' => $admin->id, 'updated_by_user_id' => $admin->id]);
        $plan = PaymentPlan::create(['plan_number' => 'LP-PHONE', 'title' => 'Phone payment plan', 'original_purchase_balance' => 1, 'customary_monthly_payment' => 10000, 'monthly_due_day' => 1, 'first_due_date' => '2026-08-06', 'plan_start_date' => '2026-08-01', 'status' => 'draft', 'created_by_user_id' => $admin->id, 'updated_by_user_id' => $admin->id]);
        app(ContractOpeningService::class)->open($plan, $admin, 100000, 0, 0, '2026-08-01');
        app(PaymentPlanMembershipService::class)->add($plan, $client, $admin, 'primary', '2026-08-01');
        $plan->update(['status' => 'active']);
        $invoice = app(FirstPaymentInvoiceService::class)->issue($plan, $admin, 12000, today(), today()->addDays(5));
        return [$admin, $client, $plan, $invoice];
    }
}
