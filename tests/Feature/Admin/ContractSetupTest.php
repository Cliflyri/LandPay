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
            'effective_from' => '2026-08-28',
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


    public function test_revision_prefills_and_updates_the_same_draft_and_keeps_old_contracts(): void
    {
        [$admin, $plan, $data] = $this->revisionFixture();
        $oldDocument = $plan->contractDocuments()->firstOrFail();
        $data['property_title'] = 'Revised property';
        $data['purchase_price'] = '3500.00';
        $data['co_mode'] = 'new';
        $data['co_client_type'] = 'individual';
        $data['co_first_name'] = 'Second';
        $data['co_last_name'] = 'Buyer';
        $data['co_effective_from'] = '2026-08-28';
        $data['co_receives_invoices'] = '0';
        $this->put(route('admin.contract-setups.update', $plan), $data)->assertSessionHasNoErrors()->assertRedirect(route('admin.plans.show', $plan));
        $this->assertDatabaseCount('payment_plans', 1);
        $this->assertSame('draft', $plan->fresh()->status);
        $this->assertSame('Revised property', $plan->fresh()->title);
        $this->assertSame(350000, $plan->fresh()->purchase_price);
        $this->assertSame(2, $plan->memberships()->count());
        $this->assertSame(1, $plan->billingTerms()->count());
        $this->assertSame(1, $plan->financialTransactions()->where('type', 'opening_purchase_balance')->count());
        $this->assertSame(0, $plan->invoices()->count());
        $this->assertStringStartsWith('Superseded - ', $oldDocument->fresh()->name);
        Storage::disk('local')->assertExists($oldDocument->path);
        $latest = $plan->contractDocuments()->latest('id')->firstOrFail();
        $this->assertStringContainsString('Second Buyer', $this->documentXml(Storage::disk('local')->path($latest->path)));
    }

    public function test_active_plan_co_client_revision_preserves_billing_and_invoice_history(): void
    {
        [$admin, $plan, $data] = $this->revisionFixture();
        $this->post(route('admin.contract-setups.activate', $plan))->assertSessionHasNoErrors();
        $terms = $plan->currentBillingTerms()->firstOrFail()->getAttributes();
        $invoices = $plan->invoices()->get()->toArray();
        $transactions = $plan->financialTransactions()->count();
        $data['co_mode'] = 'new';
        $data['co_client_type'] = 'individual';
        $data['co_first_name'] = 'Active';
        $data['co_last_name'] = 'Buyer';
        $this->put(route('admin.contract-setups.update', $plan), $data)->assertSessionHasNoErrors();
        $this->assertSame('active', $plan->fresh()->status);
        $this->assertSame($terms, $plan->currentBillingTerms()->firstOrFail()->getAttributes());
        $this->assertSame($invoices, $plan->invoices()->get()->toArray());
        $this->assertSame($transactions, $plan->financialTransactions()->count());
    }

    public function test_failed_regeneration_rolls_back_plan_client_and_financial_changes(): void
    {
        [$admin, $plan, $data] = $this->revisionFixture();
        $oldDocument = $plan->contractDocuments()->firstOrFail();
        $transactionCount = $plan->financialTransactions()->count();
        $this->mock(\App\Services\ContractDocumentService::class)->shouldReceive('generate')->once()->andThrow(new \RuntimeException('Invalid template'));
        $data['purchase_price'] = '4500.00';
        $data['co_mode'] = 'new';
        $data['co_client_type'] = 'individual';
        $data['co_first_name'] = 'Rollback';
        $data['co_last_name'] = 'Buyer';
        $this->put(route('admin.contract-setups.update', $plan), $data)->assertSessionHasErrors('contract_templates');
        $this->assertSame(299900, $plan->fresh()->purchase_price);
        $this->assertSame($transactionCount, $plan->financialTransactions()->count());
        $this->assertSame(1, $plan->memberships()->count());
        $this->assertDatabaseMissing('clients', ['first_name' => 'Rollback']);
        $this->assertSame($oldDocument->name, $oldDocument->fresh()->name);
        Storage::disk('local')->assertExists($oldDocument->path);
    }

    public function test_add_co_client_invites_them_and_existing_portal_permissions_apply(): void
    {
        [$admin, $plan] = $this->revisionFixture();
        \Illuminate\Support\Facades\Mail::fake();
        $this->get(route('admin.plans.co-clients.create', $plan))->assertOk()->assertSee('Send portal invitation');
        $this->post(route('admin.plans.co-clients.store', $plan), [
            'co_mode' => 'new', 'co_client_type' => 'individual', 'co_first_name' => 'Portal',
            'co_last_name' => 'Buyer', 'co_email' => 'co@example.com',
            'co_effective_from' => today()->toDateString(), 'co_receives_invoices' => '0', 'invite_co_client' => '1',
        ])->assertSessionHasNoErrors()->assertSessionMissing('warning')->assertRedirect(route('admin.plans.show', $plan));
        $co = Client::query()->where('email', 'co@example.com')->firstOrFail();
        $this->assertDatabaseHas('portal_invitations', ['client_id' => $co->id, 'email' => 'co@example.com']);
        $account = new \App\Models\PortalAccount(['client_id' => $co->id]);
        $this->assertContains($plan->id, $account->activePlanIds());
        $this->assertSame(1, $plan->contractDocuments()->count());
        $this->post(route('admin.plans.co-clients.store', $plan), [
            'co_mode' => 'existing', 'co_client_id' => $co->id, 'co_effective_from' => today()->toDateString(),
        ])->assertSessionHasErrors('client_id');
        $this->assertSame(2, $plan->memberships()->count());
    }

    public function test_revision_cannot_replace_existing_clients_or_restart_active_billing(): void
    {
        [$admin, $plan, $data] = $this->revisionFixture();
        $this->post(route('admin.contract-setups.activate', $plan))->assertSessionHasNoErrors();
        $data['first_invoice_date'] = '2026-11-03';
        $this->put(route('admin.contract-setups.update', $plan), $data)->assertSessionHasErrors('first_invoice_date');
        $data['first_invoice_date'] = '2026-10-03';
        $data['primary_mode'] = 'new';
        $data['primary_client_type'] = 'individual';
        $data['primary_first_name'] = 'Replacement';
        $data['primary_last_name'] = 'Buyer';
        $this->put(route('admin.contract-setups.update', $plan), $data)->assertSessionHasErrors('co_client_id');
        $this->assertDatabaseMissing('clients', ['first_name' => 'Replacement']);
    }


    public function test_active_revision_amends_amounts_and_terms_without_reissuing_invoices(): void
    {
        [$admin, $plan, $data] = $this->revisionFixture();
        $this->post(route('admin.contract-setups.activate', $plan))->assertSessionHasNoErrors();
        $invoiceIds = $plan->invoices()->pluck('id')->all();
        $oldTerms = $plan->currentBillingTerms()->firstOrFail();
        $data['purchase_price'] = '3200.00';
        $data['plan_payment'] = '150.00';
        $this->put(route('admin.contract-setups.update', $plan), $data)->assertSessionHasNoErrors();
        $this->assertSame(320000, $plan->fresh()->purchase_price);
        $this->assertSame(15000, $plan->currentBillingTerms()->firstOrFail()->scheduled_payment_amount);
        $this->assertSame('2026-08-31', $oldTerms->fresh()->effective_to->toDateString());
        $this->assertSame(2, $plan->billingTerms()->count());
        $this->assertSame(1, $plan->financialTransactions()->where('type', 'opening_purchase_balance')->count());
        $this->assertSame(1, $plan->financialTransactions()->where('type', 'adjustment')->count());
        $this->assertSame($invoiceIds, $plan->invoices()->pluck('id')->all());
        $this->assertSame('2026-10-03', $plan->fresh()->first_scheduled_invoice_date->toDateString());
    }


    public function test_co_client_removal_hides_only_their_plan_documents_and_preserves_history_and_links(): void
    {
        [$admin, $plan] = $this->revisionFixture();
        $this->post(route('admin.contract-setups.activate', $plan))->assertSessionHasNoErrors();
        $co = Client::query()->create(['client_type' => 'individual', 'first_name' => 'Removed', 'last_name' => 'Buyer', 'email' => 'removed@example.com', 'created_by_user_id' => $admin->id, 'updated_by_user_id' => $admin->id]);
        $memberships = app(\App\Services\PaymentPlanMembershipService::class);
        $member = $memberships->add($plan, $co, $admin, 'co_client', today());
        $account = \App\Models\PortalAccount::query()->create(['client_id' => $co->id, 'email' => $co->email, 'password' => 'password', 'enabled' => true]);
        $otherPlan = $plan->replicate(['uuid']);
        $otherPlan->plan_number = 'OTHER-PLAN';
        $otherPlan->save();
        $memberships->add($otherPlan, $co, $admin, 'primary', today());
        $primary = $plan->memberships()->where('role', 'primary')->firstOrFail();
        $makeDocument = function ($clientId, $planId, $name) {
            Storage::disk('local')->put($name, 'pdf');
            return \App\Models\SharedDocument::query()->create(['client_id' => $clientId, 'payment_plan_id' => $planId, 'name' => $name, 'category' => 'contract', 'disk' => 'local', 'path' => $name, 'mime' => 'application/pdf', 'size' => 3, 'visible_to_client' => true]);
        };
        $hidden = $makeDocument($co->id, $plan->id, 'removed-plan.pdf');
        $otherDocument = $makeDocument($co->id, $otherPlan->id, 'other-plan.pdf');
        $generalDocument = $makeDocument($co->id, null, 'general.pdf');
        $primaryDocument = $makeDocument($primary->client_id, $plan->id, 'primary.pdf');
        $thread = \App\Models\SecureMessageThread::query()->create(['client_id' => $co->id, 'payment_plan_id' => $plan->id, 'subject' => 'Existing conversation', 'category' => 'general', 'latest_message_at' => now()]);
        $invoice = $plan->invoices()->firstOrFail();
        $links = app(\App\Services\InvoiceAccessLinkService::class);
        $link = $links->activeOrCreate($invoice, $co, $admin);
        $token = $link->token_hash;
        $invoiceIds = $plan->invoices()->pluck('id')->all();
        $transactionIds = $plan->financialTransactions()->pluck('id')->all();
        $this->delete(route('admin.plans.co-clients.destroy', [$plan, $member->id]), ['reason' => 'No longer participating'])->assertSessionHasNoErrors()->assertRedirect(route('admin.plans.show', $plan));
        $this->assertSame(today()->toDateString(), $member->fresh()->effective_to->toDateString());
        $this->assertSame($admin->id, $member->fresh()->ended_by_user_id);
        $this->assertNull($primary->fresh()->effective_to);
        $this->assertFalse($hidden->fresh()->visible_to_client);
        foreach ([$otherDocument, $generalDocument, $primaryDocument] as $document) $this->assertTrue($document->fresh()->visible_to_client);
        $this->assertSame([$otherPlan->id], $account->activePlanIds());
        $this->assertTrue($account->fresh()->enabled);
        $this->assertSame($invoiceIds, $plan->invoices()->pluck('id')->all());
        $this->assertSame($transactionIds, $plan->financialTransactions()->pluck('id')->all());
        $this->assertSame(1, $plan->contractDocuments()->count());
        $this->get(route('admin.plans.show', $plan))->assertOk()->assertSee('Former clients')->assertSee('No longer participating');
        $this->get(route('admin.contract-setups.edit', $plan))->assertOk()->assertViewHas('prefill', fn ($data) => $data['co_mode'] === 'none');
        $this->get(route('admin.documents.download', $hidden))->assertOk();
        $this->actingAs($account, 'client')->get(route('portal.invoices.show', $invoice))->assertNotFound();
        $this->get(route('portal.documents.download', $hidden))->assertNotFound();
        $this->get(route('portal.documents.preview', $hidden))->assertNotFound();
        $this->get(route('portal.documents.index'))->assertOk()->assertDontSee('removed-plan.pdf')->assertSee('other-plan.pdf')
            ->assertViewHas('plans', fn ($plans) => !$plans->contains('id', $plan->id));
        $this->get(route('portal.messages.create'))->assertOk()->assertViewHas('plans', fn ($plans) => !$plans->contains('id', $plan->id));
        $this->post(route('portal.documents.store'), ['payment_plan_id' => $plan->id, 'category' => 'general', 'document' => UploadedFile::fake()->create('blocked.pdf', 10, 'application/pdf')])->assertStatus(422);
        $this->post(route('portal.messages.store'), ['payment_plan_id' => $plan->id, 'subject' => 'Blocked new thread', 'body' => 'Test'])->assertStatus(422);
        $this->get(route('portal.messages.show', $thread))->assertOk();
        $this->post(route('portal.messages.reply', $thread), ['body' => 'Existing conversation still works'])->assertSessionHasNoErrors();
        $this->post(route('portal.messages.reply', $thread), ['body' => 'New plan document', 'attachments' => [UploadedFile::fake()->create('blocked.pdf', 10, 'application/pdf')], 'save_in_documents' => [0]])->assertStatus(422);
        $this->actingAs($admin, 'web')->post(route('admin.documents.visibility', $hidden))->assertSessionHas('success');
        $this->actingAs($account, 'client')->get(route('portal.documents.download', $hidden))->assertOk();
        $this->assertSame($token, $link->fresh()->token_hash);
        $this->assertTrue($link->fresh()->isActive());
        $this->get($links->url($link))->assertRedirect(route('secure-invoice.show'));
        $this->get(route('secure-invoice.show'))->assertOk();
        Storage::disk('local')->assertExists($hidden->path);
    }

    public function test_co_client_removal_requires_reason_and_rejects_primary_other_plan_and_repeat_removal(): void
    {
        [$admin, $plan] = $this->revisionFixture();
        $primary = $plan->memberships()->firstOrFail();
        $this->delete(route('admin.plans.co-clients.destroy', [$plan, $primary->id]), ['reason' => 'Not allowed'])->assertStatus(422);
        $co = Client::query()->create(['client_type' => 'individual', 'first_name' => 'Second', 'last_name' => 'Client', 'created_by_user_id' => $admin->id, 'updated_by_user_id' => $admin->id]);
        $member = app(\App\Services\PaymentPlanMembershipService::class)->add($plan, $co, $admin, 'co_client', today());
        $url = route('admin.plans.co-clients.destroy', [$plan, $member->id]);
        $this->delete($url, ['reason' => '   '])->assertSessionHasErrors('reason');
        $other = $plan->replicate(['uuid']);
        $other->plan_number = 'OTHER-REMOVE';
        $other->save();
        $this->delete(route('admin.plans.co-clients.destroy', [$other, $member->id]), ['reason' => 'Wrong plan'])->assertNotFound();
        $this->assertNull($member->fresh()->effective_to);
        $this->delete($url, ['reason' => 'Correction', 'next' => 'contracts'])->assertRedirect(route('admin.contract-setups.edit', $plan));
        $this->delete($url, ['reason' => 'Repeated'])->assertSessionHasErrors('membership');
        $this->assertNull($primary->fresh()->effective_to);
        $this->assertDatabaseCount('payment_plan_clients', 2);
        $this->assertSame(1, \App\Models\AuditLog::query()->where('event', 'payment_plan.co_client_removed')->count());
    }

    private function revisionFixture(): array
    {
        Storage::fake('local');
        $this->travelTo(Carbon::parse('2026-09-01'));
        $admin = User::factory()->create();
        $this->actingAs($admin)->post(route('admin.contract-setups.store'), [
            'primary_mode' => 'new', 'primary_client_type' => 'individual', 'primary_first_name' => 'First',
            'primary_last_name' => 'Buyer', 'co_mode' => 'none', 'plan_number' => 'REVISION-1',
            'property_title' => 'Original property', 'purchase_price' => '2999.00', 'down_payment' => '300.00',
            'documentation_fee' => '249.00', 'plan_payment' => '120.00', 'service_fee' => '15.00',
            'contract_start_date' => '2026-08-28', 'first_invoice_date' => '2026-10-03',
            'create_first_payment_invoice' => '1', 'due_days_after_issue' => 5, 'grace_days' => 2,
            'stage_one_fee_type' => 'fixed', 'stage_one_fee_value' => '15.00',
            'stage_two_enabled' => '1', 'stage_two_days_late' => 30, 'stage_two_fee_type' => 'fixed',
            'stage_two_fee_value' => '50.00', 'default_eligibility_days' => 60,
            'contract_templates' => [$this->template()],
        ])->assertSessionHasNoErrors();
        $plan = PaymentPlan::query()->firstOrFail();
        $view = $this->get(route('admin.contract-setups.edit', $plan))->assertOk()->assertSee('value="2999.00"', false);
        $data = $view->viewData('prefill') + ['amendment_reason' => 'Add co-client', 'contract_templates' => [$this->template()]];
        $data['effective_from'] = today()->toDateString();
        $data['create_first_payment_invoice'] = '1';
        unset($data['email_first_payment_invoice']);
        return [$admin, $plan, $data];
    }

    private function template(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'landpay-contract-').'.docx';
        $word = new PhpWord;
        $word->addSection()->addText('$'.'{C1Name} | $'.'{C2Name} | $'.'{PFirstInvoiceDate} | $'.'{PPlanPayment}');
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
