<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\FinancialActorType;
use App\Enums\FinancialEffectComponent;
use App\Enums\FinancialEffectType;
use App\Enums\FinancialTransactionType;
use App\Financial\PostingEffect;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\FinancialTransaction;
use App\Models\Invoice;
use App\Models\PaymentPlan;
use App\Models\PaymentPlanBillingTerm;
use App\Models\User;
use App\Services\FinancialBalanceService;
use App\Services\FinancialPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ClientAndPaymentPlanManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_routes_require_authentication(): void
    {
        $this->get(route('admin.clients.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.plans.index'))->assertRedirect(route('admin.login'));
        $this->postJson(route('admin.clients.quick-store'), [])->assertRedirect(route('admin.login'));
    }

    public function test_an_administrator_can_create_and_edit_a_client(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('admin.clients.store'), [
            'client_type' => 'individual', 'first_name' => 'Maya', 'last_name' => 'Ortiz',
            'email' => 'MAYA@EXAMPLE.COM', 'country_code' => 'us',
        ]);
        $client = Client::query()->sole();
        $this->assertSame('maya@example.com', $client->email);

        $response = $this->put(route('admin.clients.update', $client), [
            'client_type' => 'individual', 'first_name' => 'Maya', 'middle_name' => 'Elena', 'last_name' => 'Ortiz',
            'email' => 'MAYA.NEW@EXAMPLE.COM', 'primary_phone' => '555-0102', 'address_line_1' => '42 Desert Lane',
            'city' => 'Phoenix', 'state_region' => 'AZ', 'postal_code' => '85001', 'country_code' => 'us',
        ]);
        $response->assertRedirect(route('admin.clients.show', $client));
        $this->assertSame('Elena', $client->fresh()->middle_name);
        $this->assertSame('maya.new@example.com', $client->fresh()->email);

        $this->get(route('admin.clients.index'))
            ->assertOk()
            ->assertSee('Maya Elena Ortiz')
            ->assertSee('APN / Plan #')
            ->assertSee('Contract Balance')
            ->assertSee('Paid-in Value')
            ->assertSee('Last Login')
            ->assertSee('Private Notes')
            ->assertSee('maya.new@example.com');
        $this->get(route('admin.clients.show', $client))
            ->assertOk()
            ->assertSee('Contact information')
            ->assertSee('42 Desert Lane')
            ->assertSee('Phoenix, AZ, 85001');
    }

    public function test_client_list_repeats_a_client_for_each_payment_plan_relationship(): void
    {
        $user = User::factory()->create();
        $client = $this->client($user);
        $client->update(['email' => 'maya@example.com', 'notes' => 'Call before sending documents.']);

        foreach (['111-11-111', '222-22-222'] as $number) {
            $plan = PaymentPlan::query()->create([
                'plan_number' => $number, 'apn' => $number, 'title' => 'Parcel '.$number,
                'purchase_price' => 100_000, 'documentation_fee_standard' => 0,
                'documentation_fee_waived' => 0, 'original_purchase_balance' => 100_000,
                'customary_monthly_payment' => 10_000, 'monthly_service_fee' => 0,
                'monthly_due_day' => 1, 'plan_start_date' => '2026-08-01', 'status' => 'active',
                'activated_at' => now(), 'created_by_user_id' => $user->id, 'updated_by_user_id' => $user->id,
            ]);
            $client->memberships()->create([
                'payment_plan_id' => $plan->id, 'role' => 'primary', 'responsibility' => 'joint',
                'receives_invoices' => true, 'effective_from' => '2026-08-01',
                'contact_risk_acknowledged_at' => now(),
                'contact_risk_acknowledgment_method' => 'admin_contract_acceptance',
                'created_by_user_id' => $user->id,
            ]);
        }

        $response = $this->actingAs($user)->get(route('admin.clients.index'));
        $response->assertOk()
            ->assertSee('111-11-111')
            ->assertSee('222-22-222')
            ->assertSee('Call before sending documents.')
            ->assertSee(route('admin.clients.edit', $client), false);
        $this->assertGreaterThanOrEqual(2, substr_count($response->getContent(), 'Maya Ortiz'));
    }

    public function test_quick_client_creation_returns_a_selectable_client(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson(route('admin.clients.quick-store'), [
            'client_type' => 'individual', 'first_name' => 'Luis', 'last_name' => 'Ortiz',
            'email' => 'LUIS@EXAMPLE.COM', 'primary_phone' => '555-0199', 'country_code' => 'US',
        ])->assertCreated()->assertJsonPath('label', 'Luis Ortiz')->assertJsonPath('email', 'luis@example.com');
    }

    public function test_new_plan_form_renders_the_guided_workflow(): void
    {
        $user = User::factory()->create();
        $this->client($user);

        $this->actingAs($user)->get(route('admin.plans.create'))
            ->assertOk()
            ->assertSee('Opening contract balance')
            ->assertSee('Plan beginning and down/first payment')
            ->assertSee('Recurring monthly invoices')
            ->assertSee('Stage-two late fee');
    }

    public function test_dashboard_shows_plan_rows_with_separate_balances_and_recipient_details(): void
    {
        $user = User::factory()->create();
        $primary = $this->client($user, 'Maya');
        $primary->update(['email' => 'maya@example.com']);
        $primary->portalAccount()->create(['email' => 'maya@example.com', 'password' => 'password', 'enabled' => true]);
        $coClient = $this->client($user, 'Luis');

        $data = $this->validPlanData($primary) + ['co_client_ids' => [$coClient->id]];
        $this->actingAs($user)->post(route('admin.plans.store'), $data)->assertSessionHasNoErrors();
        $plan = PaymentPlan::query()->sole();
        app(FinancialPostingService::class)->post($plan, FinancialTransactionType::Payment, 1_234, '2026-08-15', FinancialActorType::Administrator, [new PostingEffect(FinancialEffectType::ClientCredit, 1_234, FinancialEffectComponent::UnappliedCredit)], actor: $user, description: 'Dashboard credit fixture');

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Maya Ortiz')
            ->assertSee('+1')
            ->assertSee('APN / Plan #')
            ->assertSee('Monthly')
            ->assertSee('$525.00')
            ->assertSee('Contract balance')
            ->assertSee('Current Due')
            ->assertSee('Credit')
            ->assertSee('$12.34')
            ->assertSee('$10,200.00')
            ->assertSee('$0.00')
            ->assertSee('Current')
            ->assertSee('Never')
            ->assertSee('Next reminder')
            ->assertSee('Next invoice')
            ->assertSee('maya@example.com')
            ->assertSee('Open client portal')
            ->assertSee(route('admin.portal-access.store', $primary), false);
    }

    public function test_due_first_payment_is_included_in_current_balance_without_double_counting(): void
    {
        $user = User::factory()->create();
        $client = $this->client($user);
        $data = $this->validPlanData($client) + [
            'first_payment_amount' => '500.00',
            'first_payment_due_date' => '2026-07-15',
        ];
        $data['contract_start_date'] = '2026-07-01';

        $this->actingAs($user)->post(route('admin.plans.store'), $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('invoices', 0);
        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Current Due')
            ->assertSee('Credit')
            ->assertSee('$500.00')
            ->assertSee('Due');
    }

    public function test_plan_number_opens_an_amendment_form_and_updates_preserve_term_history(): void
    {
        $user = User::factory()->create();
        $client = $this->client($user);
        $this->actingAs($user)->post(route('admin.plans.store'), $this->validPlanData($client))->assertSessionHasNoErrors();
        $plan = PaymentPlan::query()->sole();

        $this->get(route('admin.dashboard'))
            ->assertSee(route('admin.plans.show', $plan), false)
            ->assertSee(route('admin.clients.show', $client), false);
        $this->get(route('admin.plans.show', $plan))
            ->assertOk()
            ->assertSee('Edit plan')
            ->assertSee('Maya Ortiz')
            ->assertSee(route('admin.clients.show', $client), false)
            ->assertSee('Total monthly payment')
            ->assertSee('$525.00')
            ->assertSee('Purchase price')
            ->assertSee('$10,000.00')
            ->assertSee('Documentation fee waived')
            ->assertSee(route('admin.plans.edit', $plan), false);
        $this->get(route('admin.plans.edit', $plan))
            ->assertOk()
            ->assertSee('Plan amendment')
            ->assertSee('Maya Ortiz')
            ->assertSee('Existing invoices and ledger entries remain unchanged');

        $response = $this->put(route('admin.plans.update', $plan), [
            'plan_number' => '123-45-999', 'title' => 'Renegotiated north parcel',
            'purchase_price' => '11000.00', 'documentation_fee_standard' => '300.00',
            'documentation_fee_waived' => '100.00', 'documentation_fee_waiver_reason' => 'Updated allowance',
            'contract_start_date' => '2026-08-02',
            'status' => 'active', 'scheduled_payment_amount' => '600.00', 'monthly_service_fee' => '30.00',
            'invoice_day' => 5, 'due_days_after_issue' => 7, 'grace_days' => 3,
            'stage_one_fee_type' => 'fixed', 'stage_one_fee_value' => '20.00',
            'stage_one_minimum_amount' => '0.00', 'default_eligibility_days' => 75,
            'effective_from' => '2026-09-15', 'amendment_reason' => 'Client renegotiated the monthly terms.',
        ]);

        $response->assertRedirect(route('admin.plans.show', $plan));
        $this->assertSame('123-45-999', $plan->fresh()->plan_number);
        $this->assertSame('123-45-999', $plan->fresh()->apn);
        $this->assertSame(60_000, $plan->fresh()->customary_monthly_payment);
        $this->assertSame(1_100_000, $plan->fresh()->purchase_price);
        $this->assertSame(30_000, $plan->fresh()->documentation_fee_standard);
        $this->assertSame(10_000, $plan->fresh()->documentation_fee_waived);
        $this->assertSame(1_120_000, $plan->fresh()->original_purchase_balance);
        $this->assertSame('2026-08-02', $plan->fresh()->plan_start_date->toDateString());
        $this->assertDatabaseHas('financial_transactions', ['payment_plan_id' => $plan->id, 'type' => 'adjustment', 'gross_amount' => 100_000]);
        $this->assertDatabaseCount('payment_plan_billing_terms', 2);
        $this->assertDatabaseHas('payment_plan_billing_terms', ['payment_plan_id' => $plan->id, 'effective_to' => '2026-09-14 00:00:00']);
        $this->assertDatabaseHas('payment_plan_billing_terms', ['payment_plan_id' => $plan->id, 'effective_from' => '2026-09-15 00:00:00', 'scheduled_payment_amount' => 60_000]);
        $this->assertSame('payment_plan.amended', AuditLog::query()->sole()->event);

        $this->get(route('admin.plans.show', $plan))
            ->assertOk()
            ->assertSee('Amendment history')
            ->assertSee('Current')
            ->assertSee('Expired')
            ->assertSee('Client renegotiated the monthly terms.')
            ->assertSee('$500.00')
            ->assertSee('$600.00')
            ->assertSee($user->name);
    }

    public function test_plan_can_start_without_first_payment_details(): void
    {
        $user = User::factory()->create();
        $client = $this->client($user);
        $response = $this->actingAs($user)->post(route('admin.plans.store'), $this->validPlanData($client));

        $plan = PaymentPlan::query()->sole();
        $response->assertRedirect(route('admin.plans.show', $plan));
        $this->assertSame('active', $plan->status);
        $this->assertNull($plan->first_payment_amount);
        $this->assertNull($plan->first_due_date);
        $this->assertSame(1_020_000, $plan->original_purchase_balance);
        $this->assertSame(0, Invoice::query()->count());
        $this->assertSame(1, FinancialTransaction::query()->where('payment_plan_id', $plan->id)->count());
        $this->assertDatabaseHas('payment_plan_clients', [
            'payment_plan_id' => $plan->id,
            'contact_risk_acknowledgment_method' => 'admin_contract_acceptance',
        ]);
        $terms = PaymentPlanBillingTerm::query()->sole();
        $this->assertSame(3, $terms->stage_one_days_late);
        $this->assertSame(0, $terms->stage_one_minimum_amount);
    }

    public function test_admin_can_optionally_create_a_first_payment_invoice_without_increasing_principal(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $client = $this->client($user);
        $data = $this->validPlanData($client) + [
            'first_payment_amount' => '1000.00',
            'first_payment_due_date' => today()->addDays(3)->toDateString(),
            'create_first_payment_invoice' => '1',
        ];
        $this->actingAs($user)->post(route('admin.plans.store'), $data)->assertSessionHasNoErrors();

        $plan = PaymentPlan::query()->sole();
        $invoice = Invoice::query()->with('items')->sole();
        $this->assertSame(100_000, $plan->first_payment_amount);
        $this->assertSame(today()->addDays(3)->toDateString(), $plan->first_due_date->format('Y-m-d'));
        $this->assertSame(100_000, $invoice->items->firstWhere('description', 'Down payment')->amount);
        $this->assertSame(20_000, $invoice->items->firstWhere('description', 'Documentation fee')->amount);
        $contractBalance = app(FinancialBalanceService::class)->contractBalance($plan);
        $this->assertSame(1_020_000, $contractBalance);

        $this->actingAs($user)->delete(route('admin.invoices.destroy', $invoice), [
            'reason' => 'Invoice created in error',
        ])->assertSessionHas('success');
        $this->assertSame('voided', $invoice->fresh()->status->value);
        $this->get(route('admin.invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Create first-payment invoice');

        $this->post(route('admin.invoices.first-payment.store', $invoice))
            ->assertRedirect();
        $newInvoice = Invoice::query()->whereKeyNot($invoice->id)->sole();
        $this->assertStringStartsWith('FP-'.$plan->plan_number.'-', $newInvoice->invoice_number);
        $this->assertStringNotContainsString('-R', $newInvoice->invoice_number);
        $this->assertSame('issued', $newInvoice->status->value);
        $this->assertSame(120_000, app(FinancialBalanceService::class)->invoiceBalance($newInvoice));
        $this->assertSame(0, app(FinancialBalanceService::class)->invoiceBalance($invoice));
        $this->assertSame($contractBalance, app(FinancialBalanceService::class)->contractBalance($plan));
        $this->get(route('admin.invoices.show', $newInvoice))->assertOk()
            ->assertDontSee('Replacement invoice.');
        Mail::assertNothingSent();
    }

    public function test_stage_two_must_follow_the_calculated_stage_one_threshold(): void
    {
        $user = User::factory()->create();
        $client = $this->client($user);
        $data = $this->validPlanData($client) + [
            'stage_two_enabled' => '1', 'stage_two_days_late' => 3,
            'stage_two_fee_type' => 'fixed', 'stage_two_fee_value' => '25.00',
        ];

        $this->actingAs($user)->post(route('admin.plans.store'), $data)
            ->assertSessionHasErrors('stage_two_days_late');
        $this->assertDatabaseCount('payment_plans', 0);
    }

    public function test_validation_failure_preserves_optional_first_payment_and_co_clients(): void
    {
        $user = User::factory()->create();
        $primary = $this->client($user);
        $coClient = $this->client($user, 'Luis');

        $this->actingAs($user)->from(route('admin.plans.create'))->post(route('admin.plans.store'), [
            'primary_client_id' => $primary->id, 'co_client_ids' => [$coClient->id],
            'contract_start_date' => '2026-08-01', 'first_payment_amount' => '500.00',
            'first_payment_due_date' => today()->addDays(3)->toDateString(),
        ])->assertRedirect(route('admin.plans.create'))
            ->assertSessionHasInput('contract_start_date', '2026-08-01')
            ->assertSessionHasInput('first_payment_due_date', today()->addDays(3)->toDateString())
            ->assertSessionHasInput('co_client_ids', [$coClient->id]);
    }

    private function client(User $user, string $firstName = 'Maya'): Client
    {
        return Client::query()->create([
            'client_type' => 'individual', 'first_name' => $firstName, 'last_name' => 'Ortiz', 'country_code' => 'US',
            'created_by_user_id' => $user->id, 'updated_by_user_id' => $user->id,
        ]);
    }

    private function validPlanData(Client $client): array
    {
        return [
            'plan_number' => '123-45-678', 'title' => 'North parcel', 'primary_client_id' => $client->id,
            'purchase_price' => '10000.00', 'documentation_fee_standard' => '250.00', 'documentation_fee_waived' => '50.00',
            'documentation_fee_waiver_reason' => 'Promotional allowance', 'scheduled_payment_amount' => '500.00', 'monthly_service_fee' => '25.00',
            'first_scheduled_invoice_date' => '2026-08-01', 'due_days_after_issue' => 5, 'grace_days' => 2, 'contract_start_date' => '2026-08-01',
            'stage_one_fee_type' => 'fixed', 'stage_one_fee_value' => '15.00', 'stage_one_minimum_amount' => '99.00',
            'default_eligibility_days' => 60, 'contact_risk_acknowledged' => '1',
        ];
    }
}
