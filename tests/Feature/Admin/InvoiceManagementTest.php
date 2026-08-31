<?php

namespace Tests\Feature\Admin;

use App\Mail\InvoiceReminderMail;
use App\Models\AppSetting;
use App\Mail\TemplatedInvoiceMail;
use App\Models\Client;
use App\Models\EmailDelivery;
use App\Models\EmailTemplate;
use App\Models\Invoice;
use App\Models\InvoiceReminder;
use App\Models\PaymentPlan;
use App\Models\PaymentPlanBillingTerm;
use App\Models\User;
use App\Services\ReminderAutomationService;
use Illuminate\Support\Carbon;
use App\Services\ContractOpeningService;
use App\Services\FinancialBalanceService;
use App\Services\PaymentPlanMembershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvoiceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_routes_require_authentication(): void
    {
        [, $plan] = $this->activePlan();

        $this->get(route('admin.plans.invoices.create', $plan))->assertRedirect(route('admin.login'));
        $this->post(route('admin.invoices.first-payment.store', 1))->assertRedirect(route('admin.login'));
        $this->delete(route('admin.invoices.destroy', 1), ['reason' => 'Skipped month'])->assertRedirect(route('admin.login'));
        $this->post(route('admin.invoices.reminders.store', 1))->assertRedirect(route('admin.login'));
    }

    public function test_administrator_previews_and_issues_one_invoice_per_billing_month(): void
    {
        Mail::fake();
        [$user, $plan] = $this->activePlan();
        $data = ['billing_month' => '2026-08', 'monthly_fee_waiver' => '5.00', 'waiver_reason' => 'Courtesy credit'];

        $this->actingAs($user)->post(route('admin.plans.invoices.preview', $plan), $data)
            ->assertOk()
            ->assertSee('INV-'.$plan->id.'-202608')
            ->assertSee('Scheduled payment')
            ->assertSee('$520.00')
            ->assertSee('Courtesy credit');
        $this->assertDatabaseCount('invoices', 0);

        $response = $this->post(route('admin.plans.invoices.store', $plan), $data);
        $invoice = Invoice::query()->sole();
        $response->assertRedirect(route('admin.invoices.show', $invoice));
        $this->assertSame('2026-08-01', $invoice->issue_date->toDateString());
        $this->assertSame('2026-08-06', $invoice->due_date->toDateString());
        $this->assertSame(52_000, app(FinancialBalanceService::class)->invoiceBalance($invoice));
        $this->assertDatabaseHas('invoice_items', ['invoice_id' => $invoice->id, 'item_type' => 'monthly_service_fee', 'standard_amount' => 2_500, 'waived_amount' => 500, 'amount' => 2_000]);
        $this->get(route('admin.invoices.show', $invoice))->assertOk()
            ->assertSee('Courtesy credit')
            ->assertSee('Invoice details')
            ->assertSee('Invoice date')
            ->assertSee('Due date')
            ->assertSee('Invoice summary')
            ->assertSee('Subtotal')
            ->assertSee('$525.00')
            ->assertSee('Waivers / discounts')
            ->assertSee('Invoice amount')
            ->assertSee('$520.00')
            ->assertSee('Paid to date')
            ->assertSee('Balance due')
            ->assertSee('Enter payment')
            ->assertSee(route('admin.plans.payments.create', $plan), false)
            ->assertSee('Email invoice '.$invoice->invoice_number.' to client@example.com?', false)
            ->assertSee('Final confirmation: send this invoice email now?', false);
        $this->get(route('admin.plans.show', $plan))->assertOk()->assertSee($invoice->invoice_number);
        $this->get(route('admin.dashboard'))->assertOk()->assertSee(route('admin.invoices.show', $invoice), false);
        $this->post(route('admin.invoices.reminders.store', $invoice))->assertSessionHas('success');
        $reminder = InvoiceReminder::query()->sole();
        $this->assertSame('sent', $reminder->status);
        $this->assertSame('client@example.com', $reminder->recipient_email);
        $this->assertSame(52_000, $reminder->balance_snapshot);
        Mail::assertSent(InvoiceReminderMail::class, fn ($mail) => $mail->hasTo('client@example.com')
            && $mail->balance === 52_000
            && $mail->magicLinkEmbedded
            && substr_count($mail->render(), 'View and pay invoice') === 1);
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Send reminder')->assertDontSee('Never');
        $this->get(route('admin.settings.index'))->assertOk()
            ->assertSee('Email templates')
            ->assertSee('Invoice email')
            ->assertSee('{{ magic_invoice_link }}');
        $invoiceTemplate = EmailTemplate::query()->where('slug', 'invoice-email')->sole();
        $this->put(route('admin.settings.templates.update', $invoiceTemplate), [
            'subject' => 'Your {{ invoice_number }} balance is {{ amount_due }}',
            'body_html' => '<p>Custom invoice for {{ client_name }}</p>',
            'active' => '1',
        ])->assertSessionHas('success');
        $this->assertSame(52_000, app(FinancialBalanceService::class)->invoiceBalance($invoice));
        $this->get(route('admin.invoices.show', $invoice))
            ->assertOk()
            ->assertSee('value="inline" selected', false);
        $this->post(route('admin.invoices.email.store', $invoice), ['delivery_format' => 'both'])->assertSessionHas('success');
        $this->assertSame(52_000, app(FinancialBalanceService::class)->invoiceBalance($invoice));
        $delivery = EmailDelivery::query()->sole();
        $this->assertSame('sent', $delivery->status);
        $this->assertSame('both', $delivery->delivery_format);
        $this->assertSame('Your INV-'.$plan->id.'-202608 balance is $520.00', $delivery->subject_snapshot);
        Mail::assertSent(TemplatedInvoiceMail::class, function ($mail): bool {
            return $mail->hasTo('client@example.com')
                && $mail->deliveryFormat === 'both'
                && ! $mail->magicLinkEmbedded
                && substr_count($mail->render(), 'View and pay invoice') === 1
                && count($mail->attachments()) === 1;
        });
        $this->put(route('admin.settings.templates.update', $invoiceTemplate), [
            'subject' => 'Bad {{ secret_value }}', 'body_html' => '<p>Body</p>', 'active' => '1',
        ])->assertSessionHasErrors('template');

        $this->post(route('admin.plans.invoices.store', $plan), $data)->assertRedirect(route('admin.invoices.show', $invoice));
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_items', 2);
    }
    public function test_voided_scheduled_invoice_does_not_block_an_independent_invoice_for_the_same_period(): void
    {
        [$user, $plan] = $this->activePlan();
        $data = ['billing_month' => '2026-08', 'monthly_fee_waiver' => '0.00'];
        $baseNumber = 'INV-'.$plan->id.'-202608';

        $this->actingAs($user)->post(route('admin.plans.invoices.store', $plan), $data)->assertRedirect();
        $first = Invoice::query()->where('invoice_number', $baseNumber)->sole();
        $this->delete(route('admin.invoices.destroy', $first), ['reason' => 'Incorrect invoice'])
            ->assertRedirect(route('admin.plans.show', $plan));
        $this->assertSame('voided', $first->fresh()->status->value);

        $this->post(route('admin.plans.invoices.preview', $plan), $data)
            ->assertOk()
            ->assertSee($baseNumber.'-2')
            ->assertDontSee('This billing month has already been issued.');
        $this->post(route('admin.plans.invoices.store', $plan), $data)->assertRedirect();
        $second = Invoice::query()->where('invoice_number', $baseNumber.'-2')->sole();
        $this->assertSame('issued', $second->status->value);

        $this->delete(route('admin.invoices.destroy', $second), ['reason' => 'Still incorrect'])->assertRedirect();
        $this->post(route('admin.plans.invoices.store', $plan), $data)->assertRedirect();
        $third = Invoice::query()->where('invoice_number', $baseNumber.'-3')->sole();

        $this->assertSame('issued', $third->status->value);
        $this->assertDatabaseCount('invoices', 3);
        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $third->id,
            'item_type' => 'scheduled_purchase_payment',
        ]);
    }

    public function test_administrator_creates_a_manual_plan_invoice_without_changing_the_schedule(): void
    {
        [$user, $plan] = $this->activePlan();
        $data = [
            'issue_date' => '2026-08-15',
            'items' => [
                ['type' => 'principal', 'description' => 'Additional plan payment', 'amount' => '100.00'],
                ['type' => 'fee', 'description' => 'Processing fee', 'amount' => '25.00'],
                ['type' => 'other', 'description' => 'Survey copy', 'amount' => '10.00'],
            ],
        ];

        $this->actingAs($user)->get(route('admin.plans.invoices.manual.create', $plan))
            ->assertOk()
            ->assertSee('Plan service fee')
            ->assertSee('$25.00')
            ->assertSee('Only a plan payment reduces principal when paid.');
        $this->post(route('admin.plans.invoices.manual.preview', $plan), $data)
            ->assertOk()
            ->assertSee('Additional plan payment')
            ->assertSee('$135.00');
        $this->assertDatabaseCount('invoices', 0);

        $contractBefore = app(FinancialBalanceService::class)->contractBalance($plan);
        $response = $this->post(route('admin.plans.invoices.manual.store', $plan), $data);
        $invoice = Invoice::query()->sole();
        $response->assertRedirect(route('admin.invoices.show', $invoice));
        $this->assertStringStartsWith('MINV-'.$plan->id.'-', $invoice->invoice_number);
        $this->assertSame('manual', $invoice->generation_source);
        $this->assertNull($invoice->period_start);
        $this->assertNull($invoice->period_end);
        $this->assertSame('2026-08-15', $invoice->issue_date->toDateString());
        $this->assertSame('2026-08-20', $invoice->due_date->toDateString());
        $this->assertSame(13_500, app(FinancialBalanceService::class)->invoiceBalance($invoice));
        $this->assertSame($contractBefore, app(FinancialBalanceService::class)->contractBalance($plan));
        $this->assertDatabaseHas('invoice_items', ['invoice_id' => $invoice->id, 'item_type' => 'scheduled_purchase_payment', 'amount' => 10_000]);
        $this->assertDatabaseHas('invoice_items', ['invoice_id' => $invoice->id, 'item_type' => 'administrative_fee', 'amount' => 2_500]);
        $this->assertDatabaseHas('invoice_items', ['invoice_id' => $invoice->id, 'item_type' => 'other', 'amount' => 1_000]);

        app(\App\Services\AutomaticInvoiceService::class)->run(Carbon::parse('2026-09-01'));
        $this->assertDatabaseHas('invoices', [
            'payment_plan_id' => $plan->id,
            'invoice_number' => 'INV-'.$plan->id.'-202609',
        ]);
    }

    public function test_manual_invoice_automatically_uses_available_account_credit(): void
    {
        [$user, $plan] = $this->activePlan();
        app(\App\Services\FinancialPostingService::class)->post(
            $plan,
            \App\Enums\FinancialTransactionType::Payment,
            7_500,
            '2026-08-14',
            \App\Enums\FinancialActorType::Administrator,
            [new \App\Financial\PostingEffect(\App\Enums\FinancialEffectType::ClientCredit, 7_500, \App\Enums\FinancialEffectComponent::UnappliedCredit)],
            actor: $user,
            description: 'Early payment held for next invoice',
        );

        $contractBeforeInvoice = app(FinancialBalanceService::class)->contractBalance($plan);

        $this->actingAs($user)->post(route('admin.plans.invoices.manual.store', $plan), [
            'issue_date' => '2026-08-15',
            'items' => [
                ['type' => 'principal', 'description' => 'Next plan payment', 'amount' => '35.00'],
            ],
        ])->assertRedirect();

        $invoice = Invoice::query()->sole();
        $this->assertSame(0, app(FinancialBalanceService::class)->invoiceBalance($invoice));
        $this->assertSame(4_000, app(FinancialBalanceService::class)->clientCredit($plan));
        $this->assertSame('paid', $invoice->fresh()->status->value);
        $this->assertDatabaseHas('financial_transactions', [
            'invoice_id' => $invoice->id,
            'type' => \App\Enums\FinancialTransactionType::CreditApplication->value,
            'gross_amount' => 3_500,
        ]);

        $this->get(route('admin.plans.show', $plan))
            ->assertOk()
            ->assertSee('Account credit')
            ->assertSee('$40.00');
        $this->get(route('admin.plans.show', ['plan' => $plan, 'tab' => 'ledger']))
            ->assertOk()
            ->assertSee('Credit change')
            ->assertSee('Early payment held for next invoice')
            ->assertSee('Account credit applied to invoice')
            ->assertSeeText('+$75.00')
            ->assertSeeText('-$35.00')
            ->assertSeeText('+$40.00');

        $this->get(route('admin.invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Delete invoice</h2>', false)
            ->assertSee('account credit applied to this invoice will be restored');

        $this->delete(route('admin.invoices.destroy', $invoice), ['reason' => 'Manual invoice created in error'])
            ->assertRedirect(route('admin.plans.show', $plan))
            ->assertSessionHas('success');

        $this->assertSame('voided', $invoice->fresh()->status->value);
        $this->assertSame(7_500, app(FinancialBalanceService::class)->clientCredit($plan));
        $this->assertSame($contractBeforeInvoice, app(FinancialBalanceService::class)->contractBalance($plan));
        $this->assertSame(0, app(FinancialBalanceService::class)->invoiceBalance($invoice));
    }
    public function test_dashboard_breaks_current_balance_into_linked_invoice_due_dates(): void
    {
        [$user, $plan] = $this->activePlan();
        foreach (['2026-08', '2026-09', '2026-10', '2026-11'] as $month) {
            $this->actingAs($user)->post(route('admin.plans.invoices.store', $plan), [
                'billing_month' => $month,
                'monthly_fee_waiver' => '0.00',
            ])->assertRedirect();
        }

        $invoices = Invoice::query()->orderBy('due_date')->get();
        $response = $this->get(route('admin.dashboard'));
        $response->assertOk()
            ->assertSee('Current balance')
            ->assertSee('$2,100.00')
            ->assertSee('$525.00 due 8/6')
            ->assertSee('$525.00 due 9/6')
            ->assertSee('$525.00 due 10/6')
            ->assertSee('$525.00 due 11/6')
            ->assertSee('+ 1 more invoice')
            ->assertSee('data-current-balance-hidden="balance-items-'.$plan->id.'"', false);
        foreach ($invoices as $invoice) {
            $response->assertSee(route('admin.invoices.show', $invoice), false);
        }
    }
    public function test_administrator_can_save_encrypted_smtp_settings_and_send_a_test(): void
    {
        Mail::fake();
        [$user] = $this->activePlan();

        $this->actingAs($user)->put(route('admin.settings.smtp.update'), [
            'smtp_enabled' => '1',
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => '587',
            'smtp_security' => 'tls',
            'smtp_username' => 'mailer@example.com',
            'smtp_password' => 'correct-horse-battery-staple',
            'smtp_from_address' => 'billing@example.com',
            'smtp_from_name' => 'LandPay Billing',
            'smtp_ehlo_domain' => 'landpay.example.com',
        ])->assertSessionHas('success');

        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.example.com', config('mail.mailers.smtp.host'));
        $this->assertSame('billing@example.com', config('mail.from.address'));
        $stored = AppSetting::query()->where('key', 'smtp_password')->value('value');
        $this->assertNotSame('correct-horse-battery-staple', $stored);
        $this->assertSame('correct-horse-battery-staple', AppSetting::encryptedValueFor('smtp_password'));
        $this->get(route('admin.settings.index'))->assertOk()
            ->assertSee('smtp.example.com')
            ->assertSee('Saved — leave blank to keep')
            ->assertDontSee('correct-horse-battery-staple');
        $this->post(route('admin.settings.smtp.test'), ['test_email' => 'owner@example.com'])
            ->assertSessionHas('success');
    }

    public function test_scheduled_invoice_email_follows_the_automated_billing_toggle(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-09-01 06:00:00');
        try {
            [, $plan] = $this->activePlan();
            EmailTemplate::query()->where('slug', 'invoice-email')->update(['active' => false]);
            $plan->update([
                'scheduled_invoice_email_enabled' => true,
                'automated_reminders_enabled' => false,
                'automatic_invoice_email_enabled' => false,
            ]);

            $result = app(\App\Services\AutomaticInvoiceService::class)->run(Carbon::today());

            $this->assertSame(1, $result['emailed']);
            Mail::assertSent(TemplatedInvoiceMail::class, fn ($mail) => $mail->hasTo('client@example.com'));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_manually_created_invoice_email_follows_the_manual_invoice_toggle(): void
    {
        Mail::fake();
        [$user, $plan] = $this->activePlan();
        $plan->update([
            'automated_reminders_enabled' => false,
            'automatic_invoice_email_enabled' => true,
        ]);

        $this->actingAs($user)->post(route('admin.plans.invoices.manual.store', $plan), [
            'issue_date' => '2026-08-15',
            'items' => [
                ['type' => 'principal', 'description' => 'Manual plan payment', 'amount' => '100.00'],
            ],
        ])->assertSessionHas('success', 'Invoice issued successfully. Invoice emailed to client@example.com.');

        Mail::assertSent(TemplatedInvoiceMail::class, function ($mail): bool {
            return $mail->hasTo('client@example.com')
                && $mail->deliveryFormat === 'inline';
        });
    }

    public function test_automated_reminders_follow_rules_and_do_not_duplicate(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-08-03 08:00:00');
        try {
            [$user, $plan] = $this->activePlan();
            $this->actingAs($user)->post(route('admin.plans.invoices.store', $plan), [
                'billing_month' => '2026-08',
                'monthly_fee_waiver' => '0.00',
            ])->assertRedirect();
            $invoice = Invoice::query()->sole();
            AppSetting::putMany([
                'reminders_automated_enabled' => '1',
                'reminders_before_days' => '3',
                'reminders_on_due' => '1',
                'reminders_after_interval' => '7',
                'reminders_after_max' => '3',
            ]);

            $first = app(ReminderAutomationService::class)->run(Carbon::today());
            $this->assertSame(1, $first['sent']);
            $reminder = InvoiceReminder::query()->sole();
            $this->assertTrue($reminder->automated);
            $this->assertNull($reminder->sent_by_user_id);
            $this->assertSame('before_due', $reminder->trigger_type);
            $this->assertSame('2026-08-03', $reminder->trigger_date->toDateString());
            $this->assertSame(0, app(ReminderAutomationService::class)->run(Carbon::today())['sent']);
            $this->assertDatabaseCount('invoice_reminders', 1);
            Mail::assertSent(InvoiceReminderMail::class, 1);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_pause_suspends_invoices_and_reminders_and_skips_paused_months(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-09-01 07:00:00');
        try {
            [$user, $plan] = $this->activePlan();
            $plan->update(['automated_reminders_enabled' => true]);
            $this->artisan('invoices:generate')->assertSuccessful();
            $this->assertDatabaseHas('invoices', ['payment_plan_id' => $plan->id, 'invoice_number' => 'INV-'.$plan->id.'-202609', 'generation_source' => 'system']);

            Carbon::setTestNow('2026-09-02 09:00:00');
            $this->actingAs($user)->post(route('admin.plans.pause', $plan), ['pause_date' => '2026-09-02', 'reason' => 'Client requested a temporary pause'])->assertSessionHas('success');
            $this->assertSame('paused', $plan->fresh()->status);
            AppSetting::putMany(['reminders_automated_enabled' => '1', 'reminders_before_days' => '3', 'reminders_on_due' => '1', 'reminders_after_interval' => '7', 'reminders_after_max' => '3']);
            Carbon::setTestNow('2026-09-03 08:00:00');
            $this->assertSame(0, app(ReminderAutomationService::class)->run(Carbon::today())['sent']);

            Carbon::setTestNow('2026-10-01 07:00:00');
            $this->artisan('invoices:generate')->assertSuccessful();
            $this->assertDatabaseMissing('invoices', ['invoice_number' => 'INV-'.$plan->id.'-202610']);

            Carbon::setTestNow('2026-11-01 06:30:00');
            $this->actingAs($user)->post(route('admin.plans.resume', $plan), ['resume_date' => '2026-11-01'])->assertSessionHas('success');
            $this->artisan('invoices:generate')->assertSuccessful();
            $this->assertDatabaseHas('invoices', ['invoice_number' => 'INV-'.$plan->id.'-202611']);
            $this->assertDatabaseMissing('invoices', ['invoice_number' => 'INV-'.$plan->id.'-202610']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_administrator_can_delete_an_unpaid_invoice_without_changing_contract_balance(): void
    {
        [$user, $plan] = $this->activePlan();
        $this->actingAs($user)->post(route('admin.plans.invoices.store', $plan), [
            'billing_month' => '2026-08',
            'monthly_fee_waiver' => '0.00',
        ])->assertRedirect();
        $invoice = Invoice::query()->sole();
        $balances = app(FinancialBalanceService::class);
        $contractBefore = $balances->contractBalance($plan);
        $this->assertSame(52_500, $balances->invoiceBalance($invoice));

        $this->delete(route('admin.invoices.destroy', $invoice), [
            'reason' => 'Customer approved to skip August',
        ])->assertRedirect(route('admin.plans.show', $plan))->assertSessionHas('success');

        $this->assertSame($contractBefore, $balances->contractBalance($plan));
        $this->assertSame(0, $balances->invoiceBalance($invoice));
        $this->assertSame('voided', $invoice->fresh()->status->value);
        $this->assertNotNull($invoice->fresh()->operationally_closed_at);
        $this->assertDatabaseHas('financial_transactions', [
            'invoice_id' => $invoice->id,
            'type' => 'adjustment',
            'reason' => 'Customer approved to skip August',
        ]);
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('<strong>0</strong> open invoices', false);
        $this->get(route('admin.invoices.show', $invoice))->assertOk()
            ->assertSee('Deleted invoice.')
            ->assertSee('Adjustments')
            ->assertSee('&minus; $525.00', false)
            ->assertSee('Balance due')
            ->assertDontSee('Delete invoice</h2>', false);
    }
    private function activePlan(): array
    {
        $user = User::factory()->create();
        $plan = PaymentPlan::query()->create([
            'plan_number' => 'LP-INVOICE', 'title' => 'Invoice parcel', 'purchase_price' => 2_000_000,
            'documentation_fee_standard' => 50_000, 'documentation_fee_waived' => 0,
            'original_purchase_balance' => 1, 'customary_monthly_payment' => 50_000, 'monthly_service_fee' => 2_500,
            'monthly_due_day' => 1, 'first_due_date' => '2026-08-06', 'plan_start_date' => '2026-08-01',
            'status' => 'draft', 'created_by_user_id' => $user->id, 'updated_by_user_id' => $user->id,
        ]);
        $client = Client::query()->create([
            'client_type' => 'individual', 'first_name' => 'Maya', 'last_name' => 'Ortiz', 'email' => 'client@example.com',
            'country_code' => 'US', 'created_by_user_id' => $user->id, 'updated_by_user_id' => $user->id,
        ]);
        app(PaymentPlanMembershipService::class)->add($plan, $client, $user, 'primary', '2026-08-01', contactRiskAcknowledgmentMethod: 'admin_contract_acceptance');
        app(ContractOpeningService::class)->open($plan, $user, 2_000_000, 50_000, 0, '2026-08-01');
        $plan->update(['status' => 'active', 'activated_at' => now()]);
        PaymentPlanBillingTerm::query()->create([
            'payment_plan_id' => $plan->id, 'frequency' => 'monthly', 'invoice_day' => 1, 'due_days_after_issue' => 5,
            'grace_days' => 2, 'scheduled_payment_amount' => 50_000, 'monthly_service_fee' => 2_500,
            'stage_one_enabled' => true, 'stage_one_fee_type' => 'fixed', 'stage_one_fixed_amount' => 1_500,
            'stage_one_minimum_amount' => 0, 'stage_one_days_late' => 3, 'stage_two_enabled' => false,
            'default_eligibility_days' => 60, 'effective_from' => '2026-08-01', 'created_by_user_id' => $user->id,
        ]);

        return [$user, $plan->fresh()];
    }
}
