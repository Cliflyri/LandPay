@extends('layouts.admin')
@section('title', 'Amend '.$plan->plan_number.' | LandPay')
@section('body_class', 'admin-page')
@section('content')
@php
$primaryMembership = $plan->memberships->firstWhere('role', 'primary');
$primaryClient = $primaryMembership?->client;
$primaryClientName = $primaryClient?->organization_name ?: trim(($primaryClient?->first_name ?? '').' '.($primaryClient?->last_name ?? ''));
$stageOneType = old('stage_one_fee_type', $terms->stage_one_fee_type?->value ?? 'fixed');
$stageTwoType = old('stage_two_fee_type', $terms->stage_two_fee_type?->value ?? 'fixed');
$stageOneValue = $stageOneType === 'percentage' ? $terms->stage_one_percentage_rate : number_format(($terms->stage_one_fixed_amount ?? 0) / 100, 2, '.', '');
$stageTwoValue = $stageTwoType === 'percentage' ? $terms->stage_two_percentage_rate : number_format(($terms->stage_two_fixed_amount ?? 0) / 100, 2, '.', '');
@endphp
<section class="admin-section"><div class="container-fluid dashboard-container">
<div class="admin-heading"><span class="eyebrow eyebrow-dark">Plan amendment</span><h1>Edit {{ $plan->plan_number }}</h1><p><strong>{{ $primaryClientName ?: 'No primary client' }}</strong> <span aria-hidden="true">&middot;</span> Plan # {{ $plan->plan_number }}</p><p>Changes create a new effective-dated billing record. Existing invoices and ledger entries remain unchanged.</p></div>
<form class="admin-form-card" method="POST" action="{{ route('admin.plans.update', $plan) }}">@csrf @method('PUT')
@if($errors->any())<div class="alert alert-danger"><strong>The amendment was not saved.</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<div class="plan-form-section"><div class="plan-form-number">1</div><div class="plan-form-body"><h2>Plan and property</h2><div class="row g-3">
<div class="col-md-4"><label class="form-label">APN / Plan #</label><input class="form-control" name="plan_number" value="{{ old('plan_number',$plan->plan_number) }}" required></div>
<div class="col-md-6"><label class="form-label">Property description</label><input class="form-control" name="title" value="{{ old('title',$plan->title) }}" required></div>
@if($plan->status === 'draft')
<div class="col-md-2"><label class="form-label">Status</label><input type="hidden" name="status" value="draft"><div class="form-control bg-light">Draft</div><div class="form-text">Use Activate Plan after review.</div></div>
@else
<div class="col-md-2"><label class="form-label" for="plan-status">Status</label><select class="form-select" id="plan-status" name="status">@foreach(['draft'=>'Draft','active'=>'Active','paused'=>'Paused','terminated'=>'Terminated','closed'=>'Closed'] as $value=>$label)<option value="{{ $value }}" @selected(old('status',$plan->status)===$value)>{{ $label }}</option>@endforeach</select></div>
@endif
<div class="col-12"><label class="form-label">Additional property details</label><textarea class="form-control" name="asset_description" rows="2">{{ old('asset_description',$plan->asset_description) }}</textarea></div>
<div class="col-12" id="email-automation"><div class="form-check form-switch"><input type="hidden" name="scheduled_invoice_email_enabled" value="0"><input class="form-check-input" type="checkbox" id="scheduled_invoice_email_enabled" name="scheduled_invoice_email_enabled" value="1" @checked(old('scheduled_invoice_email_enabled',$plan->scheduled_invoice_email_enabled))><label class="form-check-label" for="scheduled_invoice_email_enabled">Automatically email scheduled invoices (inline)</label></div></div>
<div class="col-12"><div class="form-check form-switch"><input type="hidden" name="automated_reminders_enabled" value="0"><input class="form-check-input" type="checkbox" id="automated_reminders_enabled" name="automated_reminders_enabled" value="1" @checked(old('automated_reminders_enabled',$plan->automated_reminders_enabled))><label class="form-check-label" for="automated_reminders_enabled">Allow automated payment reminders for this plan</label></div></div>
<div class="col-12"><div class="form-check form-switch"><input type="hidden" name="automatic_invoice_email_enabled" value="0"><input class="form-check-input" type="checkbox" id="automatic_invoice_email_enabled" name="automatic_invoice_email_enabled" value="1" @checked(old('automatic_invoice_email_enabled',$plan->automatic_invoice_email_enabled))><label class="form-check-label" for="automatic_invoice_email_enabled">Automatically email manually created invoices (inline)</label></div></div>

<div
    id="accelerated-testing-mode"
    class="col-12"
    style="scroll-margin-top:8rem;"
>
    <div class="form-check form-switch">
        <input type="hidden" name="accelerated_testing_mode" value="0">

        <input
            class="form-check-input"
            type="checkbox"
            id="accelerated_testing_mode"
            name="accelerated_testing_mode"
            value="1"
            @checked(old('accelerated_testing_mode', $plan->accelerated_testing_mode))
        >

        <label class="form-check-label" for="accelerated_testing_mode">
            Accelerated testing mode
            <strong>(daily billing cycle &ndash; testing only)</strong>
        </label>

        <div class="form-text">
            Automatically treats this plan as due every day instead of every month.
        </div>
    </div>
</div>

<div class="col-12"><label class="form-label">Internal notes</label><textarea class="form-control" name="notes" rows="2">{{ old('notes',$plan->notes) }}</textarea></div>
</div></div></div>

<div class="plan-form-section"><div class="plan-form-number">2</div><div class="plan-form-body"><h2>Contract amounts</h2><p class="text-muted">Administrator-only financial terms. Changes create an immutable ledger adjustment and do not rewrite prior transactions.</p><div class="row g-3">
<div class="col-md-4"><label class="form-label">Purchase price</label><div class="input-group"><span class="input-group-text">$</span><input class="form-control" name="purchase_price" value="{{ old('purchase_price',number_format($plan->purchase_price/100,2,'.','')) }}" required></div></div>
<div class="col-md-4"><label class="form-label">Documentation fee</label><div class="input-group"><span class="input-group-text">$</span><input class="form-control" name="documentation_fee_standard" value="{{ old('documentation_fee_standard',number_format($plan->documentation_fee_standard/100,2,'.','')) }}" required></div></div>
<div class="col-md-4"><label class="form-label">Fee waived</label><div class="input-group"><span class="input-group-text">$</span><input class="form-control" name="documentation_fee_waived" value="{{ old('documentation_fee_waived',number_format($plan->documentation_fee_waived/100,2,'.','')) }}" required></div></div>
<div class="col-12"><label class="form-label">Waiver reason <span class="text-muted">(required when a fee is waived)</span></label><input class="form-control" name="documentation_fee_waiver_reason" maxlength="500" value="{{ old('documentation_fee_waiver_reason',$plan->documentation_fee_waiver_reason) }}"></div>
<div class="col-md-4"><label class="form-label">Amount previously paid in</label><div class="input-group"><span class="input-group-text">$</span><input class="form-control" name="previous_principal_paid" value="{{ old('previous_principal_paid',number_format($previousPaid/100,2,'.','')) }}"></div><div class="form-text">Cannot reduce the remaining contract balance below zero or create customer credit.</div></div>
<div class="col-md-8"><div id="projected-contract-balance-panel" class="calculation-panel calculation-panel-total h-100"><span>Projected contract balance</span><strong id="projected-contract-balance">{{ \App\Support\Money::format($contractBalance) }}</strong><small id="projected-contract-balance-warning" class="d-none">This adjustment would create customer credit and cannot be saved.</small></div></div>
</div></div></div>

<div class="plan-form-section"><div class="plan-form-number">3</div><div class="plan-form-body"><h2>Payment schedule</h2><div class="row g-3">
<div class="col-md-3"><label class="form-label">Contract start date</label><input type="date" class="form-control" name="contract_start_date" value="{{ old('contract_start_date',$plan->plan_start_date?->format('Y-m-d')) }}" required></div>
@if($plan->status === 'draft')
<div class="col-12"><div class="alert alert-warning mb-0"><h3 class="h6 mb-2">Down/first payment invoice on activation</h3>
<div class="form-check"><input class="form-check-input" type="checkbox" value="1" name="create_first_payment_invoice" id="create_first_payment_invoice" @checked(old('create_first_payment_invoice',$plan->first_payment_invoice_on_activation))><label class="form-check-label" for="create_first_payment_invoice"><strong>Create a down/first payment invoice</strong></label></div>
<small class="d-block text-muted ms-4">Lists any down payment and the net documentation fee separately. A $0 down payment creates a documentation-fee-only invoice.</small>
<div class="row g-3 mt-1"><div class="col-md-4"><label class="form-label">Down/first payment</label><div class="input-group"><span class="input-group-text">$</span><input class="form-control" name="first_payment_amount" inputmode="decimal" value="{{ old('first_payment_amount',number_format((int)$plan->first_payment_amount/100,2,'.','')) }}"></div></div>
<div class="col-md-4"><label class="form-label">Invoice due date <span class="text-muted">(optional)</span></label><input type="date" class="form-control" name="first_payment_due_date" value="{{ old('first_payment_due_date',$plan->first_due_date?->format('Y-m-d')) }}"><div class="form-text">Leave blank to use the standard payment window.</div></div>
<div class="col-md-4 d-flex align-items-end"><div class="form-check mb-2"><input class="form-check-input" type="checkbox" value="1" name="email_first_payment_invoice" id="email_first_payment_invoice" @checked(old('email_first_payment_invoice',$plan->first_payment_invoice_email_on_activation))><label class="form-check-label" for="email_first_payment_invoice"><strong>Email invoice on activation</strong></label></div></div></div>
</div></div>
@else
<div class="col-md-3"><label class="form-label">First payment amount</label><div class="input-group"><span class="input-group-text">$</span><input class="form-control" name="first_payment_amount" value="{{ old('first_payment_amount',$plan->first_payment_amount === null ? '' : number_format($plan->first_payment_amount/100,2,'.','')) }}"></div></div>
<div class="col-md-3"><label class="form-label">First payment due date</label><input type="date" class="form-control" name="first_payment_due_date" value="{{ old('first_payment_due_date',$plan->first_due_date?->format('Y-m-d')) }}"></div>
@endif
<div class="col-md-3"><label class="form-label">Monthly payment</label><div class="input-group"><span class="input-group-text">$</span><input class="form-control" name="scheduled_payment_amount" value="{{ old('scheduled_payment_amount',number_format($terms->scheduled_payment_amount/100,2,'.','')) }}" required></div></div>
<div class="col-md-3"><label class="form-label">Monthly service fee</label><div class="input-group"><span class="input-group-text">$</span><input class="form-control" name="monthly_service_fee" value="{{ old('monthly_service_fee',number_format($terms->monthly_service_fee/100,2,'.','')) }}" required></div></div>
<div class="col-md-4"><label class="form-label">Invoice day</label><input type="number" class="form-control" name="invoice_day" min="1" max="31" value="{{ old('invoice_day',$terms->invoice_day) }}" required></div>
<div class="col-md-4"><label class="form-label">Due after issue</label><div class="input-group"><input type="number" class="form-control" name="due_days_after_issue" min="0" max="60" value="{{ old('due_days_after_issue',$terms->due_days_after_issue) }}" required><span class="input-group-text">days</span></div></div>
<div class="col-md-4"><label class="form-label">Grace period</label><div class="input-group"><input type="number" class="form-control" name="grace_days" min="0" max="60" value="{{ old('grace_days',$terms->grace_days) }}" required><span class="input-group-text">days</span></div></div>
</div></div></div>

<div class="plan-form-section"><div class="plan-form-number">4</div><div class="plan-form-body"><h2>Late fees and default</h2><div class="row g-3">
<div class="col-md-4"><label class="form-label">Stage-one calculation</label><select class="form-select" name="stage_one_fee_type"><option value="fixed" @selected($stageOneType==='fixed')>Fixed amount</option><option value="percentage" @selected($stageOneType==='percentage')>Percentage</option></select></div>
<div class="col-md-4"><label class="form-label">Stage-one value</label><input class="form-control" name="stage_one_fee_value" value="{{ old('stage_one_fee_value',$stageOneValue) }}" required></div>
<div class="col-md-4"><label class="form-label">Stage-one minimum</label><input class="form-control" name="stage_one_minimum_amount" value="{{ old('stage_one_minimum_amount',number_format($terms->stage_one_minimum_amount/100,2,'.','')) }}"></div>
<div class="col-12"><div class="form-check"><input type="checkbox" class="form-check-input" name="stage_two_enabled" value="1" id="stage_two_enabled" @checked(old('stage_two_enabled',$terms->stage_two_enabled))><label class="form-check-label" for="stage_two_enabled">Enable stage-two late fee</label></div></div>
<div class="col-md-3"><label class="form-label">Stage-two days late</label><input type="number" class="form-control" name="stage_two_days_late" value="{{ old('stage_two_days_late',$terms->stage_two_days_late) }}"></div>
<div class="col-md-3"><label class="form-label">Stage-two calculation</label><select class="form-select" name="stage_two_fee_type"><option value="fixed" @selected($stageTwoType==='fixed')>Fixed amount</option><option value="percentage" @selected($stageTwoType==='percentage')>Percentage</option></select></div>
<div class="col-md-3"><label class="form-label">Stage-two value</label><input class="form-control" name="stage_two_fee_value" value="{{ old('stage_two_fee_value',$stageTwoValue) }}"></div>
<div class="col-md-3"><label class="form-label">Stage-two minimum</label><input class="form-control" name="stage_two_minimum_amount" value="{{ old('stage_two_minimum_amount',number_format($terms->stage_two_minimum_amount/100,2,'.','')) }}"></div>
<div class="col-md-4"><label class="form-label">Default eligibility</label><div class="input-group"><input type="number" class="form-control" name="default_eligibility_days" min="1" max="730" value="{{ old('default_eligibility_days',$terms->default_eligibility_days) }}" required><span class="input-group-text">days</span></div></div>
</div></div></div>

<div class="plan-form-section plan-form-section-advanced"><div class="plan-form-number">5</div><div class="plan-form-body"><h2>Amendment record</h2><p class="text-muted">The new terms begin on this date. Previously issued invoices are not recalculated.</p><div class="row g-3">
<div class="col-md-4"><label class="form-label">Effective date</label><input type="date" class="form-control" name="effective_from" value="{{ old('effective_from',now()->addDay()->format('Y-m-d')) }}" required></div>
<div class="col-md-8"><label class="form-label">Reason for amendment</label><input class="form-control" name="amendment_reason" value="{{ old('amendment_reason') }}" maxlength="500" required></div>
</div></div></div>
<div class="d-flex gap-2 mt-4"><button class="btn btn-brand btn-lg">Save amendment</button><a class="btn btn-outline-brand btn-lg" href="{{ route('admin.plans.show',$plan) }}">Cancel</a></div>
</form></div></section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{
 const form=document.querySelector('form[action*="/plans/"]');if(!form)return;
 const names=['purchase_price','documentation_fee_standard','documentation_fee_waived','previous_principal_paid'];
 const inputs=Object.fromEntries(names.map(name=>[name,form.elements[name]]));
 const cents=value=>Math.round((Number.parseFloat(String(value).replace(/[$,]/g,''))||0)*100);
 const original={purchase_price:@json($plan->purchase_price),documentation_fee_standard:@json($plan->documentation_fee_standard),documentation_fee_waived:@json($plan->documentation_fee_waived),previous_principal_paid:@json($previousPaid)};
 const current=@json($contractBalance),output=document.getElementById('projected-contract-balance'),panel=document.getElementById('projected-contract-balance-panel'),warning=document.getElementById('projected-contract-balance-warning');
 const currency=value=>new Intl.NumberFormat('en-US',{style:'currency',currency:'USD'}).format(value/100);
 function update(){const projected=current+(cents(inputs.purchase_price.value)-original.purchase_price)+(cents(inputs.documentation_fee_standard.value)-cents(inputs.documentation_fee_waived.value)-(original.documentation_fee_standard-original.documentation_fee_waived))-(cents(inputs.previous_principal_paid.value)-original.previous_principal_paid);output.textContent=currency(projected);const invalid=projected<0;panel.classList.toggle('border-danger',invalid);output.classList.toggle('text-danger',invalid);warning.classList.toggle('d-none',!invalid);}
 names.forEach(name=>inputs[name]?.addEventListener('input',update));update();
 const createFirst=document.getElementById('create_first_payment_invoice'),emailFirst=document.getElementById('email_first_payment_invoice');
 if(createFirst&&emailFirst){const syncFirst=()=>{emailFirst.disabled=!createFirst.checked;if(!createFirst.checked)emailFirst.checked=false;};createFirst.addEventListener('change',syncFirst);syncFirst();}
});
</script>
@endpush
