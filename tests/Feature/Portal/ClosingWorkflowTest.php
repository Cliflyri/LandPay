<?php

namespace Tests\Feature\Portal;

use App\Enums\FinancialActorType;
use App\Enums\FinancialEffectComponent;
use App\Enums\FinancialEffectType;
use App\Enums\FinancialTransactionType;
use App\Financial\PostingEffect;
use App\Mail\ClosingNotificationMail;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\PaymentPlan;
use App\Models\PaymentPlanClient;
use App\Models\PortalAccount;
use App\Models\User;
use App\Services\ClosingWorkflowService;
use App\Services\ContractOpeningService;
use App\Services\FinancialBalanceService;
use App\Services\FinancialPostingService;
use App\Services\InvoiceSmsService;
use App\Services\SmsDeliveryService;
use App\Services\VestingGuideService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClosingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_combined_submission_is_atomic_and_drafts_cannot_be_approved(): void
    {
        [$admin, , $plan, $account] = $this->records('COMBINED');
        $this->action($admin, $plan, 'start')->assertSessionHasNoErrors();
        $data = $this->details();
        unset($data['extras_choice']);
        $this->actingAs($account, 'client')->post(route('portal.closing.update', $plan), ['version' => 1] + $data)
            ->assertSessionHasErrors('extras_choice');
        $this->assertNull($plan->fresh()->closing->details);
        $draft = array_replace($this->details(), ['action' => 'draft', 'confirmed' => 0, 'extras_choice' => 'request', 'extras_comments' => '']);
        $this->post(route('portal.closing.update', $plan), ['version' => 1] + $draft)->assertSessionHasNoErrors();
        $this->assertSame('draft', $plan->fresh()->closing->extras_status);
        $this->get(route('portal.dashboard'))->assertOk()->assertSee('Not yet submitted.')
            ->assertSee('Complete sections 1 and 2, then submit them together below.');
        $this->assertDatabaseMissing('admin_notices', ['type' => 'closing_submission', 'payment_plan_id' => $plan->id]);
        foreach (['details', 'extras'] as $section) {
            $this->action($admin, $plan, 'review_'.$section, ['section_status' => 'complete'])->assertSessionHasErrors('closing');
        }
        $this->actingAs($account, 'client')->post(route('portal.closing.update', $plan),
            ['version' => 2] + array_replace($this->details(), ['extras_choice' => 'request']))->assertSessionHasErrors('extras_comments');
        $this->post(route('portal.closing.update', $plan), ['version' => 2] + $this->details())->assertSessionHasNoErrors();
        $this->assertSame('submitted', $plan->fresh()->closing->details_status);
        $this->assertSame('submitted', $plan->fresh()->closing->extras_status);
        $this->assertTrue($plan->fresh()->closing->details['combined_submission']);
        $this->get(route('portal.dashboard'))->assertOk()->assertDontSee('Not yet submitted.');
        $response = $this->get(route('portal.dashboard'))->assertOk()->assertSee('Submit paperwork details and requests')
            ->assertDontSee('Submit request for review');
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertSame(1, $xpath->query('//form[@data-combined-closing]')->length);
        $this->assertSame(1, $xpath->query('//form[@data-combined-closing]//select[@name="beneficiary"]')->length);
        $this->assertSame(2, $xpath->query('//form[@data-combined-closing]//input[@name="extras_choice"]')->length);
        $this->assertSame(1, $xpath->query('//form[@data-combined-closing]//button[@value="details"]')->length);
    }

    public function test_existing_active_closing_gets_missing_guide_once_before_titling(): void
    {
        Storage::fake('local');
        [$admin, , $plan, $account] = $this->records('MISSINGGUIDE');
        $this->action($admin, $plan, 'start')->assertSessionHasNoErrors();
        $this->assertCount(0, $plan->fresh()->closing->documents);
        $this->actingAs($admin, 'web')->post(route('admin.closing-defaults.update'), ['document' => $this->guideFile()])->assertSessionHasNoErrors();
        $first = app(VestingGuideService::class)->current();
        $this->actingAs($account, 'client')->get(route('portal.dashboard'))->assertOk()
            ->assertSeeInOrder(['View vesting guide', 'Download PDF', 'How would you like the property titled?']);
        $closing = $plan->fresh()->closing;
        $this->assertSame($first['path'], $closing->documents()->sole()->path);
        $version = $closing->version;
        $this->actingAs($admin, 'web')->post(route('admin.closing-defaults.update'), ['document' => $this->guideFile('replacement.pdf')])->assertSessionHasNoErrors();
        $this->actingAs($account, 'client')->get(route('portal.dashboard'))->assertOk();
        $this->assertSame($first['path'], $closing->documents()->sole()->path);
        $this->assertSame($version, $closing->fresh()->version);
    }

    public function test_next_action_banners_and_optional_release_notification_follow_progress(): void
    {
        Mail::fake();
        [$admin, , $plan, $account] = $this->records('NEXTSTEP');
        $closing = app(ClosingWorkflowService::class)->closing($plan);
        $closing->update(['status' => 'active', 'details' => $this->details() + ['combined_submission' => true],
            'details_status' => 'submitted', 'extras_status' => 'submitted']);
        $this->actingAs($admin, 'web')->get(route('admin.plans.show', $plan))->assertOk()->assertSee('Client submitted sections 1 and 2. Please review below.');
        $this->action($admin, $plan, 'review_details', ['section_status' => 'complete'])->assertSessionHasNoErrors();
        $this->action($admin, $plan, 'review_extras', ['section_status' => 'not_required'])->assertSessionHasNoErrors();
        $closing->documents()->create(['kind' => 'signing', 'name' => 'forms.pdf', 'path' => 'closing/test-forms.pdf']);
        $this->action($admin, $plan, 'release', ['notify_client' => 1])->assertSessionHasNoErrors();
        Mail::assertSent(ClosingNotificationMail::class, fn ($mail) => str_contains($mail->nextStep, 'complete step 3'));
        $this->actingAs($account, 'client')->get(route('portal.dashboard'))->assertOk()->assertSee('Your signing packet is ready. Please review and complete step 3 below.');
        $this->post(route('portal.closing.update', $plan), ['action' => 'mailed', 'version' => $closing->fresh()->version])->assertSessionHasNoErrors();
        $this->actingAs($admin, 'web')->get(route('admin.plans.show', $plan))->assertOk()->assertSee('Client reports mailing signed forms. Please confirm receipt and review step 3.');
        $this->action($admin, $plan, 'review_forms', ['section_status' => 'complete'])->assertSessionHasNoErrors();
        $this->actingAs($account, 'client')->get(route('portal.dashboard'))->assertOk()->assertSee('Paperwork accepted')->assertDontSee('Your Closing Process');
        Mail::assertSent(ClosingNotificationMail::class, 1);
    }

    public function test_approved_client_controls_stay_hidden_until_explicitly_reopened(): void
    {
        [$admin, , $plan,$account] = $this->records('REOPENFLOW');
        $closing = app(ClosingWorkflowService::class)->closing($plan);
        $closing->update(['status' => 'active', 'details' => $this->details() + ['combined_submission' => true],
            'details_status' => 'complete', 'extras_status' => 'complete', 'forms_status' => 'complete',
            'released_at' => now(), 'paperwork_accepted_at' => now()]);
        $this->actingAs($account, 'client')->get(route('portal.dashboard'))->assertOk()
            ->assertSee('Paperwork accepted')->assertDontSee('Your Closing Process')
            ->assertDontSee('name="titling"', false)->assertDontSee('I have mailed the original paperwork');
        $accepted = $closing->paperwork_accepted_at->toDateTimeString();
        $this->action($admin, $plan, 'save', ['instructions' => ['Print the revised form.', 'Notarize.', 'Mail.']])->assertSessionHasNoErrors();
        $this->assertSame($accepted, $closing->fresh()->paperwork_accepted_at->toDateTimeString());
        $this->assertSame('complete', $closing->fresh()->forms_status);
        $this->action($admin, $plan, 'review_details', ['section_status' => 'complete'])->assertSessionHasNoErrors();
        $this->assertSame('complete', $closing->fresh()->forms_status);
        $this->action($admin, $plan, 'review_forms', ['section_status' => 'complete'])->assertSessionHasNoErrors();
        $this->assertSame($accepted, $closing->fresh()->paperwork_accepted_at->toDateTimeString());
        $this->action($admin, $plan, 'reopen_step', ['section' => 'forms', 'reopen_instruction' => 'Please sign the corrected form.'])->assertSessionHasNoErrors();
        $this->actingAs($account, 'client')->get(route('portal.dashboard'))->assertOk()
            ->assertSee('Step 3 reopened: Please sign the corrected form.')
            ->assertSee('Print the revised form.')->assertSee('I have mailed the original paperwork')
            ->assertSee('To make changes to your submitted requests, please contact us by')
            ->assertDontSee('1. Confirm Your Paperwork Details')->assertDontSee('2. Request Additional Paperwork')
            ->assertDontSee('4. Recording and Your Deed');
        $this->assertNotNull($closing->fresh()->released_at);
        $this->action($admin, $plan, 'reopen_step', ['section' => 'extras', 'reopen_instruction' => 'Please clarify your beneficiary request.'])->assertSessionHasNoErrors();
        $this->actingAs($account, 'client')->get(route('portal.dashboard'))->assertOk()
            ->assertSee('Step 2 reopened: Please clarify your beneficiary request.')
            ->assertSee('name="titling"', false)->assertSee('Submit paperwork details and requests')
            ->assertDontSee('3. Complete and Sign Required Forms')->assertDontSee('4. Recording and Your Deed');
        $this->assertNull($closing->fresh()->released_at);
        $this->action($admin, $plan, 'review_extras', ['section_status' => 'complete'])->assertSessionHasErrors('closing');
    }

    public function test_compact_recording_progress_and_undo_cascades_preserve_audit_and_billing(): void
    {
        [$admin, , $plan,$account] = $this->records('UNDOPROGRESS');
        $closing = app(ClosingWorkflowService::class)->closing($plan);
        $closing->update(['status' => 'completed', 'details' => $this->details() + ['combined_submission' => true],
            'details_status' => 'complete', 'extras_status' => 'complete', 'forms_status' => 'complete', 'released_at' => now(),
            'paperwork_accepted_at' => now(), 'ready_on' => today(), 'submitted_on' => today(), 'recorded_on' => today(), 'recording_reference' => 'REC-UNDO']);
        $page = $this->actingAs($account, 'client')->get(route('portal.dashboard'))->assertOk()->assertDontSee('Your Closing Process');
        $dom = new \DOMDocument;
        @$dom->loadHTML($page->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertSame(1, $xpath->query('//div[@id="closing-'.$plan->id.'"]/ol[@aria-label="Recording progress"]')->length);
        $this->assertSame(0, $xpath->query('//div[@id="closing-'.$plan->id.'"]//form')->length);
        $thread=$closing->threads()->create(['client_id'=>$account->client_id,'payment_plan_id'=>$plan->id,
            'subject'=>'Closing history','category'=>'general','latest_message_at'=>now()]);
        $thread->messages()->create(['sender_type'=>'admin','sender_user_id'=>$admin->id,'body'=>'Your county paperwork was submitted.']);
        $this->get(route('portal.messages.show',$thread))->assertOk()->assertSee('Your county paperwork was submitted.')->assertDontSee('Send reply');

        $this->actingAs($admin, 'web')->get(route('admin.plans.show', $plan))->assertOk()->assertSee('Undo Recording confirmed');
        $this->action($admin, $plan, 'save', ['instructions' => ['Print.', 'Notarize.', 'Mail.']])->assertSessionHasNoErrors();
        $this->assertTrue($closing->fresh()->compactProgress());
        $this->action($admin, $plan, 'undo_progress', ['milestone' => 'recorded'])->assertSessionHasNoErrors();
        $this->assertNull($closing->fresh()->recorded_on);
        $this->assertNotNull($closing->fresh()->submitted_on);
        $this->assertTrue($closing->fresh()->compactProgress());
        $this->actingAs($account,'client')->get(route('portal.messages.show',$thread))->assertOk()->assertSee('Send reply');
        $this->post(route('portal.closing.messages',$plan),['body'=>'Thank you for the recording update.'])
            ->assertRedirect(route('portal.messages.show',$thread))->assertSessionHasNoErrors();
        $this->action($admin, $plan, 'undo_progress', ['milestone' => 'submitted'])->assertSessionHasNoErrors();
        $this->assertNull($closing->fresh()->submitted_on);
        $this->assertNotNull($closing->fresh()->ready_on);
        $this->actingAs($account, 'client')->get(route('portal.dashboard'))->assertOk()->assertDontSee('Your Closing Process')->assertSee('Paperwork accepted')->assertDontSee('name="titling"', false);
        $this->action($admin, $plan, 'undo_progress', ['milestone' => 'ready'])->assertSessionHasNoErrors();
        $this->assertNotNull($closing->fresh()->paperwork_accepted_at);
        $this->assertSame('complete', $closing->fresh()->forms_status);
        $this->assertTrue($closing->fresh()->compactProgress());
        $this->action($admin, $plan, 'undo_progress', ['milestone' => 'paperwork'])->assertSessionHasNoErrors();
        $this->assertSame('needed', $closing->fresh()->forms_status);
        $this->actingAs($account, 'client')->get(route('portal.dashboard'))->assertOk()->assertSee('Step 3 reopened:');
        $this->action($admin, $plan, 'undo_progress', ['milestone' => 'paperwork'])->assertSessionHasErrors('closing');
        $this->assertSame('REC-UNDO', $closing->events()->where('action', 'undo_progress')->reorder()->oldest('id')->first()->context['previous']['recording_reference']);
        $this->assertSame('active', $plan->fresh()->status);
        // Undoing an earlier milestone also clears later milestones in one action.
        $closing->refresh()->update(['status' => 'completed', 'ready_on' => today(), 'submitted_on' => today(), 'recorded_on' => today()]);
        $this->action($admin, $plan, 'undo_progress', ['milestone' => 'ready'])->assertSessionHasNoErrors();
        $this->assertNull($closing->fresh()->ready_on);
        $this->assertNull($closing->fresh()->submitted_on);
        $this->assertNull($closing->fresh()->recorded_on);
    }

    private function guideFile(string $name = 'vesting.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
    }

    public function test_shared_vesting_versions_are_pinned_and_explicitly_updated(): void
    {
        Storage::fake('local');
        [$admin, , $plan, $account] = $this->records('GUIDE');
        $this->actingAs($admin, 'web')->post(route('admin.closing-defaults.update'), ['document' => $this->guideFile()])
            ->assertSessionHasNoErrors();
        $first = app(VestingGuideService::class)->current();
        $this->get(route('admin.settings.index', ['section' => 'closing']))->assertOk()->assertSee('Default vesting guide');
        $this->action($admin, $plan, 'start')->assertSessionHasNoErrors();
        $document = $plan->fresh()->closing->documents()->where('kind', 'vesting')->sole();
        $this->assertSame($first['path'], $document->path);
        $this->actingAs($account, 'client')->get(route('portal.dashboard'))->assertOk()
            ->assertSee('View vesting guide')->assertSee('Download PDF')->assertSee('Open in new tab')->assertSee('data-vesting-modal', false);
        $this->get(route('portal.closing.documents.download', [$plan, $document, 'inline' => 1]))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertHeader('Content-Disposition', 'inline');
        $this->get(route('portal.closing.documents.download', [$plan, $document]))->assertDownload('vesting.pdf');
        $this->actingAs($admin, 'web')->post(route('admin.closing-defaults.update'), ['document' => $this->guideFile('new-guide.pdf')])->assertSessionHasNoErrors();
        $latest = app(VestingGuideService::class)->current();
        $this->assertNotSame($first['path'], $latest['path']);
        $this->assertSame($first['path'], $document->fresh()->path);
        $this->get(route('admin.plans.show', $plan))->assertOk()->assertSee('previous version')->assertSee('Update to latest guide');
        [,, $second] = $this->records('GUIDETWO');
        $this->action($admin, $second, 'start')->assertSessionHasNoErrors();
        $this->assertSame($latest['path'], $second->fresh()->closing->documents()->sole()->path);
        $this->action($admin, $plan, 'use_default_vesting')->assertSessionHasNoErrors();
        $this->assertSame($latest['path'], $plan->fresh()->closing->documents()->sole()->path);
        Storage::disk('local')->assertExists($first['path']);
    }

    public function test_plan_vesting_override_and_removal_never_delete_shared_pdf(): void
    {
        Storage::fake('local');
        [$admin, , $plan] = $this->records('OVERRIDE');
        $this->actingAs($admin, 'web')->post(route('admin.closing-defaults.update'), ['document' => $this->guideFile()])->assertSessionHasNoErrors();
        $shared = app(VestingGuideService::class)->current();
        $this->action($admin, $plan, 'start')->assertSessionHasNoErrors();
        $this->post(route('admin.closing.documents.upload', $plan), [
            'kind' => 'vesting', 'version' => $plan->fresh()->closing->version, 'document' => $this->guideFile('county.pdf'),
        ])->assertSessionHasNoErrors();
        $override = $plan->fresh()->closing->documents()->where('kind', 'vesting')->sole();
        $this->assertSame('county.pdf', $override->name);
        $this->assertNotSame($shared['path'], $override->path);
        $this->get(route('admin.plans.show', $plan))->assertOk()->assertSee('Using a different PDF for this plan');
        $this->action($admin, $plan, 'use_default_vesting')->assertSessionHasNoErrors();
        $document = $plan->fresh()->closing->documents()->sole();
        $this->delete(route('admin.closing.documents.remove', [$plan, $document]), ['version' => $plan->fresh()->closing->version])->assertSessionHasNoErrors();
        Storage::disk('local')->assertExists($shared['path']);
        $this->assertCount(0, $plan->fresh()->closing->documents);
    }

    public function test_vesting_pdf_validation_and_preview_authorization(): void
    {
        Storage::fake('local');
        [$admin, , $plan,$account] = $this->records('GUIDEACCESS');
        $this->post(route('admin.closing-defaults.update'), ['document' => $this->guideFile()])->assertRedirect(route('admin.login'));
        $this->actingAs($admin, 'web')->post(route('admin.closing-defaults.update'), ['document' => UploadedFile::fake()->image('bad.png')])->assertSessionHasErrors('document');
        $this->post(route('admin.closing-defaults.update'), ['document' => $this->guideFile()])->assertSessionHasNoErrors();
        $this->action($admin, $plan, 'start')->assertSessionHasNoErrors();
        $document = $plan->fresh()->closing->documents()->sole();
        [,,, $outsider] = $this->records('GUIDEOUTSIDER');
        $url = route('portal.closing.documents.download', [$plan, $document, 'inline' => 1]);
        $this->actingAs($outsider, 'client')->get($url)->assertNotFound();
        $this->action($admin, $plan, 'disable', ['show_hold_notice' => 0])->assertSessionHasNoErrors();
        $this->actingAs($account, 'client')->get($url)->assertForbidden();
    }

    private function records(string $suffix = 'ONE', bool $paid = true): array
    {
        $admin = User::factory()->create();
        $client = Client::create(['client_type' => 'individual', 'first_name' => 'Chris', 'middle_name' => 'A', 'last_name' => $suffix,
            'email' => strtolower($suffix).'@example.com', 'address_line_1' => '123 Main', 'city' => 'Phoenix', 'state_region' => 'AZ', 'postal_code' => '85001',
            'country_code' => 'US', 'created_by_user_id' => $admin->id, 'updated_by_user_id' => $admin->id]);
        $plan = PaymentPlan::create(['plan_number' => 'LP-'.$suffix, 'title' => 'Closing '.$suffix, 'purchase_price' => 100000,
            'documentation_fee_standard' => 0, 'documentation_fee_waived' => 0, 'original_purchase_balance' => 100000,
            'customary_monthly_payment' => 10000, 'monthly_service_fee' => 0, 'monthly_due_day' => 1,
            'first_due_date' => today()->toDateString(), 'plan_start_date' => today()->toDateString(), 'status' => 'draft',
            'created_by_user_id' => $admin->id, 'updated_by_user_id' => $admin->id]);
        app(ContractOpeningService::class)->open($plan, $admin, 100000, 0, 0, today());
        $plan->refresh()->update(['status' => 'active', 'activated_at' => now()]);
        PaymentPlanClient::create(['payment_plan_id' => $plan->id, 'client_id' => $client->id, 'role' => 'primary', 'responsibility' => 'joint',
            'receives_invoices' => true, 'effective_from' => today(), 'created_by_user_id' => $admin->id]);
        $account = PortalAccount::create(['client_id' => $client->id, 'email' => $client->email, 'password' => 'password', 'enabled' => true]);
        if ($paid) {
            app(FinancialPostingService::class)->post($plan, FinancialTransactionType::Payment, 100000, today(), FinancialActorType::Administrator,
                [new PostingEffect(FinancialEffectType::PurchaseBalance, -100000, FinancialEffectComponent::PurchasePricePrincipal)], actor: $admin);
        }

        return [$admin, $client, $plan, $account];
    }

    private function action(User $admin, PaymentPlan $plan, string $action, array $data = [])
    {
        return $this->actingAs($admin, 'web')->post(route('admin.closing.update', $plan),
            ['action' => $action, 'version' => $plan->fresh()->closing?->version ?? 0] + $data);
    }

    private function details(): array
    {
        return ['action' => 'details', 'titling' => 'Sole owner', 'owners' => [
            ['name' => 'Chris A Owner', 'address' => '123 Main, Phoenix AZ 85001', 'mailing_address' => '', 'married' => 'no'],
            ['name' => 'Second Owner', 'address' => '456 Oak, Phoenix AZ 85001', 'married' => 'yes'],
        ], 'beneficiary' => 'no', 'confirmed' => 1, 'extras_choice' => 'none', 'extras_comments' => ''];
    }

    private function extraInvoice(User $admin, PaymentPlan $plan): Invoice
    {
        $invoice = Invoice::create(['payment_plan_id' => $plan->id, 'invoice_number' => 'TAX-'.$plan->id, 'issue_date' => today(),
            'due_date' => today(), 'status' => 'issued', 'issued_at' => now(), 'created_by_user_id' => $admin->id]);
        app(FinancialPostingService::class)->post($plan, FinancialTransactionType::InvoiceCharge, 15000, today(), FinancialActorType::Administrator,
            [new PostingEffect(FinancialEffectType::InvoiceDue, 15000, FinancialEffectComponent::PropertyTax, invoiceId: $invoice->id)],
            actor: $admin, invoice: $invoice);

        return $invoice;
    }

    public function test_trigger_ignores_extra_invoice_and_dismissal_is_per_admin(): void
    {
        [$admin,,$plan,$account] = $this->records();
        $this->extraInvoice($admin, $plan);
        $this->assertTrue(app(ClosingWorkflowService::class)->eligible($plan));
        $this->assertSame(0, app(FinancialBalanceService::class)->contractBalance($plan));
        $this->actingAs($account, 'client')->get(route('portal.dashboard'))->assertOk()->assertSee('may be fulfilled')->assertDontSee('Your Closing Process');
        $this->actingAs($admin, 'web')->get(route('admin.dashboard'))->assertOk()->assertSee('Ready to close')->assertDontSee('Fully Satisfied');
        $closing = $plan->fresh()->closing;
        $this->assertNotNull($closing->eligible_at);
        $this->post(route('admin.closing.dismiss', $plan))->assertSessionHasNoErrors();
        $this->get(route('admin.dashboard'))->assertOk()->assertDontSee('Ready to close:')->assertSee('Ready to close');
        $this->assertCount(0, app(ClosingWorkflowService::class)->notices($admin->id));
        $this->assertCount(1, app(ClosingWorkflowService::class)->notices(User::factory()->create()->id));
        $plan->update(['status' => 'paused']);
        $this->assertTrue(app(ClosingWorkflowService::class)->eligible($plan->fresh()));
        $plan->update(['status' => 'terminated']);
        $this->assertFalse(app(ClosingWorkflowService::class)->eligible($plan->fresh()));
        $this->actingAs($admin, 'web')->get(route('admin.plans.index', ['status' => 'all']))->assertOk()->assertSee('Ready to close');
    }

    public function test_early_start_hold_visibility_and_permissions(): void
    {
        [$admin,,$plan,$account] = $this->records('EARLY', false);
        $this->actingAs($account, 'client')->get(route('portal.dashboard'))->assertDontSee('may be fulfilled');
        $this->actingAs($admin, 'web')->get(route('admin.plans.show', $plan))->assertOk()->assertSee('Start closing')->assertSee('Outstanding balances');
        $this->action($admin, $plan, 'start')->assertSessionHasNoErrors();
        $this->actingAs($account, 'client')->get(route('portal.dashboard'))->assertOk()->assertSeeInOrder(['Your Closing Process', 'Your Monthly Payments'])
            ->assertSee('Chris A EARLY')->assertDontSee('3. Complete and Sign Required Forms')->assertDontSee('4. Recording and Your Deed')->assertSee('for an additional fee');
        [,, $other,$outsider] = $this->records('OTHER');
        $this->actingAs($outsider, 'client')->post(route('portal.closing.update', $plan), ['action' => 'draft', 'version' => 1])->assertNotFound();
        $this->action($admin, $plan, 'disable', ['show_hold_notice' => 1, 'client_note' => 'Waiting for tax confirmation.'])->assertSessionHasNoErrors();
        $this->actingAs($account, 'client')->get(route('portal.dashboard'))->assertOk()->assertSee('currently on hold')->assertSee('Waiting for tax confirmation.')->assertDontSee('Confirm Your Paperwork Details');
        $this->post(route('portal.closing.update', $plan), ['action' => 'draft', 'version' => 2])->assertForbidden();
        $this->action($admin, $plan, 'resume')->assertSessionHasNoErrors();
        $this->action($admin, $plan, 'disable', ['show_hold_notice' => 0])->assertSessionHasNoErrors();
        $this->actingAs($account, 'client')->get(route('portal.dashboard'))->assertDontSee('currently on hold')->assertDontSee('Your Closing Process');
        $this->assertSame('active', $plan->fresh()->status);
    }

    public function test_details_validation_review_release_and_changes_require_review_again(): void
    {
        Storage::fake('local');
        [$admin,,$plan,$account] = $this->records('DETAIL');
        $this->action($admin, $plan, 'save', ['admin_notes' => 'Keep general notes', 'vesting_notes' => 'Keep vesting notes'])->assertSessionHasNoErrors();
        $this->action($admin, $plan, 'save', ['instructions' => $plan->fresh()->closing->instructions()])->assertSessionHasNoErrors();
        $this->assertSame('Keep general notes', $plan->fresh()->closing->admin_notes);
        $this->assertSame('Keep vesting notes', $plan->fresh()->closing->vesting_notes);

        $this->action($admin, $plan, 'start')->assertSessionHasNoErrors();
        $page = $this->get(route('admin.plans.show', $plan))->assertOk()->assertDontSee('<summary>Closing documents</summary>', false)
            ->assertSeeInOrder(['Upload signing forms', 'Review uploaded signing forms', 'Review signing and mailing instructions', 'Release final signing packet'])
            ->assertSee('Upload at least one signing form above.');
        $dom = new \DOMDocument;
        @$dom->loadHTML($page->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertSame(1, $xpath->query('//form[input[@name="action" and @value="release"]]//button[@disabled]')->length);

        $this->action($admin, $plan, 'review_details', ['section_status' => 'complete'])->assertSessionHasErrors('closing');
        $this->actingAs($account, 'client')->post(route('portal.closing.update', $plan), ['version' => $plan->fresh()->closing->version] + array_replace($this->details(), ['confirmed' => 0]))->assertSessionHasErrors('confirmed');
        $this->post(route('portal.closing.update', $plan), ['version' => $plan->fresh()->closing->version] + $this->details())->assertSessionHasNoErrors();
        $this->assertCount(2, $plan->fresh()->closing->details['owners']);
        $this->action($admin, $plan, 'review_details', ['section_status' => 'complete'])->assertSessionHasNoErrors();
        $this->action($admin, $plan, 'release')->assertSessionHasErrors('closing');
        $this->action($admin, $plan, 'review_extras', ['section_status' => 'complete'])->assertSessionHasNoErrors();
        $this->actingAs($admin, 'web')->post(route('admin.closing.documents.upload', $plan), ['version' => $plan->fresh()->closing->version,
            'kind' => 'signing', 'document' => UploadedFile::fake()->create('affidavit.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
        $document = $plan->fresh()->closing->documents()->sole();
        $page = $this->get(route('admin.plans.show', $plan))->assertOk();
        $dom = new \DOMDocument;
        @$dom->loadHTML($page->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertSame(1, $xpath->query('//form[input[@name="action" and @value="release"]]//button[not(@disabled)]')->length);

        $this->actingAs($account, 'client')->get(route('portal.closing.documents.download', [$plan, $document]))->assertForbidden();
        $this->action($admin, $plan, 'release')->assertSessionHasNoErrors();
        $this->get(route('admin.plans.show', $plan))->assertOk()->assertSee('Awaiting signed forms');
        $this->actingAs($account, 'client')->get(route('portal.closing.documents.download', [$plan, $document]))->assertOk();
        $this->get(route('portal.dashboard'))->assertOk()->assertSee('Obtain notarization')->assertSee('section 2')->assertSee('affidavit.pdf')->assertSee('Ready to sign and mail');
        $this->post(route('portal.closing.update', $plan), ['version' => $plan->fresh()->closing->version] + $this->details())->assertSessionHasErrors('closing');
        $this->action($admin, $plan, 'reopen_step', ['section' => 'details', 'reopen_instruction' => 'Please correct the legal address.'])->assertSessionHasNoErrors();
        $this->actingAs($account, 'client')->get(route('portal.dashboard'))->assertOk()->assertSee('Step 1 reopened: Please correct the legal address.');

        $this->post(route('portal.closing.update', $plan), ['version' => $plan->fresh()->closing->version] + $this->details())->assertSessionHasNoErrors();
        $this->assertNull($plan->fresh()->closing->released_at);
        $this->assertSame('submitted', $plan->fresh()->closing->details_status);
        $this->assertSame('submitted', $plan->fresh()->closing->extras_status);
        $this->get(route('portal.closing.documents.download', [$plan, $document]))->assertForbidden();
        $this->action($admin, $plan, 'review_details', ['section_status' => 'complete'])->assertSessionHasNoErrors();
        $this->actingAs($admin, 'web')->get(route('admin.plans.show', $plan))->assertOk()->assertSee('Second Owner');
    }

    public function test_recording_checks_fresh_balances_at_every_milestone_without_affecting_billing(): void
    {
        [$admin,,$plan,$account] = $this->records('RECORD');
        $closing = app(ClosingWorkflowService::class)->closing($plan);
        $closing->update(['status' => 'active', 'details_status' => 'complete', 'extras_status' => 'not_required', 'forms_status' => 'not_required', 'paperwork_accepted_at' => now()]);
        $this->action($admin, $plan, 'complete', ['milestone_date' => today()->toDateString()])->assertSessionHasErrors('closing');
        $this->action($admin, $plan, 'ready', ['milestone_date' => today()->toDateString()])->assertSessionHasNoErrors();
        $invoice = $this->extraInvoice($admin, $plan);
        foreach (['submit', 'complete'] as $action) {
            $this->action($admin, $plan, $action, ['milestone_date' => today()->toDateString()])->assertSessionHasErrors('closing');
            $this->action($admin, $plan, $action, ['milestone_date' => today()->toDateString(), 'acknowledge_balance' => 1, 'balance_reason' => 'Tax payment arranged separately.', 'recording_reference' => 'REC-123'])->assertSessionHasNoErrors();
        }
        $this->assertSame('completed', $closing->fresh()->status);
        $this->assertSame('active', $plan->fresh()->status);
        $this->assertSame(15000, app(FinancialBalanceService::class)->invoiceBalance($invoice));
        $this->assertSame(2, $closing->events()->get()->filter(fn ($event) => ($event->context['invoice_balance'] ?? 0) === 15000)->count());
        $this->actingAs($account, 'client')->get(route('portal.dashboard'))->assertOk()->assertSee('Recording confirmed')->assertDontSee('Your Closing Process')->assertDontSee('REC-123');
        $this->post(route('portal.closing.update', $plan), ['action' => 'draft', 'version' => $closing->fresh()->version])->assertForbidden();
    }

    public function test_notifications_messages_and_private_files_cannot_bypass_hold_or_membership(): void
    {
        Mail::fake();
        Storage::fake('local');
        [$admin,$client,$plan,$account] = $this->records('MESSAGE');
        $this->action($admin, $plan, 'start', ['notify_client' => 1])->assertSessionHasNoErrors();
        Mail::assertSent(ClosingNotificationMail::class, 1);
        $this->actingAs($account, 'client')->post(route('portal.closing.messages', $plan), ['body' => 'Please explain vesting.'])->assertSessionHasNoErrors();
        $thread = $plan->fresh()->closing->threads()->sole();
        $this->get(route('portal.messages.show', $thread))->assertRedirect(route('portal.dashboard').'#closing-messages-'.$plan->id);
        $this->post(route('portal.messages.reply', $thread), ['body' => 'Bypass'])->assertNotFound();
        $this->actingAs($admin, 'web')->post(route('admin.closing.messages', $plan), ['body' => 'We can help.', 'client_id' => $client->id])->assertSessionHasNoErrors();
        $this->get(route('admin.messages.show', $thread))->assertRedirect(route('admin.plans.show', $plan).'#closing-messages');
        $this->actingAs($account, 'client')->post(route('portal.closing.messages', $plan), ['body' => 'Thank you.'])->assertSessionHasNoErrors();
        $this->get(route('portal.dashboard'))->assertOk()->assertSee('Earlier messages (1)')->assertSee('We can help.');
        $this->action($admin, $plan, 'disable', ['show_hold_notice' => 0])->assertSessionHasNoErrors();
        $this->actingAs($account, 'client')->get(route('portal.messages.show', $thread))->assertNotFound();
        $this->post(route('portal.closing.messages', $plan), ['body' => 'Held'])->assertForbidden();
        $this->action($admin, $plan, 'resume')->assertSessionHasNoErrors();
        PaymentPlanClient::where('payment_plan_id', $plan->id)->update(['effective_to' => today()]);
        $this->actingAs($account, 'client')->get(route('portal.messages.show', $thread))->assertNotFound();
        $this->post(route('portal.closing.messages', $plan), ['body' => 'Former member'])->assertNotFound();
    }

    public function test_private_downloads_and_impersonation_cannot_bypass_access_checks(): void
    {
        Storage::fake('local');
        [$admin,,$plan,$account] = $this->records('FILES');
        [,, $other,$outsider] = $this->records('FILEOTHER');
        $closing = app(ClosingWorkflowService::class)->closing($plan);
        $closing->update(['status' => 'active', 'released_at' => now()]);
        Storage::disk('local')->put('closing/example.pdf', 'private');
        $document = $closing->documents()->create(['kind' => 'signing', 'name' => 'example.pdf', 'path' => 'closing/example.pdf']);
        $this->actingAs($outsider, 'client')->get(route('portal.closing.documents.download', [$plan, $document]))->assertNotFound();
        $this->actingAs($account, 'client')->get(route('portal.closing.documents.download', [$other, $document]))->assertNotFound();
        $this->get(route('portal.closing.documents.download', [$plan, $document]))->assertOk();
        $this->withSession(['portal_impersonation' => ['client_name' => 'Example']])
            ->post(route('portal.closing.update', $plan), ['action' => 'draft', 'version' => 0])->assertForbidden();
        session()->forget('portal_impersonation');
        $closing->update(['status' => 'hold', 'show_hold_notice' => false]);
        $this->get(route('portal.closing.documents.download', [$plan, $document]))->assertForbidden();
        $closing->update(['status' => 'active']);
        PaymentPlanClient::where('payment_plan_id', $plan->id)->update(['effective_to' => today()]);
        $this->get(route('portal.closing.documents.download', [$plan, $document]))->assertNotFound();
    }

    public function test_marital_status_confirmation_and_beneficiary_are_required_and_sms_can_send(): void
    {
        Mail::fake();
        [$admin,$client,$plan,$account] = $this->records('REQUIRED');
        $client->smsPreference()->create(['enabled' => true, 'sms_phone_e164' => '+16025550123']);
        $this->mock(InvoiceSmsService::class)->shouldReceive('eligible')->once()->andReturnUsing(function ($recipient) {
            $recipient->load('smsPreference');

            return true;
        });
        $this->mock(SmsDeliveryService::class)->shouldReceive('send')->once()->withArgs(
            fn ($phone, $body, $type, $key, $actor, $recipient) => $phone === '+16025550123' && $type === 'closing'
                && str_contains($body, $plan->plan_number) && $recipient->id === $client->id
        );
        $this->action($admin, $plan, 'start', ['notify_client' => 1])->assertSessionHasNoErrors();
        $details = $this->details();
        unset($details['owners'][1]['married']);
        $details['beneficiary'] = '';
        $this->actingAs($account, 'client')->post(route('portal.closing.update', $plan), ['version' => 1] + $details)
            ->assertSessionHasErrors(['owners.1.married', 'beneficiary']);
        $details = $this->details();
        $details['beneficiary'] = 'yes';
        $details['extras_choice'] = 'request';
        $details['extras_comments'] = 'Please discuss a beneficiary deed.';
        $this->post(route('portal.closing.update', $plan), ['version' => 1] + $details)->assertSessionHasNoErrors();
        $closing = $plan->fresh()->closing;
        $this->assertSame('request', $closing->extras_choice);
        $this->post(route('portal.closing.update', $plan), ['action' => 'extras', 'version' => $closing->version, 'extras_choice' => 'none'])->assertSessionHasErrors('action');
        $this->assertDatabaseHas('admin_notices', ['type' => 'closing_submission', 'payment_plan_id' => $plan->id]);
    }

    public function test_stale_form_cannot_approve_changed_details_and_sms_respects_existing_eligibility(): void
    {
        Mail::fake();
        [$admin,,$plan] = $this->records('STALE');
        $this->mock(InvoiceSmsService::class)->shouldReceive('eligible')->once()->andReturn(false);
        $this->mock(SmsDeliveryService::class)->shouldNotReceive('send');
        $this->action($admin, $plan, 'start', ['notify_client' => 1])->assertSessionHasNoErrors();
        $this->actingAs($admin, 'web')->post(route('admin.closing.update', $plan), ['action' => 'disable', 'version' => 0])->assertSessionHasErrors('closing');
        $this->assertSame('active', $plan->fresh()->closing->status);
        $this->get(route('admin.dashboard'))->assertOk()->assertSeeInOrder(['Fully Satisfied', 'Closing in progress']);
    }
}
