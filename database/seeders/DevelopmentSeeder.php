<?php

namespace Database\Seeders;

use App\Enums\PaymentMethod;
use App\Models\AppSetting;
use App\Models\Client;
use App\Models\PaymentPlan;
use App\Models\PaymentPlanBillingTerm;
use App\Models\PortalAccount;
use App\Models\User;
use App\Services\ContractOpeningService;
use App\Services\ManualInvoiceService;
use App\Services\PaymentPlanMembershipService;
use App\Services\PaymentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment() !== 'local' || DB::getDatabaseName() !== 'landpay') {
            throw new RuntimeException('DevelopmentSeeder may run only against the local landpay database.');
        }
        if (Client::query()->exists() || PaymentPlan::query()->exists()) {
            throw new RuntimeException('DevelopmentSeeder requires an empty development database apart from an existing administrator.');
        }

        $admin = User::query()
            ->where('role', 'administrator')
            ->where('status', 'active')
            ->first();

        if (! $admin && User::query()->exists()) {
            throw new RuntimeException('Existing users were found, but none is an active administrator.');
        }

        $admin ??= User::query()->create([
            'uuid' => '3b89c3b1-2fe3-48f4-a05c-6a38ce598ace',
            'name' => 'admin',
            'email' => 'chris@mohavedeals.com',
            'email_verified_at' => '2026-07-27 06:21:33',
            'password' => '$2y$12$bStcon2AJ6VfgKQ/TcErJOJ/urK7z4T3v9HSh0anziU1hxrcH6R0a',
            'role' => 'administrator',
            'status' => 'active',
        ]);

        AppSetting::putMany([
            'company_name' => 'LandPay Development',
            'company_email' => 'chris@mohavedeals.com',
            'invoice_view_admin_notice_enabled' => '1',
        ]);

        $client = Client::query()->create([
            'client_type' => 'individual',
            'first_name' => '3tester',
            'last_name' => '3test',
            'preferred_name' => '3tester',
            'email' => '3tester@example.com',
            'primary_phone' => '928-555-0103',
            'address_line_1' => '123 Test Street',
            'city' => 'Kingman',
            'state_region' => 'AZ',
            'postal_code' => '86401',
            'country_code' => 'US',
            'status' => 'active',
            'created_by_user_id' => $admin->id,
            'updated_by_user_id' => $admin->id,
        ]);

        PortalAccount::query()->create([
            'client_id' => $client->id,
            'email' => $client->email,
            'password' => Hash::make('password'),
            'enabled' => true,
        ]);

        $plan = PaymentPlan::query()->create([
            'plan_number' => '3tester-234',
            'apn' => 'TEST-003-234',
            'title' => '3tester development parcel',
            'asset_description' => 'Development payment-plan fixture',
            'purchase_price' => 549900,
            'documentation_fee_standard' => 44900,
            'documentation_fee_waived' => 0,
            'original_purchase_balance' => 594800,
            'customary_monthly_payment' => 15000,
            'monthly_service_fee' => 1500,
            'monthly_due_day' => 3,
            'first_due_date' => '2026-08-03',
            'plan_start_date' => '2026-08-01',
            'grace_period_days' => 5,
            'status' => 'draft',
            'automated_reminders_enabled' => true,
            'scheduled_invoice_email_enabled' => false,
            'automatic_invoice_email_enabled' => false,
            'created_by_user_id' => $admin->id,
            'updated_by_user_id' => $admin->id,
        ]);

        app(PaymentPlanMembershipService::class)->add(
            $plan, $client, $admin, 'primary', '2026-08-01',
            contactRiskAcknowledgmentMethod: 'admin_contract_acceptance',
        );
        app(ContractOpeningService::class)->open($plan, $admin, 549900, 44900, 0, '2026-08-01');
        $plan->update(['status' => 'active', 'activated_at' => '2026-08-01 09:00:00']);

        PaymentPlanBillingTerm::query()->create([
            'payment_plan_id' => $plan->id,
            'frequency' => 'monthly',
            'invoice_day' => 3,
            'due_days_after_issue' => 5,
            'grace_days' => 5,
            'scheduled_payment_amount' => 15000,
            'monthly_service_fee' => 1500,
            'stage_one_enabled' => true,
            'stage_one_fee_type' => 'fixed',
            'stage_one_fixed_amount' => 2500,
            'stage_one_minimum_amount' => 0,
            'stage_one_days_late' => 4,
            'stage_two_enabled' => false,
            'default_eligibility_days' => 60,
            'effective_from' => '2026-08-01',
            'created_by_user_id' => $admin->id,
        ]);

        $invoice = app(ManualInvoiceService::class)->issue($plan, $admin, '2026-08-03', [
            ['type' => 'fee', 'description' => 'Fee', 'amount' => 581],
            ['type' => 'fee', 'description' => 'Monthly service fee', 'amount' => 1500],
            ['type' => 'fee', 'description' => 'Late Fee added 8/12/26', 'amount' => 2500],
            ['type' => 'principal', 'description' => 'Plan Payment', 'amount' => 15000],
        ]);
        $invoice->update(['invoice_number' => 'M12-260803-56', 'due_date' => '2026-08-08']);

        $paid = app(PaymentService::class)->post(
            $plan, $admin, 19581, 'regular', PaymentMethod::Other, '2026-08-30',
            $client->id, 'development-full-invoice-payment', idempotencyKey: 'development:invoice-payment',
            invoiceId: $invoice->id,
        );

        app(PaymentService::class)->post(
            $plan, $admin, 11500, 'regular', PaymentMethod::Other, '2026-08-15',
            $client->id, 'development-account-credit', overpaymentDisposition: \App\Enums\OverpaymentDisposition::NextInvoiceCredit,
            idempotencyKey: 'development:account-credit',
        );

        $openInvoice = app(ManualInvoiceService::class)->issue($plan, $admin, '2026-08-20', [
            ['type' => 'principal', 'description' => 'Plan Payment', 'amount' => 15000],
            ['type' => 'fee', 'description' => 'Monthly service fee', 'amount' => 1500],
        ]);
        $openInvoice->update(['invoice_number' => 'M12-260820-AR', 'due_date' => '2026-08-25']);

        $reversed = app(PaymentService::class)->post(
            $plan, $admin, 40000, 'regular', PaymentMethod::Other, '2026-08-15',
            $client->id, 'development-reversed-payment', idempotencyKey: 'development:reversed-payment',
        );
        app(PaymentService::class)->reverse($reversed, $admin, 'Development reversal example');

        $invoice->update(['first_viewed_at' => '2026-08-30 10:15:00']);

        $this->createClientFixture($admin, [
            'first_name' => 'Alice',
            'last_name' => 'Current',
            'email' => 'alice.current@example.com',
            'plan_number' => 'TEST-CURRENT-101',
            'apn' => 'TEST-101',
            'invoice_count' => 3,
            'paid_invoices' => 2,
        ]);
        $this->createClientFixture($admin, [
            'first_name' => 'Bob',
            'last_name' => 'Overdue',
            'email' => 'bob.overdue@example.com',
            'plan_number' => 'TEST-OVERDUE-202',
            'apn' => 'TEST-202',
            'invoice_count' => 4,
            'paid_invoices' => 3,
            'partial_last_payment' => 5000,
            'reversed_payment' => 2500,
        ]);
        $this->createClientFixture($admin, [
            'first_name' => 'Carla',
            'last_name' => 'Portal',
            'email' => 'carla.portal@example.com',
            'plan_number' => 'TEST-PORTAL-303',
            'apn' => 'TEST-303',
            'invoice_count' => 2,
            'paid_invoices' => 1,
        ]);
        $this->command?->info('Development data created.');
        $this->command?->line('Admin preserved; four clients and plans created.');
        $this->command?->line('Client portal passwords: password');
    }

    private function createClientFixture(User $admin, array $fixture): void
    {
        $client = Client::query()->create([
            'client_type' => 'individual',
            'first_name' => $fixture['first_name'],
            'last_name' => $fixture['last_name'],
            'preferred_name' => $fixture['first_name'],
            'email' => $fixture['email'],
            'primary_phone' => '928-555-01'.str_pad((string) Client::query()->count(), 2, '0', STR_PAD_LEFT),
            'address_line_1' => (100 + Client::query()->count()).' Test Avenue',
            'city' => 'Kingman',
            'state_region' => 'AZ',
            'postal_code' => '86401',
            'country_code' => 'US',
            'status' => 'active',
            'created_by_user_id' => $admin->id,
            'updated_by_user_id' => $admin->id,
        ]);

        PortalAccount::query()->create([
            'client_id' => $client->id,
            'email' => $client->email,
            'password' => Hash::make('password'),
            'enabled' => true,
        ]);

        $plan = PaymentPlan::query()->create([
            'plan_number' => $fixture['plan_number'],
            'apn' => $fixture['apn'],
            'title' => $fixture['first_name'].' development parcel',
            'asset_description' => 'Development payment-plan fixture',
            'purchase_price' => 300000,
            'documentation_fee_standard' => 30000,
            'documentation_fee_waived' => 0,
            'original_purchase_balance' => 330000,
            'customary_monthly_payment' => 15000,
            'monthly_service_fee' => 1500,
            'monthly_due_day' => 3,
            'first_due_date' => '2026-05-03',
            'plan_start_date' => '2026-05-01',
            'grace_period_days' => 5,
            'status' => 'draft',
            'automated_reminders_enabled' => true,
            'scheduled_invoice_email_enabled' => false,
            'automatic_invoice_email_enabled' => false,
            'created_by_user_id' => $admin->id,
            'updated_by_user_id' => $admin->id,
        ]);

        app(PaymentPlanMembershipService::class)->add(
            $plan, $client, $admin, 'primary', '2026-05-01',
            contactRiskAcknowledgmentMethod: 'admin_contract_acceptance',
        );
        app(ContractOpeningService::class)->open($plan, $admin, 300000, 30000, 0, '2026-05-01');
        $plan->update(['status' => 'active', 'activated_at' => '2026-05-01 09:00:00']);

        PaymentPlanBillingTerm::query()->create([
            'payment_plan_id' => $plan->id,
            'frequency' => 'monthly',
            'invoice_day' => 3,
            'due_days_after_issue' => 5,
            'grace_days' => 5,
            'scheduled_payment_amount' => 15000,
            'monthly_service_fee' => 1500,
            'stage_one_enabled' => true,
            'stage_one_fee_type' => 'fixed',
            'stage_one_fixed_amount' => 2500,
            'stage_one_minimum_amount' => 0,
            'stage_one_days_late' => 4,
            'stage_two_enabled' => false,
            'default_eligibility_days' => 60,
            'effective_from' => '2026-05-01',
            'created_by_user_id' => $admin->id,
        ]);

        $dates = ['2026-05-03', '2026-06-03', '2026-07-03', '2026-08-03', '2026-08-20'];
        $invoices = [];

        for ($index = 0; $index < $fixture['invoice_count']; $index++) {
            $invoice = app(ManualInvoiceService::class)->issue($plan, $admin, $dates[$index], [
                ['type' => 'principal', 'description' => 'Plan Payment', 'amount' => 15000],
                ['type' => 'fee', 'description' => 'Monthly service fee', 'amount' => 1500],
            ]);
            $invoice->update([
                'invoice_number' => sprintf('DEV-%s-%02d', $fixture['apn'], $index + 1),
                'due_date' => date('Y-m-d', strtotime($dates[$index].' +5 days')),
                'first_viewed_at' => $index === 0 ? $dates[$index].' 12:00:00' : null,
            ]);
            $invoices[] = $invoice;
        }

        for ($index = 0; $index < $fixture['paid_invoices']; $index++) {
            app(PaymentService::class)->post(
                $plan, $admin, 16500, 'regular', PaymentMethod::Other,
                date('Y-m-d', strtotime($dates[$index].' +2 days')),
                $client->id, 'Full invoice payment',
                idempotencyKey: 'development:'.$fixture['plan_number'].':payment:'.$index,
                invoiceId: $invoices[$index]->id,
            );
        }

        if (! empty($fixture['partial_last_payment'])) {
            $last = $invoices[array_key_last($invoices)];
            app(PaymentService::class)->post(
                $plan, $admin, $fixture['partial_last_payment'], 'regular', PaymentMethod::Other,
                '2026-08-15', $client->id, 'Partial invoice payment',
                idempotencyKey: 'development:'.$fixture['plan_number'].':partial',
                invoiceId: $last->id,
            );
        }

        if (! empty($fixture['reversed_payment'])) {
            $payment = app(PaymentService::class)->post(
                $plan, $admin, $fixture['reversed_payment'], 'regular', PaymentMethod::Other,
                '2026-08-16', $client->id, 'Reversal test payment',
                idempotencyKey: 'development:'.$fixture['plan_number'].':reversed',
            );
            app(PaymentService::class)->reverse($payment, $admin, 'Development reversal example');
        }
    }
}
