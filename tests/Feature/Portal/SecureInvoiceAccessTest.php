<?php

namespace Tests\Feature\Portal;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceAccessLink;
use App\Models\PaymentPlan;
use App\Models\PaymentPlanBillingTerm;
use App\Models\User;
use App\Services\ContractOpeningService;
use App\Services\PaymentPlanMembershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecureInvoiceAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_secure_link_opens_only_the_invoice_and_its_payment_options(): void
    {
        [$admin, $invoice] = $this->invoice();
        $response = $this->actingAs($admin)->post(route('admin.invoices.secure-link.store', $invoice));
        $response->assertSessionHas('secure_link_url');
        $url = session('secure_link_url');
        $this->assertTrue(InvoiceAccessLink::query()->first()->isActive());

        $this->get($url)->assertRedirect(route('secure-invoice.show'))->assertSessionHas('secure_invoice_link_id');
        $this->assertTrue(InvoiceAccessLink::query()->first()->isActive());
        $this->assertNotSame('voided', $invoice->fresh()->status->value);
        $this->get(route('secure-invoice.show'))
            ->assertOk()
            ->assertSee('Secure invoice access')
            ->assertSee($invoice->invoice_number)
            ->assertSee('Pay Now');

        $this->get(route('secure-invoice.payment.create'))
            ->assertOk()
            ->assertSee('Choose a payment method')
            ->assertSee('Secure invoice access')
            ->assertSee(route('secure-invoice.payment.store'), false);

        $this->get(route('portal.dashboard'))->assertRedirect(route('portal.login'));
    }

    public function test_expired_and_revoked_links_require_normal_login(): void
    {
        [$admin, $invoice] = $this->invoice();
        $this->actingAs($admin)->post(route('admin.invoices.secure-link.store', $invoice));
        $url = session('secure_link_url');
        InvoiceAccessLink::query()->where('invoice_id', $invoice->id)->update(['expires_at' => now()->subMinute()]);

        $this->get($url)->assertRedirect(route('portal.login'))->assertSessionHas('status');
        $this->actingAs($admin)->post(route('admin.invoices.secure-link.regenerate', $invoice));
        $newUrl = session('secure_link_url');
        $this->delete(route('admin.invoices.secure-link.destroy', $invoice))->assertSessionHas('success');
        $this->get($newUrl)->assertRedirect(route('portal.login'));
    }

    private function invoice(): array
    {
        $admin = User::factory()->create();
        $client = Client::query()->create([
            'client_type' => 'individual', 'first_name' => 'Mobile', 'last_name' => 'Client',
            'email' => 'mobile@example.com', 'country_code' => 'US',
            'created_by_user_id' => $admin->id, 'updated_by_user_id' => $admin->id,
        ]);
        $plan = PaymentPlan::query()->create([
            'plan_number' => 'LP-SECURE', 'title' => 'Secure invoice plan', 'purchase_price' => 100000,
            'documentation_fee_standard' => 0, 'documentation_fee_waived' => 0,
            'original_purchase_balance' => 1, 'customary_monthly_payment' => 10000, 'monthly_service_fee' => 0,
            'monthly_due_day' => 1, 'first_due_date' => '2026-08-06', 'plan_start_date' => '2026-08-01',
            'status' => 'draft', 'created_by_user_id' => $admin->id, 'updated_by_user_id' => $admin->id,
        ]);
        app(PaymentPlanMembershipService::class)->add($plan, $client, $admin, 'primary', '2026-08-01', contactRiskAcknowledgmentMethod: 'admin_contract_acceptance');
        app(ContractOpeningService::class)->open($plan, $admin, 100000, 0, 0, '2026-08-01');
        $plan->update(['status' => 'active', 'activated_at' => now()]);
        PaymentPlanBillingTerm::query()->create([
            'payment_plan_id' => $plan->id, 'frequency' => 'monthly', 'invoice_day' => 1,
            'due_days_after_issue' => 5, 'grace_days' => 2, 'scheduled_payment_amount' => 10000,
            'monthly_service_fee' => 0, 'stage_one_enabled' => false, 'stage_one_fee_type' => 'fixed',
            'stage_one_fixed_amount' => 0, 'stage_one_minimum_amount' => 0, 'stage_one_days_late' => 3,
            'stage_two_enabled' => false, 'default_eligibility_days' => 60,
            'effective_from' => '2026-08-01', 'created_by_user_id' => $admin->id,
        ]);
        $this->actingAs($admin)->post(route('admin.plans.invoices.store', $plan), [
            'billing_month' => '2026-08', 'monthly_fee_waiver' => '0.00',
        ])->assertRedirect();

        return [$admin, Invoice::query()->sole()];
    }
}
