@extends('layouts.app')
@section('title',$invoice->invoice_number.' | LandPay')
@section('body_class','admin-page')
@section('content')
@php($primary=$invoice->paymentPlan->memberships->firstWhere('role','primary')?->client)
@php($clientName=$primary?->organization_name ?: trim(($primary?->first_name ?? '').' '.($primary?->last_name ?? '')) ?: 'Not assigned')
@php($statusLabel=str($invoice->status->value)->replace('_',' ')->title())
<section class="admin-section"><div class="container site-container">
<div class="admin-heading d-flex flex-wrap justify-content-between align-items-end gap-3">
    <div><span class="eyebrow eyebrow-dark">Invoice</span><div class="d-flex flex-wrap align-items-center gap-2"><h1 class="mb-0">{{$invoice->invoice_number}}</h1><span class="dashboard-status status-{{str($invoice->status->value)->slug()}}">{{$statusLabel}}</span></div><p class="mb-0 mt-2">{{$invoice->paymentPlan->title}} <span aria-hidden="true">&middot;</span> APN / Plan # {{$invoice->paymentPlan->apn ?: $invoice->paymentPlan->plan_number}}</p></div>
    <div class="d-flex flex-wrap gap-2">
        @if($invoice->status !== \App\Enums\InvoiceStatus::Voided && $reminderRecipient?->client?->email)
            <form method="post" action="{{route('admin.invoices.email.store',$invoice)}}" class="d-flex gap-2" onsubmit='return confirm(@js('Email invoice '.$invoice->invoice_number.' to '.$reminderRecipient->client->email.'?')) && confirm("Final confirmation: send this invoice email now?");'>@csrf<select class="form-select" name="delivery_format" aria-label="Invoice email format"><option value="inline" @selected(old('delivery_format','inline')==='inline')>Inline only</option><option value="both" @selected(old('delivery_format')==='both')>Inline + PDF</option><option value="pdf" @selected(old('delivery_format')==='pdf')>PDF attachment</option></select><button class="btn btn-outline-brand text-nowrap" type="submit">Email invoice</button></form>
        @endif
        @if($invoice->status !== \App\Enums\InvoiceStatus::Voided && $balance>0 && $reminderRecipient?->client?->email)<form method="post" action="{{route('admin.invoices.reminders.store',$invoice)}}" onsubmit="return confirm('Send a payment reminder to {{$reminderRecipient->client->email}}?');">@csrf<button class="btn btn-outline-brand text-nowrap" type="submit">Send reminder</button></form>@endif
        <a class="btn btn-brand text-nowrap" href="{{route('admin.plans.payments.create',$invoice->paymentPlan)}}">Enter payment</a>
        <a class="btn btn-outline-brand text-nowrap" href="{{route('admin.plans.show',$invoice->paymentPlan)}}">Back to plan</a>
    </div>
</div>

@if(session('success'))<div class="alert alert-success mt-4">{{session('success')}}</div>@endif
@if($errors->any())<div class="alert alert-danger mt-4">{{$errors->first()}}</div>@endif
@if($invoice->status === \App\Enums\InvoiceStatus::Voided)<div class="alert alert-warning mt-4"><strong>Deleted invoice.</strong> This invoice is retained for audit history, but its obligation has been removed.</div>@endif

<div class="admin-next-card invoice-details-card mt-4">
    <h2>Invoice details</h2>
    <dl class="row g-3 mb-0">
        <div class="col-sm-6 col-lg-3"><dt>Client</dt><dd class="mb-0">{{$clientName}}</dd></div>
        <div class="col-sm-6 col-lg-3"><dt>Invoice date</dt><dd class="mb-0">{{$invoice->issue_date->format('M j, Y')}}</dd></div>
        <div class="col-sm-6 col-lg-3"><dt>Due date</dt><dd class="mb-0 {{$balance>0 && $invoice->due_date->isPast() ? 'invoice-date-overdue' : ''}}">{{$invoice->due_date->format('M j, Y')}}</dd></div>
        <div class="col-sm-6 col-lg-3"><dt>Billing period</dt><dd class="mb-0">@if($invoice->period_start && $invoice->period_end){{$invoice->period_start->format('M j')}}{{$invoice->period_end->format('M j, Y')}}@else Not specified @endif</dd></div>
    </dl>
</div>

<div class="row g-4 mt-2 align-items-start">
    <div class="col-lg-7">
        <div class="admin-next-card invoice-charges-card">
            <h2>Charges</h2>
            <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Description</th><th class="text-end">Standard</th><th class="text-end">Waived</th><th class="text-end">Amount</th></tr></thead><tbody>
                @foreach($invoice->items->sortBy('display_order') as $item)<tr><td>{{$item->description}}@if($item->waiver_reason)<small class="d-block text-muted">{{$item->waiver_reason}}</small>@endif</td><td class="money-cell">{{\App\Support\Money::format($item->standard_amount)}}</td><td class="money-cell">@if($item->waived_amount>0)&minus; {{\App\Support\Money::format($item->waived_amount)}}@else{{\App\Support\Money::format(0)}}@endif</td><td class="money-cell">{{\App\Support\Money::format($item->amount)}}</td></tr>@endforeach
            </tbody></table></div>
        </div>
    </div>
    <div class="col-lg-5">
        <aside class="admin-next-card invoice-financial-summary" aria-labelledby="invoice-summary-title">
            <div class="d-flex justify-content-between align-items-center gap-3"><h2 id="invoice-summary-title" class="mb-0">Invoice summary</h2><span class="dashboard-status status-{{str($invoice->status->value)->slug()}}">{{$statusLabel}}</span></div>
            <dl class="payment-preview-summary mb-0">
                <div><dt>Subtotal</dt><dd>{{\App\Support\Money::format($summary['subtotal'])}}</dd></div>
                @if($summary['waivers']>0)<div><dt>Waivers / discounts</dt><dd>&minus; {{\App\Support\Money::format($summary['waivers'])}}</dd></div>@endif
                <div class="invoice-summary-primary"><dt>Invoice amount</dt><dd>{{\App\Support\Money::format($summary['invoiceAmount'])}}</dd></div>
                @if($summary['creditApplied']>0)<div><dt>Account credit applied</dt><dd>&minus; {{\App\Support\Money::format($summary['creditApplied'])}}</dd></div>@endif
                <div><dt>Paid to date</dt><dd>@if($summary['paidToDate']>0)&minus; {{\App\Support\Money::format($summary['paidToDate'])}}@else{{\App\Support\Money::format(0)}}@endif</dd></div>
                @if($summary['adjustments']!==0)<div><dt>Adjustments</dt><dd>@if($summary['adjustments']<0)&minus; {{\App\Support\Money::format(abs($summary['adjustments']))}}@else+ {{\App\Support\Money::format($summary['adjustments'])}}@endif</dd></div>@endif
                <div class="invoice-summary-balance {{$balance>0?'has-balance':''}}"><dt>Balance due</dt><dd>{{\App\Support\Money::format($balance)}}</dd></div>
            </dl>
        </aside>
    </div>
</div>

@if($canCreateFirstPayment)<form class="admin-next-card mt-4" method="post" action="{{route('admin.invoices.first-payment.store',$invoice)}}" onsubmit="return confirm('Create a new first-payment invoice? The deleted invoice will remain only in audit history.');">@csrf<h2>Create first-payment invoice</h2><p>Create a new first-payment invoice with the same amount and dates. The deleted invoice remains only in audit history.</p><button class="btn btn-brand" type="submit">Create first-payment invoice</button></form>@endif
@if($canDelete)<form class="admin-next-card mt-4" method="post" action="{{route('admin.invoices.destroy',$invoice)}}" onsubmit="return confirm('Delete this invoice obligation? The invoice will be retained as voided for audit history, and the contract balance will not change.');">@csrf @method('DELETE')<h2>Delete invoice</h2><p>Use this when the client is allowed to skip this billing period. The outstanding invoice obligation will be removed, while the contract balance and audit history remain unchanged.</p><label class="form-label" for="reason">Reason</label><textarea class="form-control" id="reason" name="reason" rows="3" maxlength="500" required></textarea><button class="btn btn-outline-danger mt-3" type="submit">Delete invoice</button></form>@endif
</div></section>
@endsection
