<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotice;
use App\Models\AuditLog;
use App\Models\BillingDefault;
use App\Models\Client;
use App\Models\ContractDocument;
use App\Models\PaymentPlan;
use App\Models\PaymentPlanBillingTerm;
use App\Services\ContractDocumentService;
use App\Services\ContractAmountAmendmentService;
use App\Services\PortalInvitationService;
use App\Services\ContractOpeningService;
use App\Services\FirstPaymentInvoiceService;
use App\Services\InvoiceEmailService;
use App\Services\PaymentPlanMembershipService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContractSetupController extends Controller
{
    public function __construct(
        private readonly PaymentPlanMembershipService $memberships,
        private readonly ContractOpeningService $opening,
        private readonly ContractDocumentService $documents,
        private readonly FirstPaymentInvoiceService $firstPaymentInvoices,
        private readonly InvoiceEmailService $invoiceEmails,
    ) {}

    private function countySuggestions(): \Illuminate\Support\Collection
    {
        return PaymentPlan::whereNotNull('property_county')->distinct()->orderBy('property_county')->pluck('property_county')
            ->map(fn ($county) => trim($county))->filter()->unique(fn ($county) => mb_strtolower($county))->values();
    }

    public function create(Request $request): View
    {
        return view('admin.contract-setups.create', [
            'counties' => $this->countySuggestions(),
            'clients' => Client::query()->whereNull('archived_at')->orderBy('last_name')->orderBy('first_name')->get(),
            'selectedClient' => $request->integer('client') ?: null,
            'defaults' => BillingDefault::query()->latest('id')->first(),
        ]);
    }

    private function rules(?PaymentPlan $plan = null): array
    {
        return [
            'primary_mode' => ['required', Rule::in(['existing', 'new'])],
            'primary_client_id' => ['nullable', 'required_if:primary_mode,existing', 'exists:clients,id'],
            'primary_client_type' => ['nullable', 'required_if:primary_mode,new', Rule::in(['individual', 'organization'])],
            'primary_organization_name' => ['nullable', 'string', 'max:180'],
            'primary_first_name' => ['nullable', 'string', 'max:100'],
            'primary_last_name' => ['nullable', 'string', 'max:100'],
            'primary_email' => ['nullable', 'email', 'max:254'],
            'primary_phone' => ['nullable', 'string', 'max:32'],
            'primary_address_line_1' => ['nullable', 'string', 'max:150'],
            'primary_address_line_2' => ['nullable', 'string', 'max:150'],
            'primary_city' => ['nullable', 'string', 'max:100'],
            'primary_state_region' => ['nullable', 'string', 'max:100'],
            'primary_postal_code' => ['nullable', 'string', 'max:24'],
            'co_mode' => ['required', Rule::in(['none', 'existing', 'new'])],
            'co_client_id' => ['nullable', 'required_if:co_mode,existing', 'different:primary_client_id', 'exists:clients,id'],
            'co_client_type' => ['nullable', 'required_if:co_mode,new', Rule::in(['individual', 'organization'])],
            'co_organization_name' => ['nullable', 'string', 'max:180'],
            'co_first_name' => ['nullable', 'string', 'max:100'],
            'co_last_name' => ['nullable', 'string', 'max:100'],
            'co_email' => ['nullable', 'email', 'max:254'],
            'co_phone' => ['nullable', 'string', 'max:32'],
            'co_address_line_1' => ['nullable', 'string', 'max:150'],
            'co_address_line_2' => ['nullable', 'string', 'max:150'],
            'co_city' => ['nullable', 'string', 'max:100'],
            'co_state_region' => ['nullable', 'string', 'max:100'],
            'co_postal_code' => ['nullable', 'string', 'max:24'],
            'plan_number' => ['required', 'string', 'max:40', Rule::unique('payment_plans', 'plan_number')->where(fn ($query) => $query->whereIn('status', ['draft', 'active', 'paused']))->ignore($plan?->id)],
            'property_title' => ['required', 'string', 'max:180'],
            'property_description' => ['nullable', 'string'],
            'property_county' => ['nullable', 'string', 'max:100'],
            'purchase_price' => ['required', 'decimal:0,2', 'gt:0'],
            'down_payment' => ['required', 'decimal:0,2', 'min:0'],
            'documentation_fee' => ['required', 'decimal:0,2', 'min:0'],
            'email_first_payment_invoice' => ['nullable', 'boolean', 'prohibited_unless:create_first_payment_invoice,1'],
            'first_payment_due_date' => ['nullable', 'date'],
            'due_days_after_issue' => ['required', 'integer', 'between:0,60'],
            'grace_days' => ['required', 'integer', 'between:0,60'],
            'stage_one_fee_type' => ['required', Rule::in(['fixed', 'percentage'])],
            'stage_one_fee_value' => ['required', 'numeric', 'min:0'],
            'stage_one_minimum_amount' => ['nullable', 'required_if:stage_one_fee_type,percentage', 'decimal:0,2'],
            'stage_two_enabled' => ['nullable', 'boolean'],
            'stage_two_days_late' => ['nullable', 'required_if:stage_two_enabled,1', 'integer', 'between:1,365'],
            'stage_two_fee_type' => ['nullable', 'required_if:stage_two_enabled,1', Rule::in(['fixed', 'percentage'])],
            'stage_two_fee_value' => ['nullable', 'required_if:stage_two_enabled,1', 'numeric', 'min:0'],
            'stage_two_minimum_amount' => ['nullable', 'required_if:stage_two_fee_type,percentage', 'decimal:0,2'],
            'default_eligibility_days' => ['required', 'integer', 'between:1,730'],
            'create_first_payment_invoice' => ['nullable', 'boolean'],
            'plan_payment' => ['required', 'decimal:0,2', 'gt:0'],
            'service_fee' => ['required', 'decimal:0,2', 'min:0'],
            'hoa_fee' => ['nullable', 'decimal:0,2', 'min:0'],
            'hoa_term' => ['nullable', 'string', 'max:50'],
            'govdeals' => ['nullable', 'boolean'],
            'contract_start_date' => ['required', 'date'],
            'first_invoice_date' => ['required', 'date', 'after_or_equal:contract_start_date'],
            'contract_templates' => ['required', 'array', 'between:1,10'],
            'contract_templates.*' => ['required', 'file', 'mimes:docx', 'max:10240'],
            'effective_from' => [$plan ? 'required' : 'nullable', 'date'],
            'amendment_reason' => [$plan ? 'required' : 'nullable', 'string', 'max:500'],
            'co_effective_from' => ['nullable', 'date'],
            'co_receives_invoices' => ['nullable', 'boolean'],
            'invite_co_client' => ['nullable', 'boolean'],
        ];
    }

    public function store(Request $request, ?PaymentPlan $plan = null): RedirectResponse
    {
        $data = $request->validate($this->rules($plan));

        $county = trim((string) ($data['property_county'] ?? ''));
        $data['property_county'] = $county === '' ? null : ($this->countySuggestions()->first(fn ($existing) => mb_strtolower($existing) === mb_strtolower($county)) ?? $county);

        if ($data['primary_mode'] === 'new') {
            $this->validateNewClient($data, 'primary');
        }
        if ($data['co_mode'] === 'new') {
            $this->validateNewClient($data, 'co');
        }

        $purchase = Money::toCents($data['purchase_price']);
        $down = Money::toCents($data['down_payment']);
        if ($down >= $purchase) {
            return back()->withInput()->withErrors(['down_payment' => 'Down payment must be less than the purchase price.']);
        }

        $stageOneDaysLate = (int) $data['grace_days'] + 1;
        if (($data['stage_two_enabled'] ?? false) && (int) $data['stage_two_days_late'] <= $stageOneDaysLate) {
            throw ValidationException::withMessages(['stage_two_days_late' => 'Stage two must occur after the stage-one late fee.']);
        }
        if ($plan !== null) {
            return $this->revise($request, $plan, $data);
        }
        $actor = $request->user();
        $firstInvoice = Carbon::parse($data['first_invoice_date']);
        $defaults = BillingDefault::query()->latest('id')->first();

        $plan = DB::transaction(function () use ($data, $actor, $purchase, $down, $firstInvoice, $request): PaymentPlan {
            $primary = $data['primary_mode'] === 'existing'
                ? Client::findOrFail($data['primary_client_id'])
                : $this->createClient($data, 'primary', $actor->id);
            $coClient = match ($data['co_mode']) {
                'existing' => Client::findOrFail($data['co_client_id']),
                'new' => $this->createClient($data, 'co', $actor->id),
                default => null,
            };
            $planPayment = Money::toCents($data['plan_payment']);
            $serviceFee = Money::toCents($data['service_fee']);
            $firstPaymentAmount = $down;
            $documentationFee = Money::toCents($data['documentation_fee']);

            $plan = PaymentPlan::query()->create([
                'plan_number' => trim($data['plan_number']),
                'apn' => trim($data['plan_number']),
                'title' => trim($data['property_title']),
                'asset_description' => $data['property_description'] ?? null,
                'property_county' => $data['property_county'] ?? null,
                'original_purchase_balance' => 1,
                'customary_monthly_payment' => $planPayment,
                'monthly_service_fee' => $serviceFee,
                'monthly_due_day' => $firstInvoice->day,
                'first_payment_amount' => $firstPaymentAmount,
                'first_payment_invoice_email_on_activation' => (bool) ($data['email_first_payment_invoice'] ?? false),
                'first_payment_invoice_on_activation' => (bool) ($data['create_first_payment_invoice'] ?? false),
                'first_due_date' => $data['first_payment_due_date'] ?? null,
                'plan_start_date' => $data['contract_start_date'],
                'grace_period_days' => (int) $data['grace_days'],
                'first_scheduled_invoice_date' => $firstInvoice,
                'status' => 'draft',
                'contract_down_payment' => $down,
                'hoa_fee' => filled($data['hoa_fee'] ?? null) ? Money::toCents($data['hoa_fee']) : 0,
                'hoa_term' => $data['hoa_term'] ?? null,
                'govdeals' => (bool) ($data['govdeals'] ?? false),
                'created_by_user_id' => $actor->id,
                'updated_by_user_id' => $actor->id,
            ]);

            $this->memberships->add($plan, $primary, $actor, 'primary', $data['contract_start_date'], contactRiskAcknowledgmentMethod: 'admin_contract_setup');
            if ($coClient) {
                $this->memberships->add($plan, $coClient, $actor, 'co_client', $data['contract_start_date'], contactRiskAcknowledgmentMethod: 'admin_contract_setup');
            }

            PaymentPlanBillingTerm::query()->create($this->billingTerms($plan, $firstInvoice, $planPayment, $serviceFee, $data, $actor->id));
            $this->opening->open($plan, $actor, $purchase, $documentationFee, 0, $data['contract_start_date']);
            $clientName = $primary->organization_name ?: trim($primary->first_name.' '.$primary->last_name);
            AdminNotice::query()->create([
                'type' => 'draft_contract_setup',
                'client_id' => $primary->id,
                'payment_plan_id' => $plan->id,
                'title' => 'Draft plan awaiting activation',
                'message' => ($data['primary_mode'] === 'new' ? 'New client ' : 'Contract setup for ').$clientName.' has draft plan '.$plan->plan_number.' awaiting activation.',
            ]);

            AuditLog::query()->create([
                'actor_type' => 'administrator', 'actor_user_id' => $actor->id,
                'event' => 'contract_setup.created', 'auditable_type' => PaymentPlan::class, 'auditable_id' => $plan->id,
                'before_values' => null, 'after_values' => ['status' => 'draft', 'first_invoice_date' => $firstInvoice->toDateString()],
                'ip_address' => $request->ip(), 'user_agent' => str($request->userAgent())->limit(500),
            ]);

            return $plan;
        }, 3);

        $plan->load('memberships.client');
        $primary = $plan->memberships->firstWhere('role', 'primary')->client;
        $coClient = $plan->memberships->firstWhere('role', 'co_client')?->client;
        $values = $this->placeholders(
            $data, $primary, $coClient, $purchase, $down, Money::toCents($data['documentation_fee']),
            Money::toCents($data['plan_payment']), Money::toCents($data['service_fee']), $firstInvoice,
        );
        try {
            $this->documents->generate($request->file('contract_templates'), $values, $plan, $primary, $actor);
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->route('admin.plans.show', $plan)->with(
                'warning',
                'The draft plan was saved, but its contract documents could not be generated. Use Revise setup & regenerate contracts to try again.',
            );
        }

        return redirect()->route('admin.plans.show', $plan)->with('success', 'Contract setup created. Download the contracts, then activate the plan when ready.');
    }


    public function edit(Request $request, PaymentPlan $plan): View
    {
        $this->assertEditable($plan);
        $plan->load(['memberships.client', 'currentBillingTerms']);
        $members = $plan->memberships->whereNull('effective_to');
        if ($members->where('role', 'co_client')->count() > 1) {
            throw ValidationException::withMessages(['co_client_id' => 'These templates support one co-client. This plan has multiple co-clients; use a contract prepared for all clients.']);
        }
        $terms = $plan->currentBillingTerms;
        abort_unless($terms && $members->firstWhere('role', 'primary'), 422);
        $money = fn ($value) => number_format(($value ?? 0) / 100, 2, '.', '');
        $co = $members->firstWhere('role', 'co_client');
        $prefill = [
            'primary_mode' => 'existing', 'primary_client_id' => $members->firstWhere('role', 'primary')->client_id,
            'co_mode' => $co ? 'existing' : 'none', 'co_client_id' => $co?->client_id,
            'co_effective_from' => $co?->effective_from?->toDateString() ?? today()->toDateString(),
            'co_receives_invoices' => $co?->receives_invoices ?? true,
            'plan_number' => $plan->plan_number, 'property_title' => $plan->title,
            'property_description' => $plan->asset_description, 'property_county' => $plan->property_county,
            'purchase_price' => $money($plan->purchase_price),
            'down_payment' => $money($plan->contract_down_payment ?? $plan->first_payment_amount),
            'documentation_fee' => $money($plan->documentation_fee_standard - $plan->documentation_fee_waived),
            'plan_payment' => $money($terms->scheduled_payment_amount), 'service_fee' => $money($terms->monthly_service_fee),
            'hoa_fee' => $money($plan->hoa_fee), 'hoa_term' => $plan->hoa_term, 'govdeals' => $plan->govdeals,
            'contract_start_date' => $plan->plan_start_date?->toDateString(),
            'first_invoice_date' => $plan->first_scheduled_invoice_date?->toDateString(),
            'first_payment_due_date' => $plan->first_due_date?->toDateString(),
            'create_first_payment_invoice' => $plan->first_payment_invoice_on_activation,
            'email_first_payment_invoice' => $plan->first_payment_invoice_email_on_activation,
            'effective_from' => $plan->status === 'draft' ? $plan->plan_start_date?->toDateString() : today()->toDateString(),
        ];
        foreach (['due_days_after_issue', 'grace_days', 'stage_two_enabled', 'stage_two_days_late', 'default_eligibility_days'] as $key) {
            $prefill[$key] = $terms->$key;
        }
        foreach (['stage_one', 'stage_two'] as $stage) {
            $type = $terms->{$stage.'_fee_type'}?->value ?? 'fixed';
            $prefill[$stage.'_fee_type'] = $type;
            $prefill[$stage.'_fee_value'] = $type === 'fixed' ? $money($terms->{$stage.'_fixed_amount'}) : $terms->{$stage.'_percentage_rate'};
            $prefill[$stage.'_minimum_amount'] = $money($terms->{$stage.'_minimum_amount'});
        }
        return view('admin.contract-setups.create', [
            'plan' => $plan, 'prefill' => $prefill, 'defaults' => $terms,
            'selectedClient' => $prefill['primary_client_id'], 'counties' => $this->countySuggestions(),
            'clients' => Client::query()->whereNull('archived_at')->orWhereIn('id', $members->pluck('client_id'))->orderBy('last_name')->get(),
        ]);
    }

    public function addClientForm(PaymentPlan $plan): View
    {
        $this->assertEditable($plan);
        return view('admin.contract-setups.add-client', [
            'plan' => $plan,
            'clients' => Client::query()->whereNull('archived_at')->whereDoesntHave('memberships', fn ($q) => $q->where('payment_plan_id', $plan->id)->whereNull('effective_to'))->orderBy('last_name')->get(),
        ]);
    }

    public function addClient(Request $request, PaymentPlan $plan): RedirectResponse
    {
        $rules = array_filter($this->rules(), fn ($key) => str_starts_with($key, 'co_') || $key === 'invite_co_client', ARRAY_FILTER_USE_KEY);
        $rules['co_mode'] = ['required', Rule::in(['existing', 'new'])];
        $rules['co_effective_from'] = ['required', 'date'];
        $data = $request->validate($rules);
        if ($data['co_mode'] === 'new') $this->validateNewClient($data, 'co');
        $client = DB::transaction(function () use ($request, $plan, $data) {
            $plan = PaymentPlan::query()->lockForUpdate()->findOrFail($plan->id);
            $this->assertEditable($plan);
            $client = $this->resolveCoClient($data, $request->user()->id);
            $this->memberships->add($plan, $client, $request->user(), 'co_client', $data['co_effective_from'], (bool) ($data['co_receives_invoices'] ?? false));
            AuditLog::query()->create([
                'actor_type' => 'administrator', 'actor_user_id' => $request->user()->id,
                'event' => 'payment_plan.co_client_added', 'auditable_type' => PaymentPlan::class, 'auditable_id' => $plan->id,
                'after_values' => ['client_id' => $client->id, 'effective_from' => $data['co_effective_from'], 'receives_invoices' => (bool) ($data['co_receives_invoices'] ?? false)],
            ]);
            return $client;
        });
        $redirect = $request->input('next') === 'contracts'
            ? redirect()->route('admin.contract-setups.edit', $plan)
            : redirect()->route('admin.plans.show', $plan);
        return $this->inviteCoClient($request, $client, $redirect->with('success', 'Co-client added. Existing contracts have not been changed.'));
    }


    public function removeClient(Request $request, PaymentPlan $plan, int $membership): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        DB::transaction(function () use ($request, $plan, $membership, $data): void {
            $plan = PaymentPlan::query()->lockForUpdate()->findOrFail($plan->id);
            $this->assertEditable($plan);
            $member = $plan->memberships()->lockForUpdate()->findOrFail($membership);
            abort_unless($member->role === 'co_client', 422, 'Only co-clients can be removed.');
            $this->memberships->end($member, $request->user(), today(), $data['reason']);
            $hidden = \App\Models\SharedDocument::query()
                ->where('client_id', $member->client_id)->where('payment_plan_id', $plan->id)
                ->where('visible_to_client', true)->update(['visible_to_client' => false]);
            AuditLog::query()->create([
                'actor_type' => 'administrator', 'actor_user_id' => $request->user()->id,
                'event' => 'payment_plan.co_client_removed', 'auditable_type' => PaymentPlan::class, 'auditable_id' => $plan->id,
                'before_values' => ['membership_id' => $member->id, 'client_id' => $member->client_id, 'effective_to' => null],
                'after_values' => ['effective_to' => today()->toDateString(), 'reason' => $data['reason'], 'documents_hidden' => $hidden],
            ]);
        });
        return redirect()->route($request->input('next') === 'contracts' ? 'admin.contract-setups.edit' : 'admin.plans.show', $plan)
            ->with('success', 'Co-client removed and their shared documents for this plan hidden. Existing contracts are unchanged; use Revise setup & regenerate contracts to update them.');
    }

    private function assertEditable(PaymentPlan $plan): void
    {
        abort_unless(in_array($plan->status, ['draft', 'active', 'paused'], true), 422);
    }

    private function resolveCoClient(array $data, int $actorId): ?Client
    {
        $client = match ($data['co_mode']) {
            'existing' => Client::query()->whereNull('archived_at')->findOrFail($data['co_client_id']),
            'new' => $this->createClient($data, 'co', $actorId),
            default => null,
        };
        if (($data['invite_co_client'] ?? false) && (! $client || blank($client->email))) {
            throw ValidationException::withMessages(['invite_co_client' => 'A co-client email is required to send a portal invitation.']);
        }
        return $client;
    }

    private function inviteCoClient(Request $request, ?Client $client, RedirectResponse $redirect): RedirectResponse
    {
        if ($request->boolean('invite_co_client') && $client && ! $client->portalAccount?->enabled) {
            try {
                app(PortalInvitationService::class)->invite($client, $request->user());
            } catch (\Throwable $exception) {
                report($exception);
                $redirect->with('warning', 'Changes saved, but the portal invitation could not be sent. Send it again from the client page.');
            }
        }
        return $redirect;
    }

    private function revise(Request $request, PaymentPlan $plan, array $data): RedirectResponse
    {
        $generated = [];
        $co = null;
        try {
            DB::transaction(function () use ($request, $plan, $data, &$generated, &$co): void {
                $plan = PaymentPlan::query()->lockForUpdate()->findOrFail($plan->id);
                $this->assertEditable($plan);
                $members = $plan->memberships()->whereNull('effective_to')->get();
                $primary = $members->firstWhere('role', 'primary');
                $existingCo = $members->where('role', 'co_client');
                if (! $primary || $data['primary_mode'] !== 'existing' || (int) $data['primary_client_id'] !== $primary->client_id
                    || $existingCo->count() > 1 || ($existingCo->isNotEmpty() && ($data['co_mode'] !== 'existing' || (int) ($data['co_client_id'] ?? 0) !== $existingCo->first()->client_id))) {
                    throw ValidationException::withMessages(['co_client_id' => 'Keep the existing plan clients selected. This action can add a co-client, but cannot remove or replace clients.']);
                }
                $terms = $plan->currentBillingTerms()->firstOrFail();
                $before = ['plan' => $plan->getAttributes(), 'billing_terms' => $terms->getAttributes()];
                $effective = $plan->status === 'draft' ? $data['contract_start_date'] : $data['effective_from'];
                if ($plan->status !== 'draft' && Carbon::parse($effective)->lt($terms->effective_from)) {
                    throw ValidationException::withMessages(['effective_from' => 'The amendment date cannot precede the current billing terms.']);
                }
                if ($plan->status !== 'draft' && $data['first_invoice_date'] !== $plan->first_scheduled_invoice_date?->toDateString()) {
                    throw ValidationException::withMessages(['first_invoice_date' => 'The original first recurring invoice date cannot change after activation.']);
                }
                $co = $existingCo->first()?->client ?? $this->resolveCoClient($data, $request->user()->id);
                if (($data['invite_co_client'] ?? false) && (! $co || blank($co->email))) {
                    throw ValidationException::withMessages(['invite_co_client' => 'A co-client email is required to send a portal invitation.']);
                }
                if ($co && $existingCo->isEmpty()) {
                    $this->memberships->add($plan, $co, $request->user(), 'co_client', $data['co_effective_from'] ?? $effective, (bool) ($data['co_receives_invoices'] ?? false));
                }
                $purchase = Money::toCents($data['purchase_price']);
                $doc = Money::toCents($data['documentation_fee']);
                $down = Money::toCents($data['down_payment']);
                $payment = Money::toCents($data['plan_payment']);
                $fee = Money::toCents($data['service_fee']);
                $first = Carbon::parse($data['first_invoice_date']);
                app(ContractAmountAmendmentService::class)->amend($plan, $request->user(), $purchase, $doc + (int) $plan->documentation_fee_waived, (int) $plan->documentation_fee_waived, $effective, $data['amendment_reason'], $plan->documentation_fee_waiver_reason);
                $plan->refresh();
                $plan->fill([
                    'plan_number' => trim($data['plan_number']), 'apn' => trim($data['plan_number']),
                    'title' => $data['property_title'], 'asset_description' => $data['property_description'] ?? null,
                    'property_county' => $data['property_county'], 'contract_down_payment' => $down,
                    'hoa_fee' => Money::toCents($data['hoa_fee'] ?? '0'), 'hoa_term' => $data['hoa_term'] ?? null,
                    'govdeals' => (bool) ($data['govdeals'] ?? false), 'plan_start_date' => $data['contract_start_date'],
                    'customary_monthly_payment' => $payment, 'monthly_service_fee' => $fee,
                    'grace_period_days' => $data['grace_days'], 'updated_by_user_id' => $request->user()->id,
                ]);
                if ($plan->status === 'draft') {
                    $plan->fill([
                        'first_scheduled_invoice_date' => $first, 'monthly_due_day' => $first->day,
                        'first_payment_amount' => $down, 'first_due_date' => $data['first_payment_due_date'] ?? null,
                        'first_payment_invoice_on_activation' => (bool) ($data['create_first_payment_invoice'] ?? false),
                        'first_payment_invoice_email_on_activation' => (bool) ($data['email_first_payment_invoice'] ?? false),
                    ]);
                }
                $plan->save();
                $newTerms = $this->billingTerms($plan, $first, $payment, $fee, $data, $request->user()->id);
                $newTerms['invoice_day'] = $plan->monthly_due_day;
                $newTerms['stage_one_enabled'] = $terms->stage_one_enabled;
                unset($newTerms['effective_from'], $newTerms['created_by_user_id']);
                $candidate = clone $terms;
                $candidate->fill($newTerms);
                if ($plan->status === 'draft') {
                    $terms->update($newTerms + ['effective_from' => $effective]);
                } elseif ($candidate->isDirty()) {
                    $terms->update(['effective_to' => Carbon::parse($effective)->subDay()]);
                    PaymentPlanBillingTerm::query()->create($newTerms + ['effective_from' => $effective, 'reason' => $data['amendment_reason'], 'created_by_user_id' => $request->user()->id]);
                }
                $values = $this->placeholders($data, $primary->client, $co, $purchase, $down, $doc, $payment, $fee, $first);
                $values['PInvoiceDay'] = (string) $plan->monthly_due_day;
                $oldDocuments = $plan->contractDocuments()->whereNull('deleted_at')->get();
                AuditLog::query()->create([
                    'actor_type' => 'administrator', 'actor_user_id' => $request->user()->id,
                    'event' => 'contract_setup.revised', 'auditable_type' => PaymentPlan::class, 'auditable_id' => $plan->id,
                    'before_values' => $before, 'after_values' => ['plan' => $plan->getAttributes(), 'billing_terms' => $plan->currentBillingTerms()->first()?->getAttributes(), 'co_client_id' => $co?->id, 'reason' => $data['amendment_reason']],
                ]);
                $generated = $this->documents->generate($request->file('contract_templates'), $values, $plan, $primary->client, $request->user());
                foreach ($oldDocuments as $document) {
                    if (! str_starts_with($document->name, 'Superseded - ')) $document->update(['name' => 'Superseded - '.$document->name]);
                }
            });
        } catch (\Throwable $exception) {
            foreach ($generated as $document) Storage::disk($document->disk)->delete($document->path);
            if ($exception instanceof ValidationException || $exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) throw $exception;
            report($exception);
            return back()->withInput()->withErrors(['contract_templates' => 'Revision was not saved. Check the uploaded Word templates and try again.']);
        }
        return $this->inviteCoClient($request, $co, redirect()->route('admin.plans.show', $plan)->with('success', 'Plan updated and contracts regenerated. Previous files are marked superseded.'));
    }

    public function activate(Request $request, PaymentPlan $plan): RedirectResponse
    {
        abort_unless($plan->status === 'draft', 422);
        if ($plan->first_scheduled_invoice_date?->lt(today()) && ! $request->boolean('acknowledge_skipped_recurring_invoice')) {
            return back()->withErrors(['activation' => 'The first recurring invoice date has passed. Confirm that missed recurring invoices will not be created automatically.']);
        }
        $minimumDue = today()->addDays(3);
        if ($plan->first_due_date?->lt($minimumDue)) {
            return back()->withErrors(['activation' => 'Choose a down/first payment invoice due date at least three days after activation, or clear it to use the standard payment window.']);
        }

        $result = DB::transaction(function () use ($request, $plan): array {
            $plan->update(['status' => 'active', 'activated_at' => now(), 'updated_by_user_id' => $request->user()->id]);
            $invoice = null;
            $documentationFee = max(0, (int) $plan->documentation_fee_standard - (int) $plan->documentation_fee_waived);
            if ($plan->first_payment_invoice_on_activation && ((int) $plan->first_payment_amount > 0 || $documentationFee > 0)) {
                $issueDate = Carbon::today();
                $dueDays = max(3, (int) ($plan->currentBillingTerms()->value('due_days_after_issue') ?? 5));
                $dueDate = $plan->first_due_date?->copy() ?? $issueDate->copy()->addDays($dueDays);
                $plan->update(['first_due_date' => $dueDate]);
                $invoice = $this->firstPaymentInvoices->issue(
                    $plan->fresh(), $request->user(), (int) $plan->first_payment_amount,
                    $issueDate, $dueDate,
                    $documentationFee,
                );
            }
            AdminNotice::query()->where('type', 'draft_contract_setup')->where('payment_plan_id', $plan->id)
                ->whereNull('dismissed_at')->update(['dismissed_at' => now(), 'dismissed_by_user_id' => $request->user()->id]);
            AuditLog::query()->create([
                'actor_type' => 'administrator', 'actor_user_id' => $request->user()->id,
                'event' => 'contract_setup.activated', 'auditable_type' => PaymentPlan::class, 'auditable_id' => $plan->id,
                'before_values' => ['status' => 'draft'], 'after_values' => ['status' => 'active'],
                'ip_address' => $request->ip(), 'user_agent' => str($request->userAgent())->limit(500),
            ]);

            return ['invoice' => $invoice];
        });

        $emailedTo = null;
        $emailFailed = false;
        if ($result['invoice'] && $plan->first_payment_invoice_email_on_activation) {
            try {
                $emailedTo = $this->invoiceEmails->send($result['invoice'], $request->user(), 'inline')->recipient_email;
            } catch (\Throwable $exception) {
                report($exception);
                $emailFailed = true;
            }
        }
        $message = 'Plan activated.';
        if ($emailedTo) {
            $message .= ' First-payment invoice created and emailed to '.$emailedTo.'.';
        } elseif ($result['invoice']) {
            $message .= ' First-payment invoice created.';
        }
        $message .= ' Automatic invoicing will begin on its first invoice date.';

        $redirect = back()->with('success', $message);
        if ($emailFailed) {
            $redirect->with('warning', 'The plan and invoice were saved, but the invoice email could not be delivered. Review the failed delivery and resend it.');
        }

        return $redirect;
    }

    public function deleteDraft(Request $request, PaymentPlan $plan): RedirectResponse
    {
        $result = DB::transaction(function () use ($request, $plan): array {
            $lockedPlan = PaymentPlan::query()->lockForUpdate()->findOrFail($plan->id);
            abort_unless($lockedPlan->status === 'draft', 422);

            $documents = ContractDocument::query()->where('payment_plan_id', $lockedPlan->id)
                ->whereNull('deleted_at')->get(['disk', 'path'])->map->only(['disk', 'path'])->all();
            $suffix = '-DEL-'.$lockedPlan->id;
            $releasedNumber = $lockedPlan->plan_number;
            $deletedNumber = str($lockedPlan->plan_number)->limit(40 - strlen($suffix), '')->append($suffix)->toString();

            ContractDocument::query()->where('payment_plan_id', $lockedPlan->id)
                ->whereNull('deleted_at')->update(['deleted_at' => now()]);
            $noticeDismissed = AdminNotice::query()->where('type', 'draft_contract_setup')->where('payment_plan_id', $lockedPlan->id)
                ->whereNull('dismissed_at')->update(['dismissed_at' => now(), 'dismissed_by_user_id' => $request->user()->id]);
            $lockedPlan->update([
                'plan_number' => $deletedNumber,
                'status' => 'deleted',
                'updated_by_user_id' => $request->user()->id,
            ]);
            AuditLog::query()->create([
                'actor_type' => 'administrator', 'actor_user_id' => $request->user()->id,
                'event' => 'contract_setup.deleted', 'auditable_type' => PaymentPlan::class, 'auditable_id' => $lockedPlan->id,
                'before_values' => ['status' => 'draft', 'plan_number' => $plan->plan_number],
                'after_values' => ['status' => 'deleted', 'plan_number' => $deletedNumber],
                'ip_address' => $request->ip(), 'user_agent' => str($request->userAgent())->limit(500),
            ]);
            $lockedPlan->delete();

            return compact('documents', 'releasedNumber', 'noticeDismissed');
        }, 3);

        $filesDeleted = 0;
        $filesFailed = 0;
        foreach ($result['documents'] as $document) {
            $disk = Storage::disk($document['disk']);
            if (! $disk->exists($document['path']) || $disk->delete($document['path'])) {
                $filesDeleted++;
            } else {
                $filesFailed++;
            }
        }

        $redirect = redirect()->route('admin.plans.index')
            ->with('success', 'Draft plan deleted.')
            ->with('success_details', [
                'Plan hidden and parcel/plan number '.$result['releasedNumber'].' released for reuse.',
                $filesDeleted.' generated contract '.str('file')->plural($filesDeleted).' removed.',
                $result['noticeDismissed'] ? 'Draft admin notice dismissed.' : 'No open draft admin notice remained.',
                'Client, financial, and audit records preserved.',
            ]);

        if ($filesFailed > 0) {
            $redirect->with('warning', $filesFailed.' generated contract file could not be removed and requires cleanup.');
        }

        return $redirect;
    }

    public function download(ContractDocument $document): StreamedResponse
    {
        abort_if($document->deleted_at || $document->expires_at->isPast() || ! Storage::disk($document->disk)->exists($document->path), 404);
        $document->update(['downloaded_at' => now()]);

        return Storage::disk($document->disk)->download($document->path, $document->name);
    }

    public function destroy(ContractDocument $document): RedirectResponse
    {
        $this->documents->delete($document);

        return back()->with('success', 'Generated contract deleted.');
    }

    private function validateNewClient(array $data, string $prefix): void
    {
        if (($data[$prefix.'_client_type'] ?? 'individual') === 'organization') {
            validator($data, [$prefix.'_organization_name' => ['required', 'string', 'max:180']])->validate();
        } else {
            validator($data, [
                $prefix.'_first_name' => ['required', 'string', 'max:100'],
                $prefix.'_last_name' => ['required', 'string', 'max:100'],
            ])->validate();
        }
    }

    private function createClient(array $data, string $prefix, int $actorId): Client
    {
        $get = fn (string $field) => $data[$prefix.'_'.$field] ?? null;

        return Client::query()->create([
            'client_type' => $get('client_type'), 'organization_name' => $get('organization_name'),
            'first_name' => $get('first_name'), 'last_name' => $get('last_name'), 'email' => $get('email'),
            'primary_phone' => $get('phone'), 'address_line_1' => $get('address_line_1'), 'address_line_2' => $get('address_line_2'),
            'city' => $get('city'), 'state_region' => $get('state_region'), 'postal_code' => $get('postal_code'),
            'country_code' => 'US', 'status' => 'active', 'created_by_user_id' => $actorId, 'updated_by_user_id' => $actorId,
        ]);
    }

    private function billingTerms(PaymentPlan $plan, Carbon $first, int $payment, int $fee, array $data, int $actorId): array
    {
        $stageOneType = $data['stage_one_fee_type'];
        $stageTwo = (bool) ($data['stage_two_enabled'] ?? false);

        return [
            'payment_plan_id' => $plan->id, 'frequency' => 'monthly', 'invoice_day' => $first->day,
            'due_days_after_issue' => (int) $data['due_days_after_issue'], 'grace_days' => (int) $data['grace_days'],
            'scheduled_payment_amount' => $payment, 'monthly_service_fee' => $fee,
            'stage_one_enabled' => true, 'stage_one_fee_type' => $stageOneType,
            'stage_one_fixed_amount' => $stageOneType === 'fixed' ? Money::toCents((string) $data['stage_one_fee_value']) : null,
            'stage_one_percentage_rate' => $stageOneType === 'percentage' ? $data['stage_one_fee_value'] : null,
            'stage_one_minimum_amount' => $stageOneType === 'percentage' ? Money::toCents($data['stage_one_minimum_amount']) : 0,
            'stage_one_days_late' => (int) $data['grace_days'] + 1,
            'stage_two_enabled' => $stageTwo, 'stage_two_fee_type' => $stageTwo ? $data['stage_two_fee_type'] : null,
            'stage_two_fixed_amount' => $stageTwo && $data['stage_two_fee_type'] === 'fixed' ? Money::toCents((string) $data['stage_two_fee_value']) : null,
            'stage_two_percentage_rate' => $stageTwo && $data['stage_two_fee_type'] === 'percentage' ? $data['stage_two_fee_value'] : null, 'stage_two_minimum_amount' => $stageTwo && $data['stage_two_fee_type'] === 'percentage' ? Money::toCents($data['stage_two_minimum_amount']) : 0, 'stage_two_days_late' => $stageTwo ? $data['stage_two_days_late'] : null, 'default_eligibility_days' => $data['default_eligibility_days'],
            'effective_from' => Carbon::parse($data['contract_start_date']), 'created_by_user_id' => $actorId,
        ];
    }

    private function placeholders(array $data, Client $primary, ?Client $co, int $price, int $down, int $doc, int $payment, int $fee, Carbon $first): array
    {
        $name = fn (?Client $client) => $client ? ($client->organization_name ?: trim($client->first_name.' '.$client->last_name)) : '';
        $address = fn (?Client $client) => $client ? implode(', ', array_filter([$client->address_line_1, $client->address_line_2, trim($client->city.' '.$client->state_region.' '.$client->postal_code)])) : '';
        $money = fn (int $cents) => number_format($cents / 100, 2, '.', ',');
        $words = function (int $cents): string {
            if (! class_exists(\NumberFormatter::class)) {
                return (string) intdiv($cents, 100);
            }

            return ucwords((new \NumberFormatter('en', \NumberFormatter::SPELLOUT))->format(intdiv($cents, 100)));
        };
        $principal = $price - $down;
        $total = $price + $doc;
        $initial = $down + $doc;
        $terms = (int) ceil($principal / $payment);
        $start = Carbon::parse($data['contract_start_date']);

        return [
            'C1Name' => $name($primary), 'C1Address' => $address($primary), 'C1Email' => $primary->email ?? '', 'C1Phone' => $primary->primary_phone ?? '',
            'C2Name' => $name($co), 'C2Address' => $address($co), 'C2Email' => $co?->email ?? '', 'C2Phone' => $co?->primary_phone ?? '',
            'Papn' => $data['plan_number'], 'PropDesc' => $data['property_description'] ?? $data['property_title'], 'Pcounty' => $data['property_county'] ?? '',
            'PPrice' => $money($price), 'PPriceWords' => $words($price), 'PDown' => $money($down), 'PMonthly' => $money($payment + $fee),
            'PDoc' => $money($doc), 'PInitial' => $money($initial), 'PInitialWords' => $words($initial), 'PStartDate' => $first->format('m/d/y'),
            'Govd' => ($data['govdeals'] ?? false) ? ' through the Govdeals website' : '', 'PPrincipal' => $money($principal),
            'PPrincipalWords' => $words($principal), 'PTotal' => $money($total), 'PTotalWords' => $words($total),
            'PPaymentFinanced' => $money($payment), 'PTerms' => (string) $terms, 'PTermsWords' => $words($terms * 100),
            'PHoaFee' => $money(filled($data['hoa_fee'] ?? null) ? Money::toCents($data['hoa_fee']) : 0), 'PHoaTerm' => $data['hoa_term'] ?? '',
            'PPlanPayment' => $money($payment), 'PServiceFee' => $money($fee), 'PTotalMonthly' => $money($payment + $fee),
            'PContractStartDate' => $start->format('m/d/y'), 'PFirstInvoiceDate' => $first->format('m/d/y'), 'PInvoiceDay' => (string) $first->day,
        ];
    }
}
