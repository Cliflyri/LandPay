@extends('layouts.admin')
@section('title','Record payment | LandPay')
@section('body_class','admin-page')
@section('content')
<section class="admin-section"><div class="container-fluid dashboard-container">
<div class="admin-heading d-flex flex-wrap justify-content-between align-items-end gap-3">
    <div><span class="eyebrow eyebrow-dark">Record payment</span><h1>{{ $primaryClientName }}</h1><p class="mb-0"><strong>APN / Plan # {{ $plan->plan_number }}</strong> <span aria-hidden="true">&mdash;</span> {{ $plan->title }}</p></div>
    <a class="btn btn-outline-brand" href="{{ route('admin.plans.show',$plan) }}">Back to plan</a>
</div>

@if($errors->any())<div class="alert alert-danger mt-4" role="alert"><strong>Payment not ready.</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<div class="row g-4 mt-2">
<div class="col-lg-7">
<form method="post" action="{{ route('admin.plans.payments.preview',$plan) }}" class="admin-next-card payment-entry-form">
@csrf
<input type="hidden" name="idempotency_token" value="{{$idempotencyToken}}">
@if($input['client_payment_intent_id'] ?? null)<input type="hidden" name="client_payment_intent_id" value="{{$input['client_payment_intent_id']}}"><input type="hidden" name="client_note" value="{{$input['client_note'] ?? ''}}"><div class="alert alert-info"><strong>Client payment notification.</strong> Verify receipt before posting.@if(filled($input['overpayment_disposition'] ?? null))<br><strong>Client overpayment instruction:</strong> {{($input['overpayment_disposition'] ?? null) === 'next_invoice_credit' ? 'Keep extra as account credit.' : 'Apply extra to principal.'}}@endif @if(filled($input['client_note'] ?? null))<br><strong>Client note:</strong> {{$input['client_note']}}@endif</div>@endif
<h2>Payment details</h2>
<p>Preview the allocation before posting. Nothing financial is recorded until you confirm.</p>

@include('admin.shared.monthly-service-fees-collected')

<div class="row g-3 mt-1">
    <div class="col-md-6"><label class="form-label" for="received_date">Date received</label><input class="form-control" id="received_date" name="received_date" type="date" required value="{{ old('received_date',$input['received_date'] ?? now()->toDateString()) }}"></div>
    <div class="col-md-6"><label class="form-label" for="amount">Amount</label><div class="input-group"><span class="input-group-text">$</span><input class="form-control" id="amount" name="amount" inputmode="decimal" required value="{{ old('amount',$input['amount'] ?? '') }}"></div></div>
    <div class="col-md-6"><label class="form-label" for="payment_type">Payment type</label><select class="form-select" id="payment_type" name="payment_type" required><option value="regular" @selected(old('payment_type',$input['payment_type'] ?? 'regular')==='regular')>Regular payment</option><option value="principal_only" @selected(old('payment_type',$input['payment_type'] ?? '')==='principal_only')>Principal only</option></select></div>
    <div class="col-md-6"><label class="form-label" for="payment_method">Method</label><select class="form-select" id="payment_method" name="payment_method" required>@foreach($methods as $method)<option value="{{$method->value}}" @selected(old('payment_method',$input['payment_method'] ?? 'other')===$method->value)>{{ str($method->value)->replace('_',' ')->title() }}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label" for="payer_client_id">Payer (optional)</label><select class="form-select" id="payer_client_id" name="payer_client_id"><option value="">Not specified</option>@foreach($plan->memberships as $membership)@php($client=$membership->client)<option value="{{$client->id}}" @selected((string)old('payer_client_id',$input['payer_client_id'] ?? '')===(string)$client->id)>{{ $client->organization_name ?: trim($client->first_name.' '.$client->last_name) }}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label" for="external_reference">Reference (optional)</label><input class="form-control" id="external_reference" name="external_reference" maxlength="150" value="{{ old('external_reference',$input['external_reference'] ?? '') }}"></div>
</div>

@php($selectedOverpayment=old('overpayment_disposition',$input['overpayment_disposition'] ?? 'principal'))
@php($enteredAmountCents=(int)round((float)old('amount',$input['amount'] ?? 0)*100))
@php($showOverpayment=old('payment_type',$input['payment_type'] ?? 'regular')==='regular' && $enteredAmountCents>$currentlyPayable)
@if(!$preview)
<fieldset class="payment-choice mt-4" id="live-overpayment-choice" data-currently-payable="{{$currentlyPayable}}" @if(!$showOverpayment) hidden @endif><legend>How should the extra payment be applied?</legend><p>This payment includes <strong id="live-overpayment-extra">{{\App\Support\Money::format(max(0,$enteredAmountCents-$currentlyPayable))}}</strong> beyond the amount currently payable.</p>
<div class="form-check"><input class="form-check-input" type="radio" name="overpayment_disposition" id="live_overpayment_principal" value="principal" @checked($selectedOverpayment==='principal')><label class="form-check-label" for="live_overpayment_principal"><strong>Apply to principal.</strong> Reduces the contract balance immediately.</label></div>
<div class="form-check mt-2"><input class="form-check-input" type="radio" name="overpayment_disposition" id="live_overpayment_credit" value="next_invoice_credit" @checked($selectedOverpayment==='next_invoice_credit')><label class="form-check-label" for="live_overpayment_credit"><strong>Carry forward as credit.</strong> Reduces a future invoice when applied later.</label></div>
</fieldset>
@endif
@if($preview && $preview['overpayment_amount'] > 0)
<fieldset class="payment-choice mt-4"><legend>Client instruction required</legend><p>This payment includes <strong>{{\App\Support\Money::format($preview['overpayment_amount'])}}</strong> beyond currently open invoices. Record the client’s choice; neither option is selected automatically.</p>
<div class="form-check"><input class="form-check-input" type="radio" name="overpayment_disposition" id="overpayment_principal" value="principal" @checked($selectedOverpayment==='principal')><label class="form-check-label" for="overpayment_principal"><strong>Apply to principal.</strong> Reduces the contract balance immediately.</label></div>
<div class="form-check mt-2"><input class="form-check-input" type="radio" name="overpayment_disposition" id="overpayment_credit" value="next_invoice_credit" @checked($selectedOverpayment==='next_invoice_credit')><label class="form-check-label" for="overpayment_credit"><strong>Carry forward as credit.</strong> Reduces a future invoice when applied later.</label></div>
</fieldset>
@endif

<div class="d-flex flex-wrap gap-2 mt-4">
    <button class="btn btn-outline-brand" type="submit">{{ $preview ? 'Refresh preview' : 'Preview allocation' }}</button>
    @if($preview && ($preview['overpayment_amount']===0 || filled($input['overpayment_disposition'] ?? null)))<button class="btn btn-brand" type="submit" formaction="{{ route('admin.plans.payments.store',$plan) }}">Confirm and post payment</button><button class="btn btn-sun" type="submit" name="email_receipt" value="1" formaction="{{ route('admin.plans.payments.store',$plan) }}">Post and email receipt</button>@endif
</div>
</form>
</div>
<div class="col-lg-5">
    @if($uninvoicedFirstPaymentDue > 0)<div><dt>Uninvoiced first payment due</dt><dd>{{\App\Support\Money::format($uninvoicedFirstPaymentDue)}}</dd></div>
    <div><dt>Currently payable</dt><dd><strong>{{\App\Support\Money::format($currentlyPayable)}}</strong></dd></div>@endif
<div class="admin-next-card payment-preview-card">
<h2>Contract summary</h2>
<dl class="payment-preview-summary mb-4">
    <div class="open-invoices-total"><dt>Open invoices</dt><dd>{{\App\Support\Money::format($invoiceBalance)}}</dd></div>
    <div><dt>Purchase price</dt><dd>{{\App\Support\Money::format($plan->purchase_price)}}</dd></div>
    @if($plan->documentation_fee_standard > 0)
    <div><dt>Documentation fee</dt><dd>{{\App\Support\Money::format($plan->documentation_fee_standard)}}</dd></div>
    @endif
    @if($plan->documentation_fee_waived > 0)
    <div><dt>Fee waived</dt><dd>&minus; {{\App\Support\Money::format($plan->documentation_fee_waived)}}</dd></div>
    @endif
    <div><dt>Total purchase price</dt><dd>{{\App\Support\Money::format($plan->original_purchase_balance)}}</dd></div>
    <div><dt>Contract balance</dt><dd>{{\App\Support\Money::format($contractBalance)}}</dd></div>
</dl>
<hr>
<h2>Allocation preview</h2>
@if(!$preview)<p>Enter the payment details to see exactly how funds will be allocated.</p>
@else
<div class="payment-preview-total"><span>Payment total</span><strong>{{\App\Support\Money::format($preview['amount'])}}</strong></div>
<ol class="payment-allocation-list">
@foreach($preview['allocations'] as $allocation)<li><div><strong>{{ $allocation['label'] }}</strong>@if($allocation['invoice_number'] ?? null)<small>@if($allocation['invoice_id'] ?? null)<a href="{{route('admin.invoices.show',$allocation['invoice_id'])}}">Invoice {{ $allocation['invoice_number'] }}</a>@else Invoice {{ $allocation['invoice_number'] }}@endif</small>@endif</div><span>{{\App\Support\Money::format($allocation['amount'])}}</span></li>@endforeach
@if($preview['overpayment_amount']>0 && blank($input['overpayment_disposition'] ?? null))<li class="payment-allocation-pending"><div><strong>Client choice pending</strong><small>Select how to use the excess funds.</small></div><span>{{\App\Support\Money::format($preview['overpayment_amount'])}}</span></li>@endif
</ol>
<dl class="payment-preview-summary"><div><dt>Applied to invoices</dt><dd>{{\App\Support\Money::format($preview['invoice_amount'])}}</dd></div><div><dt>Applied to principal</dt><dd>{{\App\Support\Money::format($preview['principal_amount'])}}</dd></div><div><dt>Future credit</dt><dd>{{\App\Support\Money::format($preview['credit_amount'])}}</dd></div></dl>
@endif
</div>
</div>
</div>
</div></section>
<script>
(() => {
    const previewChoice = document.querySelector('.payment-choice:not(#live-overpayment-choice)');
    if (previewChoice) {
        const formattedExtra = previewChoice.querySelector('p strong')?.textContent || '';
        previewChoice.querySelector('legend').textContent = 'How should the extra payment be applied?';
        previewChoice.querySelector('p').innerHTML = 'This payment includes <strong>' + formattedExtra
            + '</strong> beyond the amount currently payable. Apply to principal is selected by default.';
    }
    const drawer = document.getElementById('live-overpayment-choice');
    if (!drawer) return;
    const amount = document.getElementById('amount');
    const paymentType = document.getElementById('payment_type');
    const principal = document.getElementById('live_overpayment_principal');
    const extra = document.getElementById('live-overpayment-extra');
    const payable = Number(drawer.dataset.currentlyPayable || 0);
    const currency = new Intl.NumberFormat('en-US', {style: 'currency', currency: 'USD'});
    const update = () => {
        const cents = Math.round((Number(String(amount.value).replace(/[^0-9.-]/g, '')) || 0) * 100);
        const show = paymentType.value === 'regular' && cents > payable;
        drawer.hidden = !show;
        extra.textContent = currency.format(Math.max(0, cents - payable) / 100);
        if (!show) principal.checked = true;
    };
    amount.addEventListener('input', update);
    paymentType.addEventListener('change', update);
    update();
})();
</script>
@endsection
