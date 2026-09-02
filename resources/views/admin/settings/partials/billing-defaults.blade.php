@php
$billing = $billingDefaults;
$stageOneType = old('stage_one_fee_type', $billing?->stage_one_fee_type?->value ?? 'fixed');
$stageTwoType = old('stage_two_fee_type', $billing?->stage_two_fee_type?->value ?? 'fixed');
$stageOneValue = $stageOneType === 'percentage' ? ($billing?->stage_one_percentage_rate ?? '0.0000') : number_format(($billing?->stage_one_fixed_amount ?? 2500) / 100, 2, '.', '');
$stageTwoValue = $stageTwoType === 'percentage' ? ($billing?->stage_two_percentage_rate ?? '0.0000') : number_format(($billing?->stage_two_fixed_amount ?? 5000) / 100, 2, '.', '');
@endphp
<div class="tab-pane fade" id="billing-settings" role="tabpanel" tabindex="0">
<div class="admin-next-card mt-4"><h2>Billing defaults</h2><p class="text-muted">These values prefill new plans only. Existing plans are not changed.</p>
<form method="post" action="{{route('admin.settings.billing.update')}}" class="row g-3">@csrf @method('put')
<div class="col-md-4"><label class="form-label">Default plan payment <span class="text-muted">(optional)</span></label><div class="input-group"><span class="input-group-text">$</span><input class="form-control" name="scheduled_payment_amount" value="{{old('scheduled_payment_amount',number_format(($billing?->scheduled_payment_amount ?? 0)/100,2,'.',''))}}"></div></div>
<div class="col-md-4"><label class="form-label">Monthly service fee</label><div class="input-group"><span class="input-group-text">$</span><input class="form-control" name="monthly_service_fee" value="{{old('monthly_service_fee',number_format(($billing?->monthly_service_fee ?? 0)/100,2,'.',''))}}" required></div></div>
<div class="col-md-4"><label class="form-label">Invoice due after</label><div class="input-group"><input class="form-control" type="number" name="due_days_after_issue" min="0" max="60" value="{{old('due_days_after_issue',$billing?->due_days_after_issue ?? 5)}}" required><span class="input-group-text">days</span></div></div>
<div class="col-md-4"><label class="form-label">Grace period</label><div class="input-group"><input class="form-control" type="number" name="grace_days" min="0" max="60" value="{{old('grace_days',$billing?->grace_days ?? 0)}}" required><span class="input-group-text">days</span></div></div>
<div class="col-12"><hr><h3 class="h5">Stage-one late fee <span class="text-muted">(enabled)</span></h3></div>
<div class="col-md-4"><label class="form-label">Calculation</label><select class="form-select" name="stage_one_fee_type"><option value="fixed" @selected($stageOneType==='fixed')>Fixed amount</option><option value="percentage" @selected($stageOneType==='percentage')>Percentage</option></select></div>
<div class="col-md-4"><label class="form-label">Value</label><input class="form-control" name="stage_one_fee_value" value="{{$stageOneValue}}" required></div>
<div class="col-md-4"><label class="form-label">Percentage minimum</label><input class="form-control" name="stage_one_minimum_amount" value="{{old('stage_one_minimum_amount',number_format(($billing?->stage_one_minimum_amount ?? 0)/100,2,'.',''))}}"></div>
<div class="col-12"><hr><div class="form-check form-switch"><input type="hidden" name="stage_two_enabled" value="0"><input class="form-check-input" type="checkbox" id="billing_stage_two_enabled" name="stage_two_enabled" value="1" @checked(old('stage_two_enabled',$billing?->stage_two_enabled ?? true))><label class="form-check-label h5" for="billing_stage_two_enabled">Enable stage-two late fee by default</label></div></div>
<div class="col-md-4"><label class="form-label">Apply when</label><div class="input-group"><input class="form-control" type="number" name="stage_two_days_late" min="1" max="365" value="{{old('stage_two_days_late',$billing?->stage_two_days_late ?? 30)}}"><span class="input-group-text">days late</span></div></div>
<div class="col-md-4"><label class="form-label">Calculation</label><select class="form-select" name="stage_two_fee_type"><option value="fixed" @selected($stageTwoType==='fixed')>Fixed amount</option><option value="percentage" @selected($stageTwoType==='percentage')>Percentage</option></select></div>
<div class="col-md-4"><label class="form-label">Value</label><input class="form-control" name="stage_two_fee_value" value="{{$stageTwoValue}}"></div>
<div class="col-md-4"><label class="form-label">Percentage minimum</label><input class="form-control" name="stage_two_minimum_amount" value="{{old('stage_two_minimum_amount',number_format(($billing?->stage_two_minimum_amount ?? 0)/100,2,'.',''))}}"></div>
<div class="col-md-4"><label class="form-label">Default eligibility</label><div class="input-group"><input class="form-control" type="number" name="default_eligibility_days" min="1" max="730" value="{{old('default_eligibility_days',$billing?->default_eligibility_days ?? 60)}}" required><span class="input-group-text">days</span></div></div>
<div class="col-12"><button class="btn btn-brand">Save billing defaults</button></div>
</form></div></div>
