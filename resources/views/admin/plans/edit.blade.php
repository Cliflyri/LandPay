@extends('layouts.app')
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
<section class="admin-section"><div class="container site-container">
<div class="admin-heading"><span class="eyebrow eyebrow-dark">Plan amendment</span><h1>Edit {{ $plan->plan_number }}</h1><p><strong>{{ $primaryClientName ?: 'No primary client' }}</strong> <span aria-hidden="true">&middot;</span> Plan # {{ $plan->plan_number }}</p><p>Changes create a new effective-dated billing record. Existing invoices and ledger entries remain unchanged.</p></div>
<form class="admin-form-card" method="POST" action="{{ route('admin.plans.update', $plan) }}">@csrf @method('PUT')
@if($errors->any())<div class="alert alert-danger"><strong>The amendment was not saved.</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<div class="plan-form-section"><div class="plan-form-number">1</div><div class="plan-form-body"><h2>Plan and property</h2><div class="row g-3">
<div class="col-md-4"><label class="form-label">APN / Plan #</label><input class="form-control" name="plan_number" value="{{ old('plan_number',$plan->plan_number) }}" required></div>
<div class="col-md-6"><label class="form-label">Property description</label><input class="form-control" name="title" value="{{ old('title',$plan->title) }}" required></div>
<div class="col-md-2"><label class="form-label">Status</label><select class="form-select" name="status">@foreach(['draft'=>'Draft','active'=>'Active','paused'=>'Paused','terminated'=>'Terminated','closed'=>'Closed'] as $value=>$label)<option value="{{ $value }}" @selected(old('status',$plan->status)===$value)>{{ $label }}</option>@endforeach</select></div>
<div class="col-12"><label class="form-label">Additional property details</label><textarea class="form-control" name="asset_description" rows="2">{{ old('asset_description',$plan->asset_description) }}</textarea></div>
<div class="col-12"><div class="form-check form-switch"><input type="hidden" name="automated_reminders_enabled" value="0"><input class="form-check-input" type="checkbox" id="automated_reminders_enabled" name="automated_reminders_enabled" value="1" @checked(old('automated_reminders_enabled',$plan->automated_reminders_enabled))><label class="form-check-label" for="automated_reminders_enabled">Allow automated email reminders for this plan</label></div></div>
<div class="col-12"><div class="form-check form-switch"><input type="hidden" name="automatic_invoice_email_enabled" value="0"><input class="form-check-input" type="checkbox" id="automatic_invoice_email_enabled" name="automatic_invoice_email_enabled" value="1" @checked(old('automatic_invoice_email_enabled',$plan->automatic_invoice_email_enabled))><label class="form-check-label" for="automatic_invoice_email_enabled">Automatically email newly generated invoices (inline)</label></div></div>
<div class="col-12"><label class="form-label">Internal notes</label><textarea class="form-control" name="notes" rows="2">{{ old('notes',$plan->notes) }}</textarea></div>
</div></div></div>

<div class="plan-form-section"><div class="plan-form-number">2</div><div class="plan-form-body"><h2>Contract amounts</h2><p class="text-muted">Administrator-only financial terms. Changes create an immutable ledger adjustment and do not rewrite prior transactions.</p><div class="row g-3">
<div class="col-md-4"><label class="form-label">Purchase price</label><div class="input-group"><span class="input-group-text">$</span><input class="form-control" name="purchase_price" value="{{ old('purchase_price',number_format($plan->purchase_price/100,2,'.','')) }}" required></div></div>
<div class="col-md-4"><label class="form-label">Documentation fee</label><div class="input-group"><span class="input-group-text">$</span><input class="form-control" name="documentation_fee_standard" value="{{ old('documentation_fee_standard',number_format($plan->documentation_fee_standard/100,2,'.','')) }}" required></div></div>
<div class="col-md-4"><label class="form-label">Fee waived</label><div class="input-group"><span class="input-group-text">$</span><input class="form-control" name="documentation_fee_waived" value="{{ old('documentation_fee_waived',number_format($plan->documentation_fee_waived/100,2,'.','')) }}" required></div></div>
<div class="col-12"><label class="form-label">Waiver reason <span class="text-muted">(required when a fee is waived)</span></label><input class="form-control" name="documentation_fee_waiver_reason" maxlength="500" value="{{ old('documentation_fee_waiver_reason',$plan->documentation_fee_waiver_reason) }}"></div>
</div></div></div>

<div class="plan-form-section"><div class="plan-form-number">3</div><div class="plan-form-body"><h2>Payment schedule</h2><div class="row g-3">
<div class="col-md-3"><label class="form-label">Contract start date</label><input type="date" class="form-control" name="contract_start_date" value="{{ old('contract_start_date',$plan->plan_start_date?->format('Y-m-d')) }}" required></div>
<div class="col-md-3"><label class="form-label">First payment amount</label><div class="input-group"><span class="input-group-text">$</span><input class="form-control" name="first_payment_amount" value="{{ old('first_payment_amount',$plan->first_payment_amount === null ? '' : number_format($plan->first_payment_amount/100,2,'.','')) }}"></div></div>
<div class="col-md-3"><label class="form-label">First payment due date</label><input type="date" class="form-control" name="first_payment_due_date" value="{{ old('first_payment_due_date',$plan->first_due_date?->format('Y-m-d')) }}"></div>
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
