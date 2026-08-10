@extends('layouts.admin')
@section('title',$plan->title.' | LandPay')
@section('body_class','admin-page')
@section('content')
@php
$primaryMembership = $plan->memberships->firstWhere('role', 'primary');
$primaryClient = $primaryMembership?->client;
$primaryClientName = $primaryClient?->organization_name ?: trim(($primaryClient?->first_name ?? '').' '.($primaryClient?->last_name ?? ''));
@endphp
<section class="admin-section"><div class="container-fluid dashboard-container">
<div class="admin-heading d-flex flex-wrap justify-content-between align-items-end gap-3">
    <div>
        <span class="eyebrow eyebrow-dark">Payment plan</span>
        
<h1 class="d-flex flex-wrap align-items-center gap-2">
    @if($primaryClient)
        <a class="dashboard-client-link" href="{{ route('admin.clients.show',$primaryClient) }}">
            {{ $primaryClientName }}
        </a>
    @else
        No primary client
    @endif

    @if($plan->accelerated_testing_mode)
    <a
    href="{{ route('admin.plans.edit', $plan) }}#accelerated_testing_mode"
    class="text-decoration-none"
    title="Testing mode enabled — click to edit"
    >
    <span
        class="dashboard-status status-due ms-2"
        style="font-size:1rem; letter-spacing:normal; padding:.35rem .8rem;"
        title="Accelerated testing mode (daily billing cycle)"
    >
        TEST: Daily Billing
    </span>
    </a>
    @endif
</h1>
        <p class="mb-0">APN / Plan # {{ $plan->plan_number }} <span aria-hidden="true">&middot;</span> {{ ucfirst($plan->status) }} <span aria-hidden="true">&middot;</span> {{ $plan->title }}</p>

        
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-brand" href="{{ route('admin.plans.invoices.create',$plan) }}">Review next invoice</a>
        <a class="btn btn-outline-brand" href="{{ route('admin.plans.invoices.manual.create',$plan) }}">Create invoice</a>
        <a class="btn btn-brand" href="{{ route('admin.plans.payments.create',$plan) }}">Record payment</a>
        <a class="btn btn-brand" href="{{ route('admin.plans.edit',$plan) }}">Edit plan</a>
        <a class="btn btn-outline-brand" href="{{ route('admin.dashboard') }}">Back to dashboard</a>
    </div>
</div>

<div class="row g-4 mt-3">
    <div class="col-md-4"><article class="admin-summary-card"><span>Contract balance</span><strong>{{\App\Support\Money::format($contractBalance)}}</strong><span class="d-block text-muted fs-6">({{ \App\Support\Money::format($currentPayoff) }} payoff)</span></article></div>
    <div class="col-md-4"><article class="admin-summary-card"><span>Paid-in value</span><strong>{{\App\Support\Money::format($paidInValue)}}</strong></article></div>
    <div class="col-md-4"><article class="admin-summary-card"><span>Monthly payment</span><strong>{{\App\Support\Money::format($plan->customary_monthly_payment)}}</strong></article></div>
</div>

<div class="admin-next-card mt-4" id="pause-plan-controls">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h2 class="mb-0">Plan details</h2>
        @if(in_array($plan->status, ['active','paused'], true))
        <button class="btn btn-sm {{ $plan->status === 'paused' ? 'btn-warning' : 'btn-outline-danger' }}" type="button" data-bs-toggle="collapse" data-bs-target="#billingScheduleControl" aria-expanded="{{ $plan->status === 'paused' ? 'true' : 'false' }}" aria-controls="billingScheduleControl">{{ $plan->status === 'paused' ? 'Paused — resume plan' : 'Pause plan' }}</button>
        @endif
    </div>
    @if(in_array($plan->status, ['active','paused'], true))
    <div class="collapse {{ $plan->status === 'paused' ? 'show' : '' }}" id="billingScheduleControl">
        <div class="border rounded bg-light p-3 mt-3">
        @if($plan->status === 'paused')
            <p class="small mb-2"><strong>Billing schedule paused.</strong> Scheduled invoices and automated reminders are suspended. Payments remain available.</p>
            <form method="post" action="{{ route('admin.plans.resume',$plan) }}" class="row g-2 align-items-end">@csrf
                <div class="col-md-3"><label class="form-label small">Resume effective</label><input class="form-control form-control-sm" type="date" name="resume_date" value="{{ today()->format('Y-m-d') }}" required></div>
                <div class="col-md-6"><label class="form-label small">Resume note</label><input class="form-control form-control-sm" name="resume_note" maxlength="500"></div>
                <div class="col-md-3"><button class="btn btn-brand btn-sm w-100">Resume plan</button></div>
            </form>
        @else
            <p class="small text-muted mb-2">Stops scheduled invoices and automated reminders. Payments remain available; paused periods are not back-billed.</p>
            <form method="post" action="{{ route('admin.plans.pause',$plan) }}" class="row g-2 align-items-end">@csrf
                <div class="col-md-3"><label class="form-label small">Pause effective</label><input class="form-control form-control-sm" type="date" name="pause_date" value="{{ today()->format('Y-m-d') }}" required></div>
                <div class="col-md-3"><label class="form-label small">Planned resume (optional)</label><input class="form-control form-control-sm" type="date" name="planned_resume_date"></div>
                <div class="col-md-4"><label class="form-label small">Reason</label><input class="form-control form-control-sm" name="reason" maxlength="500" required></div>
                <div class="col-md-2"><button class="btn btn-outline-danger btn-sm w-100">Pause plan</button></div>
            </form>
        @endif
        </div>
    </div>
    @endif
    <dl class="row mb-0 mt-3">
        <dt class="col-sm-4 col-lg-3">Purchase price</dt><dd class="col-sm-8 col-lg-9">{{\App\Support\Money::format($plan->purchase_price)}}</dd>
        <dt class="col-sm-4 col-lg-3">Documentation fee</dt><dd class="col-sm-8 col-lg-9">{{\App\Support\Money::format($plan->documentation_fee_standard)}}</dd>
        <dt class="col-sm-4 col-lg-3">Documentation fee waived</dt><dd class="col-sm-8 col-lg-9">{{\App\Support\Money::format($plan->documentation_fee_waived)}}@if($plan->documentation_fee_waived > 0) &mdash; {{ $plan->documentation_fee_waiver_reason }}@endif</dd>
        <dt class="col-sm-4 col-lg-3">Documentation fee charged</dt><dd class="col-sm-8 col-lg-9">{{\App\Support\Money::format($plan->documentation_fee_standard - $plan->documentation_fee_waived)}}</dd>
        <dt class="col-sm-4 col-lg-3">Amount previously paid in</dt><dd class="col-sm-8 col-lg-9">{{\App\Support\Money::format($previousPaid)}}</dd>
        <dt class="col-sm-4 col-lg-3">Adjusted initial contract amount</dt><dd class="col-sm-8 col-lg-9"><strong>{{\App\Support\Money::format($plan->original_purchase_balance - $previousPaid)}}</strong></dd>
        <dt class="col-12"><hr></dt>
        <dt class="col-sm-4 col-lg-3">Property</dt><dd class="col-sm-8 col-lg-9">{{ $plan->title }}</dd>
        <dt class="col-sm-4 col-lg-3">Additional details</dt><dd class="col-sm-8 col-lg-9">{{ $plan->asset_description ?: 'None' }}</dd>
        <dt class="col-sm-4 col-lg-3">Monthly payment</dt><dd class="col-sm-8 col-lg-9">{{\App\Support\Money::format($plan->customary_monthly_payment)}}</dd>
        <dt class="col-sm-4 col-lg-3">Monthly service fee</dt><dd class="col-sm-8 col-lg-9">+ {{\App\Support\Money::format($plan->monthly_service_fee)}}</dd>
        <dt class="col-sm-4 col-lg-3">Total monthly payment</dt><dd class="col-sm-8 col-lg-9"><strong>{{\App\Support\Money::format($plan->customary_monthly_payment + $plan->monthly_service_fee)}}</strong></dd>
        <dt class="col-sm-4 col-lg-3">Invoice day</dt><dd class="col-sm-8 col-lg-9">Day {{ $plan->monthly_due_day }} of each month</dd>
        <dt class="col-sm-4 col-lg-3">Contract start</dt><dd class="col-sm-8 col-lg-9">{{ $plan->plan_start_date?->format('M j, Y') }}</dd>
        <dt class="col-sm-4 col-lg-3">Notes</dt><dd class="col-sm-8 col-lg-9">{{ $plan->notes ?: 'None' }}</dd>
    </dl>
</div>

<div class="admin-next-card mt-4">
    <h2>Clients</h2>
    <div class="mt-3">
    @forelse($plan->memberships as $membership)
        <p class="mb-2"><a class="dashboard-client-link" href="{{ route('admin.clients.show',$membership->client) }}">{{ $membership->client->organization_name ?: trim($membership->client->first_name.' '.$membership->client->last_name) }}</a> <span class="text-muted">&mdash; {{ str($membership->role)->replace('_',' ')->title() }}</span></p>
    @empty
        <p class="text-muted mb-0">No clients are associated with this plan.</p>
    @endforelse
    </div>
</div>

<div class="admin-next-card mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2"><div><h2 class="mb-1">Invoices</h2><p class="text-muted mb-0">All invoices issued for this plan.</p></div><div class="d-flex flex-wrap gap-2"><a class="btn btn-outline-brand" href="{{route('admin.plans.invoices.create',$plan)}}">Review next invoice</a><a class="btn btn-outline-brand" href="{{route('admin.plans.invoices.manual.create',$plan)}}">Create invoice</a></div></div>
    <div class="table-responsive mt-3"><table class="table align-middle"><thead><tr><th>Invoice</th><th>Issued</th><th>Due</th><th>Status</th><th class="text-end">Original amount</th></tr></thead><tbody>
    @forelse($plan->invoices->sortByDesc('issue_date') as $invoice)
    <tr><td><a class="dashboard-plan-link" href="{{route('admin.invoices.show',$invoice)}}">{{$invoice->invoice_number}}</a></td><td>{{$invoice->issue_date->format('M j, Y')}}</td><td>{{$invoice->due_date->format('M j, Y')}}</td><td><span class="dashboard-status status-{{str($invoice->status->value)->replace('_','-')}}">{{str($invoice->status->value)->replace('_',' ')->title()}}</span></td><td class="money-cell">{{\App\Support\Money::format($invoice->items->sum('amount'))}}</td></tr>
    @empty
    <tr><td colspan="5" class="dashboard-empty"><strong>No invoices yet</strong><span>Review and issue the first monthly invoice when ready.</span></td></tr>
    @endforelse
    </tbody></table></div>
</div>

<div class="admin-next-card mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2"><div><h2 class="mb-1">Payments</h2><p class="text-muted mb-0">Posted payments and reversals remain visible.</p></div><a class="btn btn-outline-brand" href="{{route('admin.plans.payments.create',$plan)}}">Record payment</a></div>
    <div class="table-responsive mt-3"><table class="table align-middle"><thead><tr><th>Date</th><th>Payer</th><th>Purpose</th><th>Method</th><th>Status</th><th class="text-end">Amount</th></tr></thead><tbody>
    @forelse($payments as $payment)
    @php
        $invoiceAllocations = $payment->invoiceAllocations();
        $hasAdditional = $payment->hasAdditionalAllocation();
        $isReversed = (bool) $payment->financialTransaction->reversedBy;
    @endphp
    <tr>
        <td><a class="dashboard-plan-link" href="{{route('admin.payments.show',$payment)}}">{{$payment->received_date->format('M j, Y')}}</a></td>
        <td>{{ $payment->payer?->organization_name ?: trim(($payment->payer?->first_name ?? '').' '.($payment->payer?->last_name ?? '')) ?: 'Not specified' }}</td>
        <td>
            @if($invoiceAllocations->isNotEmpty())
                {{ $invoiceAllocations->count() > 1 ? 'Invoices' : 'Invoice' }}
                @foreach($invoiceAllocations as $allocation)
                    <a class="dashboard-plan-link" href="{{route('admin.invoices.show',$allocation->invoice)}}">{{$allocation->invoice->invoice_number}}</a>{{!$loop->last ? ',' : ''}}
                @endforeach
                payment
                @if($isReversed)
                    reversal
                @elseif($hasAdditional)
                    + additional
                @endif
            @else
                {{$isReversed ? 'Additional payment reversal' : $payment->purposeLabel()}}
            @endif
        </td>
        <td>{{str($payment->payment_method->value)->replace('_',' ')->title()}}</td>
        <td>@if($payment->financialTransaction->reversedBy)<span class="dashboard-status status-closed">Reversed</span>@else<span class="dashboard-status status-current">Posted</span>@endif</td>
        <td class="money-cell">{{\App\Support\Money::format($payment->gross_amount)}}</td>
    </tr>
    @empty
    <tr><td colspan="6" class="dashboard-empty"><strong>No payments yet</strong><span>Record the first payment when funds are received.</span></td></tr>
    @endforelse
    </tbody></table></div>
</div>

<div class="admin-next-card mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div><h2 class="mb-1">Amendment history</h2><p class="text-muted mb-0">Current and previous billing terms are retained below.</p></div>
        <span class="badge text-bg-light">{{ $plan->billingTerms->count() }} {{ str('version')->plural($plan->billingTerms->count()) }}</span>
    </div>
    <div class="table-responsive mt-3">
        <table class="table align-middle amendment-history-table">
            <thead><tr><th>Version</th><th>Effective period</th><th>Monthly payment</th><th>Service fee</th><th>Total monthly</th><th>Invoice day</th><th>Changed by</th><th>Reason</th></tr></thead>
            <tbody>
            @foreach($plan->billingTerms->sortByDesc('effective_from') as $term)
                <tr>
                    <td>@if($term->effective_to === null)<span class="dashboard-status status-current">Current</span>@else<span class="dashboard-status status-closed">Expired</span>@endif</td>
                    <td class="text-nowrap">{{ $term->effective_from->format('M j, Y') }} &mdash; {{ $term->effective_to?->format('M j, Y') ?? 'Present' }}</td>
                    <td>{{\App\Support\Money::format($term->scheduled_payment_amount)}}</td>
                    <td>{{\App\Support\Money::format($term->monthly_service_fee)}}</td>
                    <td><strong>{{\App\Support\Money::format($term->scheduled_payment_amount + $term->monthly_service_fee)}}</strong></td>
                    <td>Day {{ $term->invoice_day }}</td>
                    <td>{{ $term->createdBy?->name ?? 'System' }}</td>
                    <td>{{ $term->reason ?: ($plan->billingTerms->count() === 1 ? 'Original terms' : '&mdash;') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @if($amendments->isNotEmpty())
        <div class="amendment-timeline mt-4">
        @foreach($amendments as $amendment)
            @php
                $beforeTerms = $amendment->before_values['billing_terms'] ?? [];
                $afterTerms = $amendment->after_values['billing_terms'] ?? [];
                $beforePlan = $amendment->before_values['plan'] ?? [];
                $afterPlan = $amendment->after_values['plan'] ?? [];
            @endphp
            <article class="amendment-entry">
                <div class="amendment-entry-heading">
                    <strong>{{ $amendment->after_values['reason'] ?? 'Plan amendment' }}</strong>
                    <span>{{ $amendment->created_at->format('M j, Y g:i A') }} by {{ $amendment->actorUser?->name ?? 'Unknown administrator' }}</span>
                </div>
                <ul class="amendment-change-list">
                    @if(($beforeTerms['scheduled_payment_amount'] ?? null) !== ($afterTerms['scheduled_payment_amount'] ?? null))<li><strong>Monthly payment:</strong> {{\App\Support\Money::format((int)($beforeTerms['scheduled_payment_amount'] ?? 0))}} &rarr; {{\App\Support\Money::format((int)($afterTerms['scheduled_payment_amount'] ?? 0))}}</li>@endif
                    @if(($beforeTerms['monthly_service_fee'] ?? null) !== ($afterTerms['monthly_service_fee'] ?? null))<li><strong>Service fee:</strong> {{\App\Support\Money::format((int)($beforeTerms['monthly_service_fee'] ?? 0))}} &rarr; {{\App\Support\Money::format((int)($afterTerms['monthly_service_fee'] ?? 0))}}</li>@endif
                    @if(($beforeTerms['invoice_day'] ?? null) !== ($afterTerms['invoice_day'] ?? null))<li><strong>Invoice day:</strong> {{ $beforeTerms['invoice_day'] ?? 'None' }} &rarr; {{ $afterTerms['invoice_day'] ?? 'None' }}</li>@endif
                    @if(($beforeTerms['grace_days'] ?? null) !== ($afterTerms['grace_days'] ?? null))<li><strong>Grace period:</strong> {{ $beforeTerms['grace_days'] ?? 'None' }} days &rarr; {{ $afterTerms['grace_days'] ?? 'None' }} days</li>@endif
                    @foreach(['plan_number'=>'APN / Plan #','title'=>'Property','status'=>'Status'] as $field=>$label)
                        @if(($beforePlan[$field] ?? null) !== ($afterPlan[$field] ?? null))<li><strong>{{ $label }}:</strong> {{ $beforePlan[$field] ?? 'None' }} &rarr; {{ $afterPlan[$field] ?? 'None' }}</li>@endif
                    @endforeach
                </ul>
            </article>
        @endforeach
        </div>
    @endif
</div>
</div></section>
@endsection
