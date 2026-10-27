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
            ->assertViewHas('ledgerRows', fn ($rows) => $rows->count() === 1)
            ->assertSee('Account ledger')
            ->assertSee('Applied to contract')
            ->assertSee('Totals / ending balance')
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

        $this->actingAs($user)->get(route('admin.plans.payments.create', $plan))
            ->assertOk()
            ->assertSeeText('Total outstanding')
            ->assertSeeText('$1,200.00')
            ->assertSeeText('Open invoices: $0.00 · Outstanding first payment: $1,200.00');

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

    public function test_preview_identifies_direct_fee_allocation_and_invoice_shortfall(): void
    {
        [$user, $plan] = $this->activePlanWithInvoice();

        $this->actingAs($user)->post(route('admin.plans.payments.preview', $plan), [
            'received_date' => '2026-08-08',
            'amount' => '525.00',
            'service_fee_amount' => '15.00',
            'payment_type' => 'regular',
            'payment_method' => 'check',
            'idempotency_token' => '66666666-6666-4666-8666-666666666666',
        ])->assertOk()
            ->assertSeeText('Not Invoiced, allocated by this payment screen')
            ->assertSeeText('Scheduled purchase payment ($500.00 due)')
            ->assertSee('payment-allocation-shortfall', false)
            ->assertSeeText('Outstanding due after payment')
            ->assertSeeText('$15.00');
    }

    public function test_manual_invoice_service_fee_defaults_direct_allocation_to_zero(): void
    {
        [$user, $plan, $invoice] = $this->activePlanWithInvoice();
        $invoice->update(['period_start' => null, 'period_end' => null]);

        $this->actingAs($user)->get(route('admin.plans.payments.create', [
            $plan, 'received_date' => '2026-08-08',
        ]))->assertOk()
            ->assertViewHas('defaultServiceFee', '0.00')
            ->assertSeeText('Allocate service fee from this payment')
            ->assertSeeText('Deducted before the remainder is applied to invoices.');
    }

    public function test_payment_details_can_be_corrected_without_a_reason_or_financial_changes(): void
    {
        [$user, $plan, $invoice] = $this->activePlanWithInvoice();
        $payment = app(\App\Services\PaymentService::class)->postInvoiceInFull(
            $invoice, $user, \App\Enums\PaymentMethod::Cash, '2026-08-08',
        );
        $allocations = $payment->allocations()->get()->toArray();
        $transaction = $payment->financialTransaction->getAttributes();
        $balance = app(FinancialBalanceService::class)->contractBalance($plan);
        $this->put(route('admin.payments.update', $payment), [])->assertRedirect(route('admin.login'));
        $this->actingAs($user)->get(route('admin.payments.show', $payment))
            ->assertOk()->assertSee('Reason for correction (optional)');
        foreach ([
            ['payment_method' => 'check', 'external_reference' => '1234'],
            ['payment_method' => 'check', 'external_reference' => '5678', 'correction_reason' => ''],
        ] as $data) {
            $this->put(route('admin.payments.update', $payment), $data)
                ->assertRedirect(route('admin.payments.show', $payment))->assertSessionHasNoErrors();
        }
        $payment->refresh();
        $this->assertSame('check', $payment->payment_method->value);
        $this->assertSame('5678', $payment->external_reference);
        $this->assertSame($allocations, $payment->allocations()->get()->toArray());
        $this->assertSame($transaction, $payment->financialTransaction->getAttributes());
        $this->assertSame($balance, app(FinancialBalanceService::class)->contractBalance($plan));
        $this->assertSame(0, app(FinancialBalanceService::class)->invoiceBalance($invoice));
        $logs = \App\Models\AuditLog::query()->where('event', 'payment.details_corrected')->orderBy('id')->get();
        $this->assertCount(2, $logs);
        $this->assertSame('cash', $logs[0]->before_values['payment_method']);
        $this->assertSame('check', $logs[0]->after_values['payment_method']);
        $this->assertNull($logs[0]->after_values['reason']);
        $this->put(route('admin.payments.update', $payment), ['payment_method' => 'invalid'])
            ->assertSessionHasErrors('payment_method');
        app(\App\Services\PaymentService::class)->reverse($payment, $user, 'Test cancellation');
        $this->put(route('admin.payments.update', $payment), ['payment_method' => 'cash'])
            ->assertSessionHasErrors('payment');
        $this->assertSame('check', $payment->fresh()->payment_method->value);
    }

    public function test_paused_plan_can_collect_fee_and_principal_without_paying_tax_invoice(): void
    {
        [$user, $plan, $monthlyInvoice] = $this->activePlanWithInvoice();
        app(\App\Services\InvoiceVoidService::class)->void($monthlyInvoice, $user, 'Manual payments while paused');
        $plan->update(['status' => 'paused']);
        $invoice = app(\App\Services\ManualInvoiceService::class)->issue($plan, $user, '2026-08-08', [
            ['type' => 'other', 'description' => 'Property tax', 'amount' => 1300],
        ]);
        $balances = app(FinancialBalanceService::class);
        $before = $balances->contractBalance($plan);
        $data = [
            'received_date' => '2026-08-08', 'amount' => '120.00', 'service_fee_amount' => '15.00',
            'payment_type' => 'principal_with_service_fee', 'payment_method' => 'cash',
            'idempotency_token' => '77777777-7777-4777-8777-777777777777',
        ];
        $this->actingAs($user)->post(route('admin.plans.payments.preview', $plan), $data)
            ->assertOk()->assertSee('Principal + monthly service fee')
            ->assertViewHas('preview', fn ($preview) =>
                $preview['principal_amount'] === 10500 && $preview['service_fee_amount'] === 1500
                && $preview['invoice_amount'] === 0 && $preview['credit_amount'] === 0);
        $this->assertDatabaseCount('payments', 0);
        $this->post(route('admin.plans.payments.store', $plan), $data)->assertSessionHasNoErrors();
        $payment = Payment::query()->sole();
        $this->assertSame(12000, $payment->gross_amount);
        $this->assertSame([1500, 10500], $payment->allocations()->orderBy('display_order')->pluck('amount')->all());
        $this->assertSame(0, $payment->allocations()->whereNotNull('invoice_id')->count());
        $this->assertSame(1300, $balances->invoiceBalance($invoice));
        $this->assertSame('issued', $invoice->fresh()->status->value);
        $this->assertSame($before - 10500, $balances->contractBalance($plan));
        $this->assertSame('paused', $plan->fresh()->status);
        $this->post(route('admin.plans.payments.store', $plan), $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('payments', 1);
        $this->post(route('admin.payments.reverse', $payment), ['reason' => 'Regression test'])
            ->assertSessionHasNoErrors();
        $this->assertSame($before, $balances->contractBalance($plan));
        $this->assertSame(1300, $balances->invoiceBalance($invoice));
    }

    public function test_principal_only_accepts_zero_or_blank_fee_but_rejects_positive_fee(): void
    {
        [$user, $plan, $invoice] = $this->activePlanWithInvoice();
        $data = [
            'received_date' => '2026-08-08', 'amount' => '120.00',
            'payment_type' => 'principal_only', 'payment_method' => 'cash',
            'idempotency_token' => '88888888-8888-4888-8888-888888888888',
        ];
        foreach (['0.00', ''] as $fee) {
            $this->actingAs($user)->post(route('admin.plans.payments.preview', $plan), $data + ['service_fee_amount' => $fee])
                ->assertOk()->assertViewHas('preview', fn ($preview) => $preview['principal_amount'] === 12000);
        }
        $this->post(route('admin.plans.payments.store', $plan), $data + ['service_fee_amount' => '15.00'])
            ->assertSessionHasErrors('service_fee_amount');
        $this->assertDatabaseCount('payments', 0);
        $this->post(route('admin.plans.payments.store', $plan), $data + ['service_fee_amount' => '0.00'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseCount('payments', 1);
        $this->assertSame(52500, app(FinancialBalanceService::class)->invoiceBalance($invoice));
    }

    public function test_principal_with_fee_limits_the_net_principal_to_the_contract_balance(): void
    {
        [$user, $plan] = $this->activePlanWithInvoice();
        $data = [
            'received_date' => '2026-08-08', 'amount' => '20515.00', 'service_fee_amount' => '15.00',
            'payment_type' => 'principal_with_service_fee', 'payment_method' => 'cash',
            'idempotency_token' => '99999999-9999-4999-8999-999999999999',
        ];
        $this->actingAs($user)->post(route('admin.plans.payments.preview', $plan), $data)->assertOk();
        $data['amount'] = '20515.01';
        $this->post(route('admin.plans.payments.store', $plan), $data)->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('payments', 0);
        $data['amount'] = '120.00';
        $data['service_fee_amount'] = '0.00';
        $this->post(route('admin.plans.payments.preview', $plan), $data)->assertOk()
            ->assertViewHas('preview', fn ($preview) => $preview['principal_amount'] === 12000);
    }

    public function test_payment_then_credit_uses_remaining_line_balances(): void
    {
        [$user, $plan, $invoice] = $this->activePlanWithInvoice();
        $payments = app(\App\Services\PaymentService::class);
        $payment = $payments->post($plan, $user, 2_500, 'regular', \App\Enums\PaymentMethod::Cash, '2026-08-08');
        $this->applyTestCredit($plan, $invoice, $user, 5_000);
        $balances = app(FinancialBalanceService::class);
        $fee = $invoice->items()->where('item_type', 'monthly_service_fee')->firstOrFail();
        $this->assertSame(0, $balances->invoiceItemBalance($fee));
        $this->assertSame(45_000, $balances->invoiceBalance($invoice));
        $this->assertSame(2_045_000, $balances->contractBalance($plan));

        $payments->reverse($payment, $user, 'Correct payment');
        $this->assertSame(2_500, $balances->invoiceItemBalance($fee));
        $payments->post($plan, $user, 2_500, 'regular', \App\Enums\PaymentMethod::Cash, '2026-08-09');
        $this->assertSame(0, $balances->invoiceItemBalance($fee));
        $this->assertSame(45_000, $balances->invoiceBalance($invoice));
        $this->assertSame(2_045_000, $balances->contractBalance($plan));
    }

    public function test_credit_then_payment_uses_remaining_line_balances(): void
    {
        [$user, $plan, $invoice] = $this->activePlanWithInvoice();
        $this->applyTestCredit($plan, $invoice, $user, 2_500);
        app(\App\Services\PaymentService::class)->post($plan, $user, 5_000, 'regular', \App\Enums\PaymentMethod::Cash, '2026-08-08');
        $balances = app(FinancialBalanceService::class);
        $this->assertSame(0, $balances->invoiceItemBalance($invoice->items()->where('item_type', 'monthly_service_fee')->firstOrFail()));
        $this->assertSame(45_000, $balances->invoiceBalance($invoice));
        $this->assertSame(2_045_000, $balances->contractBalance($plan));
    }

    public function test_repeated_partial_credits_do_not_cover_the_same_fee_twice(): void
    {
        [$user, $plan, $invoice] = $this->activePlanWithInvoice();
        $this->applyTestCredit($plan, $invoice, $user, 1_000);
        $this->applyTestCredit($plan, $invoice, $user, 4_000);
        $balances = app(FinancialBalanceService::class);
        $this->assertSame(0, $balances->invoiceItemBalance($invoice->items()->where('item_type', 'monthly_service_fee')->firstOrFail()));
        $this->assertSame(47_500, $balances->invoiceBalance($invoice));
        $this->assertSame(2_047_500, $balances->contractBalance($plan));
    }

    private function applyTestCredit($plan, $invoice, $user, int $amount): void
    {
        app(\App\Services\FinancialPostingService::class)->post(
            $plan, \App\Enums\FinancialTransactionType::Payment, $amount, '2026-08-08',
            \App\Enums\FinancialActorType::Administrator,
            [new \App\Financial\PostingEffect(\App\Enums\FinancialEffectType::ClientCredit, $amount, \App\Enums\FinancialEffectComponent::UnappliedCredit)],
            actor: $user,
        );
        app(\App\Services\AccountCreditApplicationService::class)->applyToInvoice($plan, $invoice, $user);
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
