@extends('layouts.app')
@section('title','Create invoice | LandPay')
@section('body_class','admin-page')
@section('content')
@php($rows = old('items', $input['items'] ?? [['type' => 'principal', 'description' => 'Plan payment', 'amount' => '']]))
<section class="admin-section"><div class="container site-container">
<div class="admin-heading d-flex flex-wrap justify-content-between align-items-end gap-3">
<div><span class="eyebrow eyebrow-dark">Manual invoice</span><h1>Create invoice</h1><p class="mb-0">{{ $plan->title }} <span aria-hidden="true">&middot;</span> APN / Plan # {{ $plan->plan_number }}</p></div>
<a class="btn btn-outline-brand" href="{{ route('admin.plans.show',$plan) }}">Back to plan</a>
</div>
@if($errors->any())<div class="alert alert-danger mt-4"><strong>Invoice not ready.</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@if($plan->status === 'paused')<div class="alert alert-warning mt-4"><strong>This plan is paused.</strong> This manual invoice will not resume or change the automatic schedule.</div>@endif
<div class="row g-4 mt-2"><div class="col-lg-8">
<form class="admin-next-card" method="post" action="{{ route('admin.plans.invoices.manual.preview',$plan) }}">@csrf
<h2>Invoice details</h2><p>Create a plan-specific invoice without changing the next scheduled invoice.</p>
<p class="alert alert-light border mb-3"><strong>Plan Payment:</strong> {{ \App\Support\Money::format((int)$plan->customary_monthly_payment) }}, <strong>Fee:</strong> {{ \App\Support\Money::format($serviceFee) }}. Fees are not automatically added to a created invoice.</p>
<div class="row g-3">
<div class="col-md-4"><label class="form-label" for="issue_date">Invoice date</label><input class="form-control" type="date" id="issue_date" name="issue_date" required value="{{ old('issue_date',$input['issue_date'] ?? today()->format('Y-m-d')) }}"></div>
<div class="col-md-4"><label class="form-label">Plan service fee</label><div class="form-control bg-light">{{ \App\Support\Money::format($serviceFee) }}</div><div class="form-text">Not automatically added; add a fee line below when needed.</div></div>
<div class="col-md-4"><label class="form-label">Payment terms</label><div class="form-control bg-light">Due {{ $dueDays }} {{ str('day')->plural($dueDays) }} after invoice</div></div>
</div>
<div class="d-flex justify-content-between align-items-center mt-4"><div><h2 class="mb-1">Line items</h2><p class="text-muted mb-0">Only a plan payment reduces principal when paid.</p></div><button class="btn btn-sm btn-outline-brand" id="add-invoice-item" type="button">Add line item</button></div>
<div id="manual-invoice-items" class="mt-3">
@foreach($rows as $index => $row)
<div class="manual-invoice-item border rounded p-3 mb-3"><div class="row g-3 align-items-end">
<div class="col-md-3"><label class="form-label">Type</label><select class="form-select" name="items[{{ $index }}][type]" required><option value="principal" @selected(($row['type'] ?? '') === 'principal')>Plan payment</option><option value="fee" @selected(($row['type'] ?? '') === 'fee')>Fee</option><option value="other" @selected(($row['type'] ?? '') === 'other')>Other plan charge</option></select></div>
<div class="col-md-5"><label class="form-label">Description</label><input class="form-control" name="items[{{ $index }}][description]" maxlength="500" required value="{{ $row['description'] ?? '' }}"></div>
<div class="col-md-3"><label class="form-label">Amount</label><div class="input-group"><span class="input-group-text">$</span><input class="form-control" name="items[{{ $index }}][amount]" inputmode="decimal" required value="{{ $row['amount'] ?? '' }}"></div></div>
<div class="col-md-1"><button class="btn btn-outline-danger remove-invoice-item" type="button" aria-label="Remove line item">&times;</button></div>
</div></div>
@endforeach
</div>
<div class="d-flex flex-wrap gap-2 mt-4"><button class="btn btn-outline-brand" type="submit">{{ $preview ? 'Refresh preview' : 'Preview invoice' }}</button>@if($preview)<button class="btn btn-brand" type="submit" formaction="{{ route('admin.plans.invoices.manual.store',$plan) }}">Issue invoice</button>@endif</div>
</form></div>
<div class="col-lg-4"><div class="admin-next-card"><h2>Invoice preview</h2>
@if(!$preview)<p>Add the invoice date and line items to calculate the invoice.</p>@else
<dl class="payment-preview-summary"><div><dt>Invoice date</dt><dd>{{ $preview['issue_date']->format('M j, Y') }}</dd></div><div><dt>Due date</dt><dd>{{ $preview['due_date']->format('M j, Y') }}</dd></div>
@foreach($preview['items'] as $item)<div><dt>{{ $item['description'] }} <small class="text-muted">({{ $item['type'] === 'principal' ? 'plan payment' : ($item['type'] === 'fee' ? 'fee' : 'other') }})</small></dt><dd>{{ \App\Support\Money::format($item['amount']) }}</dd></div>@endforeach
<div><dt>Total due</dt><dd>{{ \App\Support\Money::format($preview['total']) }}</dd></div></dl>
<p class="small text-muted mb-0">{{ \App\Support\Money::format($preview['principal']) }} will reduce plan principal when paid.</p>
@endif
</div><article class="admin-summary-card mt-4"><span>Contract balance</span><strong>{{ \App\Support\Money::format($contractBalance) }}</strong></article></div>
</div></div></section>
<template id="manual-invoice-item-template"><div class="manual-invoice-item border rounded p-3 mb-3"><div class="row g-3 align-items-end"><div class="col-md-3"><label class="form-label">Type</label><select class="form-select" name="items[INDEX][type]" required><option value="principal">Plan payment</option><option value="fee">Fee</option><option value="other">Other plan charge</option></select></div><div class="col-md-5"><label class="form-label">Description</label><input class="form-control" name="items[INDEX][description]" maxlength="500" required></div><div class="col-md-3"><label class="form-label">Amount</label><div class="input-group"><span class="input-group-text">$</span><input class="form-control" name="items[INDEX][amount]" inputmode="decimal" required></div></div><div class="col-md-1"><button class="btn btn-outline-danger remove-invoice-item" type="button" aria-label="Remove line item">&times;</button></div></div></div></template>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const list = document.getElementById('manual-invoice-items');
    const template = document.getElementById('manual-invoice-item-template');
    let nextIndex = list.querySelectorAll('.manual-invoice-item').length;
    document.getElementById('add-invoice-item').addEventListener('click', function () {
        list.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('INDEX', nextIndex++));
    });
    list.addEventListener('click', function (event) {
        const button = event.target.closest('.remove-invoice-item');
        if (button && list.querySelectorAll('.manual-invoice-item').length > 1) button.closest('.manual-invoice-item').remove();
    });
});
</script>
@endsection
