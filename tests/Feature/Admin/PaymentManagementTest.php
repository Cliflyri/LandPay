<?php

namespace Tests\Feature\Admin;

use App\Mail\PaymentReceiptMail;
use App\Models\Client;
use App\Models\EmailDelivery;
use App\Models\FinancialTransaction;
use App\Models\Payment;
use App\Models\PaymentPlan;
use App\Models\PaymentPlanBillingTerm;
use App\Models\User;
use App\Services\ContractOpeningService;
use App\Services\FinancialBalanceService;
use App\Services\MonthlyInvoiceService;
use App\Services\PaymentPlanMembershipService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PaymentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_form_defaults_to_the_current_arizona_date(): void
    {
        $this->assertSame('America/Phoenix', config('app.timezone'));
        $this->travelTo(
            CarbonImmutable::parse('2026-08-04 01:00:00', 'UTC')->setTimezone(config('app.timezone')),
        );
        [$user, $plan] = $this->activePlanWithInvoice();

        $this->actingAs($user)
            ->get(route('admin.plans.payments.create', $plan))
            ->assertOk()
            ->assertSee('2026-08-03')
            ->assertSeeInOrder(['Receipt Recipient', 'APN / Plan #', 'Test parcel']);
    }

    public function test_regular_payment_is_previewed_and_posted_fee_first(): void
    {
        Mail::fake();
        [$user, $plan, $invoice] = $this->activePlanWithInvoice();

        $this->actingAs($user)->post(route('admin.plans.payments.preview', $plan), [
            'received_date' => '2026-08-08',
            'amount' => '525.00',
            'payment_type' => 'regular',
            'payment_method' => 'check',
            'idempotency_token' => '11111111-1111-4111-8111-111111111111',
        ])->assertOk()->assertSee('Monthly service fee')->assertSee('Scheduled purchase payment');

        $response = $this->actingAs($user)->post(route('admin.plans.payments.store', $plan), [
            'received_date' => '2026-08-08',
            'amount' => '525.00',
            'payment_type' => 'regular',
            'payment_method' => 'check',
            'external_reference' => 'CHK-100',
            'idempotency_token' => '11111111-1111-4111-8111-111111111111',
            'email_receipt' => '1',
        ]);

        $payment = Payment::query()->sole();
        $response->assertRedirect(route('admin.payments.show', $payment));
        $this->assertSame(0, app(FinancialBalanceService::class)->invoiceBalance($invoice));
        $this->assertSame(2_000_000, app(FinancialBalanceService::class)->contractBalance($plan));
        $this->assertSame(['monthly_service_fee', 'scheduled_purchase_payment'], $payment->allocations()->with('invoiceItem')->orderBy('display_order')->get()->pluck('invoiceItem.item_type.value')->all());
        $this->assertSame('paid', $invoice->fresh()->status->value);
        $this->get(route('admin.plans.show', $plan))
            ->assertOk()
            ->assertSee('Purpose')
            ->assertSeeText('Invoice INV-TEST payment')
            ->assertSee(route('admin.invoices.show', $invoice), false);
        $this->get(route('admin.plans.show', ['plan' => $plan, 'tab' => 'ledger']))
            ->assertOk()
            ->assertSee('Account ledger')
            ->assertSee('Applied to principal')
            ->assertSeeText('INV-TEST')
            ->assertSeeText('$525.00')
            ->assertSeeText('$25.00')
            ->assertSeeText('$500.00')
            ->assertSeeText('$20,000.00');
        $this->get(route('admin.payments.show', $payment))->assertOk()->assertSee('CHK-100');
        $this->get(route('admin.invoices.show', $invoice))->assertOk()
            ->assertSee('Invoice summary')
            ->assertSee('Paid to date')
            ->assertSee('&minus; $525.00', false)
            ->assertSee('Balance due')
            ->assertSee('Paid');
        $delivery = EmailDelivery::query()->sole();
        $this->assertSame('payment-receipt', $delivery->template_slug);
        $this->assertSame('sent', $delivery->status);
        $this->assertStringContainsString('$525.00', $delivery->body_snapshot);
        $this->assertStringNotContainsString('remaining contract balance', strtolower($delivery->body_snapshot));
        Mail::assertSent(PaymentReceiptMail::class, fn ($mail) => $mail->hasTo('payer@example.com') && count($mail->attachments()) === 1);
        $this->post(route('admin.payments.receipt-email.store', $payment))->assertSessionHas('success');
        $this->assertDatabaseCount('email_deliveries', 2);
        Mail::assertSent(PaymentReceiptMail::class, 2);
        $this->post(route('admin.plans.payments.store', $plan), ['received_date' => '2026-08-08', 'amount' => '525.00', 'payment_type' => 'regular', 'payment_method' => 'check', 'idempotency_token' => '11111111-1111-4111-8111-111111111111'])->assertRedirect(route('admin.payments.show', $payment));
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_overpayment_defaults_to_principal_and_records_the_decision(): void
    {
        [$user, $plan, $invoice] = $this->activePlanWithInvoice();

        $this->actingAs($user)->post(route('admin.plans.payments.store', $plan), [
            'received_date' => '2026-08-08', 'amount' => '650.00', 'payment_type' => 'regular', 'payment_method' => 'cash',
            'idempotency_token' => '22222222-2222-4222-8222-222222222222',
        ])->assertSessionHasNoErrors();

        $payment = Payment::query()->sole();
        $this->assertSame(12_500, $payment->overpayment_amount);
        $this->assertSame('principal', $payment->overpayment_disposition->value);
        $this->assertSame(1_987_500, app(FinancialBalanceService::class)->contractBalance($plan));
        $this->assertSame(0, app(FinancialBalanceService::class)->invoiceBalance($invoice));
        $this->get(route('admin.plans.show', $plan))
            ->assertOk()
            ->assertSeeText('Invoice INV-TEST payment + additional')
            ->assertSee(route('admin.invoices.show', $invoice), false);

        $this->actingAs($user)->post(route('admin.plans.payments.store', $plan), [
            'received_date' => '2026-08-09', 'amount' => '10.00', 'payment_type' => 'principal_only', 'payment_method' => 'cash',
            'idempotency_token' => '55555555-5555-4555-8555-555555555555',
        ])->assertSessionHasNoErrors();
        $this->get(route('admin.plans.show', $plan))
            ->assertOk()
            ->assertSee('Additional / principal');
    }

    public function test_payment_reversal_restores_balances_and_cannot_be_repeated(): void
    {
        [$user, $plan, $invoice] = $this->activePlanWithInvoice();
        Mail::fake();
        $this->actingAs($user)->post(route('admin.plans.payments.store', $plan), [
            'received_date' => '2026-08-08', 'amount' => '525.00', 'payment_type' => 'regular', 'payment_method' => 'ach',
            'idempotency_token' => '33333333-3333-4333-8333-333333333333',
            'email_receipt' => '1',
        ]);
        $payment = Payment::query()->sole();

        $this->get(route('admin.payments.show', $payment))
            ->assertOk()
            ->assertSee('Cancel payment')
            ->assertDontSee('Delete payment');

        $this->actingAs($user)->post(route('admin.payments.reverse', $payment), ['reason' => 'Duplicate bank entry.'])
            ->assertRedirect(route('admin.payments.show', $payment));

        $this->get(route('admin.payments.show', $payment))
            ->assertOk()
            ->assertSee('Canceled.')
            ->assertDontSee('Cancel payment');
        $this->get(route('admin.plans.show', $plan))
            ->assertOk()
            ->assertSeeText('Invoice INV-TEST payment reversal')
            ->assertSee(route('admin.invoices.show', $invoice), false);

        $this->assertSame(52_500, app(FinancialBalanceService::class)->invoiceBalance($invoice));
        $this->assertSame(2_050_000, app(FinancialBalanceService::class)->contractBalance($plan));
        $this->assertSame('issued', $invoice->fresh()->status->value);
        $this->assertDatabaseHas('financial_transactions', ['type' => 'reversal', 'reversal_of_transaction_id' => $payment->financial_transaction_id, 'reason' => 'Duplicate bank entry.']);

        $this->actingAs($user)->post(route('admin.payments.reverse', $payment), ['reason' => 'Again'])
            ->assertSessionHasErrors('payment');
        $this->assertSame(1, FinancialTransaction::query()->where('type', 'reversal')->count());
        $this->assertDatabaseHas('email_deliveries', ['payment_id' => $payment->id, 'template_slug' => 'payment-reversal', 'status' => 'sent']);
        $this->assertDatabaseCount('email_deliveries', 2);
        Mail::assertSent(PaymentReceiptMail::class, 2);
        $this->post(route('admin.payments.receipt-email.store', $payment))->assertSessionHasErrors('receipt');
    }

    public function test_due_first_payment_without_an_invoice_is_not_treated_as_excess(): void
    {
        $this->travelTo('2026-08-10');
        $user = User::factory()->create();
        $plan = PaymentPlan::query()->create([
            'plan_number' => 'LP-FIRST', 'title' => 'First payment parcel', 'original_purchase_balance' => 1,
            'customary_monthly_payment' => 120_000, 'first_payment_amount' => 120_000,
            'monthly_due_day' => 3, 'first_due_date' => '2026-08-08', 'plan_start_date' => '2026-07-26',
            'status' => 'draft', 'created_by_user_id' => $user->id, 'updated_by_user_id' => $user->id,
        ]);
        app(ContractOpeningService::class)->open($plan, $user, 2_000_000, 0, 0, '2026-07-26');
        $plan->update(['status' => 'active', 'activated_at' => now()]);

        $voided = app(\App\Services\FirstPaymentInvoiceService::class)->issue(
            $plan, $user, 120_000, '2026-07-26', '2026-08-08'
        );
        app(\App\Services\InvoiceVoidService::class)->void($voided, $user, 'Reissue required');
        $this->assertSame('voided', $voided->fresh()->status->value);

        $data = [
            'received_date' => '2026-08-10', 'amount' => '1200.00', 'payment_type' => 'regular',
            'payment_method' => 'check', 'idempotency_token' => '44444444-4444-4444-8444-444444444444',
        ];
        $this->actingAs($user)->post(route('admin.plans.payments.preview', $plan), $data)
            ->assertOk()
            ->assertSee('First payment')
            ->assertDontSee('Client choice pending');
        $this->assertDatabaseCount('invoices', 1);

        $this->post(route('admin.plans.payments.store', $plan), $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('invoices', 2);
        $this->assertDatabaseHas('payments', ['gross_amount' => 120_000, 'overpayment_amount' => 0]);
        $newInvoice = \App\Models\Invoice::query()->where('status', 'paid')->sole();
        $this->assertStringStartsWith('FP-'.$plan->plan_number.'-', $newInvoice->invoice_number);
        $this->assertStringNotContainsString('-R', $newInvoice->invoice_number);
        $this->assertSame(0, app(FinancialBalanceService::class)->invoiceBalance($newInvoice));
        $this->assertSame(1_880_000, app(FinancialBalanceService::class)->contractBalance($plan));
    }

    private function activePlanWithInvoice(): array
    {
        $user = User::factory()->create();
        $plan = PaymentPlan::query()->create([
            'plan_number' => 'LP-'.uniqid(), 'title' => 'Test parcel', 'original_purchase_balance' => 1,
            'customary_monthly_payment' => 50_000, 'monthly_due_day' => 3, 'first_due_date' => '2026-08-08',
            'plan_start_date' => '2026-07-26', 'status' => 'draft', 'created_by_user_id' => $user->id, 'updated_by_user_id' => $user->id,
        ]);
        app(ContractOpeningService::class)->open($plan, $user, 2_000_000, 50_000, 0, '2026-07-26');
        $plan->update(['status' => 'active', 'activated_at' => now()]);
        $client = Client::query()->create([
            'client_type' => 'individual', 'first_name' => 'Receipt', 'last_name' => 'Recipient', 'email' => 'payer@example.com',
            'country_code' => 'US', 'created_by_user_id' => $user->id, 'updated_by_user_id' => $user->id,
        ]);
        app(PaymentPlanMembershipService::class)->add($plan, $client, $user, 'primary', '2026-07-26', contactRiskAcknowledgmentMethod: 'admin_contract_acceptance');
        $terms = PaymentPlanBillingTerm::query()->create([
            'payment_plan_id' => $plan->id, 'frequency' => 'monthly', 'invoice_day' => 3, 'due_days_after_issue' => 5,
            'grace_days' => 0, 'scheduled_payment_amount' => 50_000, 'monthly_service_fee' => 2_500,
            'stage_one_enabled' => true, 'stage_one_fee_type' => 'fixed', 'stage_one_fixed_amount' => 2_500,
            'stage_one_minimum_amount' => 0, 'stage_one_days_late' => 1, 'stage_two_enabled' => false,
            'default_eligibility_days' => 60, 'effective_from' => '2026-07-26', 'created_by_user_id' => $user->id,
        ]);
        $invoice = app(MonthlyInvoiceService::class)->issue($plan, $terms, $user, 'INV-TEST', '2026-08-01', '2026-08-31', '2026-08-03');

        return [$user, $plan->fresh(), $invoice];
    }
}
