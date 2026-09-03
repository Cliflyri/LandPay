<?php

namespace Tests\Feature\Admin;

use App\Models\Client;
use App\Models\ContractDocument;
use App\Models\PaymentPlan;
use App\Models\User;
use App\Services\AutomaticInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;

class ContractSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_setup_creates_draft_client_plan_schedule_and_contract_without_money_activity(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create();
        $staleClient = Client::query()->create([
            'client_type' => 'individual',
            'first_name' => 'Previously',
            'last_name' => 'Selected',
            'status' => 'active',
            'created_by_user_id' => $admin->id,
            'updated_by_user_id' => $admin->id,
        ]);
        PaymentPlan::query()->create([
            'plan_number' => '123-45-678',
            'title' => 'Prior sale of parcel',
            'original_purchase_balance' => 1,
            'customary_monthly_payment' => 10000,
            'monthly_due_day' => 3,
            'first_due_date' => '2025-09-03',
            'plan_start_date' => '2025-08-01',
            'status' => 'closed',
            'closed_at' => now(),
            'created_by_user_id' => $admin->id,
            'updated_by_user_id' => $admin->id,
        ]);
        $this->actingAs($admin)->get(route('admin.contract-setups.create'))
            ->assertOk()
            ->assertSee('contract_primary_client_search', false)
            ->assertSee('contract_co_client_search', false)
            ->assertSee('Documentation fee')
            ->assertSee('Financed principal')
            ->assertSee('Estimated contract term')
            ->assertSee('Down/first payment invoice on activation')
            ->assertSee("['Property description',field('property_description').value||'Not provided']", false);

        $response = $this->actingAs($admin)->post(route('admin.contract-setups.store'), [
            'primary_mode' => 'new',
            'primary_client_id' => $staleClient->id,
            'primary_client_type' => 'individual',
            'primary_first_name' => 'Jane',
            'primary_last_name' => 'Buyer',
            'primary_email' => 'jane@example.com',
            'primary_phone' => '555-0100',
            'primary_address_line_1' => '123 Main Street',
            'primary_city' => 'Kingman',
            'primary_state_region' => 'AZ',
            'primary_postal_code' => '86401',
            'co_mode' => 'none',
            'plan_number' => '123-45-678',
            'property_title' => 'Desert Parcel',
            'property_description' => 'Lot 8, Sample Acres',
            'create_first_payment_invoice' => '1',
            'property_county' => 'Mohave',
            'purchase_price' => '2999.00',
            'down_payment' => '300.00',
            'documentation_fee' => '249.00',
            'plan_payment' => '120.00',
            'service_fee' => '15.00',
            'hoa_fee' => '25.00',
            'hoa_term' => 'annually',
            'contract_start_date' => '2026-08-28',
            'first_invoice_date' => '2026-10-03',
            'due_days_after_issue' => 5,            'grace_days' => 2,            'stage_one_fee_type' => 'fixed',            'stage_one_fee_value' => '15.00',            'stage_two_enabled' => '1',            'stage_two_days_late' => 30,            'stage_two_fee_type' => 'fixed',            'stage_two_fee_value' => '50.00',            'default_eligibility_days' => 60,
            'contract_templates' => [$this->template()],
        ]);

        $plan = PaymentPlan::query()->where('status', 'draft')->firstOrFail();
        $response->assertRedirect(route('admin.plans.show', $plan));
        $this->assertSame('Jane', $plan->memberships()->where('role', 'primary')->firstOrFail()->client->first_name);
        $this->actingAs($admin)->get(route('admin.plans.show', $plan))
            ->assertOk()
            ->assertSee('Generated contracts')
            ->assertSee('Oct 3, 2026')
            ->assertSee('Activate plan');
        $this->actingAs($admin)->get(route('admin.plans.edit', $plan))
            ->assertOk()
            ->assertSee('name="first_scheduled_invoice_date"', false)
            ->assertSee('value="2026-10-03"', false)
            ->assertSee('name="create_first_payment_invoice"', false);
        $this->actingAs($admin)->get(route('admin.plans.index'))
            ->assertOk()
            ->assertSee('Active + Draft')
            ->assertSee('123-45-678');
        $this->assertSame('2026-10-03', $plan->first_scheduled_invoice_date->toDateString());
        $this->assertSame('draft', $plan->status);
        $this->assertNull($plan->activated_at);
        $this->assertNull($plan->first_due_date);
        $this->assertSame(30000, $plan->first_payment_amount);
        $this->assertTrue($plan->first_payment_invoice_on_activation);
        $this->assertSame(3, $plan->monthly_due_day);
        $this->assertSame(0, $plan->invoices()->count());
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('payment_allocations', 0);
        $this->assertDatabaseHas('payment_plan_billing_terms', [
            'payment_plan_id' => $plan->id,
            'effective_from' => '2026-10-01',
            'invoice_day' => 3,
            'scheduled_payment_amount' => 12000,
            'monthly_service_fee' => 1500,
            'stage_two_enabled' => true,
            'stage_two_fee_type' => 'fixed',
            'stage_two_fixed_amount' => 5000,
            'stage_two_days_late' => 30,
            'default_eligibility_days' => 60,
        ]);
        $this->assertDatabaseHas('financial_transactions', [
            'payment_plan_id' => $plan->id,
            'type' => 'opening_purchase_balance',
        ]);

        $document = ContractDocument::query()->firstOrFail();
        Storage::disk('local')->assertExists($document->path);
        $xml = $this->documentXml(Storage::disk('local')->path($document->path));
        $this->assertStringContainsString('Jane Buyer', $xml);
        $this->assertStringContainsString('10/03/26', $xml);
        $this->assertStringContainsString('120.00', $xml);

        $this->actingAs($admin)->post(route('admin.contract-setups.activate', $plan))->assertSessionHas('success');
        $this->assertSame('active', $plan->fresh()->status);
        $nextDate = app(AutomaticInvoiceService::class)->nextDate($plan->fresh(), Carbon::parse('2026-08-31'));
        $plan->update(['scheduled_invoice_email_enabled' => false]);
        $this->assertSame(0, app(AutomaticInvoiceService::class)->run(Carbon::parse('2026-10-02'))['created']);
        $this->assertSame(1, app(AutomaticInvoiceService::class)->run(Carbon::parse('2026-10-03'))['created']);
        $this->assertDatabaseHas('invoices', ['payment_plan_id' => $plan->id, 'issue_date' => '2026-10-03']);
        $this->assertDatabaseHas('invoices', ['payment_plan_id' => $plan->id, 'invoice_number' => 'FP-123-45-678']);
        $this->assertDatabaseHas('invoices', ['payment_plan_id' => $plan->id, 'invoice_number' => 'FP-123-45-678', 'due_date' => Carbon::today()->addDays(5)->toDateString()]);
        $this->assertSame(Carbon::today()->addDays(5)->toDateString(), $plan->fresh()->first_due_date->toDateString());
        $this->assertDatabaseHas('invoice_items', ['description' => 'Down payment', 'amount' => 30000]);
        $this->assertDatabaseHas('invoice_items', ['description' => 'Documentation fee', 'amount' => 24900]);
        $this->assertDatabaseMissing('admin_notices', ['type' => 'draft_contract_setup', 'dismissed_at' => null]);

        $this->assertSame('2026-10-03', $nextDate?->toDateString());

        $this->travel(31)->days();
        $this->artisan('contracts:purge-expired')->assertSuccessful();
        Storage::disk('local')->assertMissing($document->path);
        $this->assertNotNull($document->fresh()->deleted_at);
    }

    public function test_existing_primary_client_does_not_require_new_client_name_fields(): void
    {
        $admin = User::factory()->create();
        $client = Client::query()->create([
            'client_type' => 'individual',
            'first_name' => 'Existing',
            'last_name' => 'Client',
            'status' => 'active',
            'created_by_user_id' => $admin->id,
            'updated_by_user_id' => $admin->id,
        ]);

        $this->actingAs($admin)->post(route('admin.contract-setups.store'), [
            'primary_mode' => 'existing',
            'primary_client_id' => $client->id,
            'co_mode' => 'none',
        ])->assertSessionDoesntHaveErrors(['primary_first_name', 'primary_last_name']);
    }

    public function test_empty_draft_plan_can_be_deleted_without_deleting_clients(): void
    {
        $admin = User::factory()->create();
        $plan = PaymentPlan::query()->create([
            'plan_number' => 'DELETE-DRAFT-1',
            'title' => 'Draft to delete',
            'original_purchase_balance' => 1,
            'customary_monthly_payment' => 10000,
            'monthly_due_day' => 3,
            'first_due_date' => '2026-09-03',
            'plan_start_date' => '2026-08-31',
            'status' => 'draft',
            'created_by_user_id' => $admin->id,
            'updated_by_user_id' => $admin->id,
        ]);
        $client = Client::query()->create([
            'client_type' => 'individual',
            'first_name' => 'Preserved',
            'last_name' => 'Client',
            'created_by_user_id' => $admin->id,
            'updated_by_user_id' => $admin->id,
        ]);
        $client->memberships()->create([
            'payment_plan_id' => $plan->id, 'role' => 'primary', 'responsibility' => 'joint',
            'receives_invoices' => true, 'effective_from' => '2026-08-31',
            'created_by_user_id' => $admin->id,
        ]);

        $this->actingAs($admin)->delete(route('admin.contract-setups.delete-draft', $plan))
            ->assertRedirect(route('admin.plans.index'))
            ->assertSessionHas('success');

        $this->assertNull(PaymentPlan::query()->find($plan->id));
        $this->assertDatabaseHas('payment_plans', [
            'id' => $plan->id, 'status' => 'deleted', 'plan_number' => 'DELETE-DRAFT-1-DEL-'.$plan->id,
        ]);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $this->assertDatabaseHas('clients', ['id' => $client->id]);
        $this->get(route('admin.clients.show', $client))
            ->assertOk()
            ->assertSee('No payment plans yet.');
    }

    private function template(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'landpay-contract-').'.docx';
        $word = new PhpWord;
        $word->addSection()->addText('$'.'{C1Name} | $'.'{PFirstInvoiceDate} | $'.'{PPlanPayment}');
        IOFactory::createWriter($word, 'Word2007')->save($path);

        return new UploadedFile($path, 'Purchase Agreement.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);
    }

    private function documentXml(string $path): string
    {
        $zip = new \ZipArchive;
        $zip->open($path);
        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();

        return $xml;
    }
}
