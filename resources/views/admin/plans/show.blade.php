@extends('layouts.admin')
@section('title',$plan->title.' | LandPay')
@section('body_class','admin-page')
@section('content')
@php
$primaryMembership = $plan->memberships->firstWhere('role', 'primary');
$primaryClient = $primaryMembership?->client;
$primaryClientName = $primaryClient?->organization_name ?: trim(($primaryClient?->first_name ?? '').' '.($primaryClient?->last_name ?? ''));
@endphp
<section class="admin-section"><div class="container-fluid dashboard-container px-2">
<div class="admin-heading d-flex flex-wrap justify-content-between align-items-end gap-3">
    <div>
        <span class="eyebrow eyebrow-dark">Payment plan</span>
        
<h1 class="d-flex flex-wrap align-items-center gap-2">
    @if($primaryClient)
        <a class="dashboard-client-link" style="font-size: inherit;" href="{{ route('admin.clients.show',$primaryClient) }}">
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
        @if($primaryClient)<a class="btn btn-outline-brand" href="{{ route('admin.messages.create',['client'=>$primaryClient->id,'plan'=>$plan->id]) }}">Secure message</a>@endif
        <a class="btn btn-outline-brand" href="{{ route('admin.plans.invoices.create',$plan) }}">Review next invoice</a>
        <a class="btn btn-outline-brand" href="{{ route('admin.plans.invoices.manual.create',$plan) }}">Create invoice</a>
        <a class="btn btn-brand" href="{{ route('admin.plans.payments.create',$plan) }}">Record payment</a>
        <a class="btn btn-brand" href="{{ route('admin.plans.edit',$plan) }}">Edit plan</a>
        <a class="btn btn-outline-brand" href="{{ route('admin.dashboard') }}">Back to dashboard</a>
    </div>
</div>

@if(session('success'))<div class="alert alert-success mt-4">{{session('success')}}</div>@endif
<ul class="nav nav-tabs mt-4">
    <li class="nav-item"><a class="nav-link {{ request('tab') !== 'ledger' ? 'active' : '' }}" href="{{ route('admin.plans.show', $plan) }}">Plan overview</a></li>
    <li class="nav-item"><a class="nav-link {{ request('tab') === 'ledger' ? 'active' : '' }}" href="{{ route('admin.plans.show', ['plan' => $plan, 'tab' => 'ledger']) }}">Account ledger</a></li>
</ul>

@if(request('tab') === 'ledger')
<div class="admin-next-card mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div><h2 class="mb-1">Account ledger</h2><p class="text-muted mb-0">Payment reconciliation for plan # {{ $plan->plan_number }}.</p></div>
        <div class="text-end"><span class="text-muted d-block">Original contract amount</span><strong>{{ \App\Support\Money::format($plan->original_purchase_balance) }}</strong></div>
    </div>
    <div class="row g-3 mt-2">
        <div class="col-6 col-lg-2"><span class="text-muted d-block small">Payments received</span><strong>{{ \App\Support\Money::format($ledgerPayments) }}</strong></div>
        <div class="col-6 col-lg-2"><span class="text-muted d-block small">Fees paid</span><strong>{{ \App\Support\Money::format($ledgerFees) }}</strong></div>
        <div class="col-6 col-lg-2"><span class="text-muted d-block small">Principal applied</span><strong>{{ \App\Support\Money::format($ledgerPrincipal) }}</strong></div>
        <div class="col-6 col-lg-2"><span class="text-muted d-block small">Contract balance</span><strong>{{ \App\Support\Money::format($contractBalance) }}</strong></div>
        <div class="col-6 col-lg-2"><span class="text-muted d-block small">Current payoff</span><strong>{{ \App\Support\Money::format($currentPayoff) }}</strong></div>
        <div class="col-6 col-lg-2"><span class="text-muted d-block small">Unused credit</span><strong>{{ \App\Support\Money::format($clientCredit) }}</strong></div>
    </div>
    <div class="table-responsive mt-4">
        <table class="table table-sm table-striped align-middle mb-0">
            <thead><tr><th>Date / activity</th><th>Invoice</th><th class="text-end">Payment</th><th class="text-end">Fee</th><th class="text-end">Applied to principal</th><th class="text-end">Credit change</th><th class="text-end">Contract balance</th></tr></thead>
            <tbody>
            @forelse($ledgerRows as $row)
                <tr class="{{ $row['reversal'] ? 'text-muted' : '' }}">
                    <td class="text-nowrap">@if($row['payment'])<a class="dashboard-plan-link" href="{{ route('admin.payments.show', $row['payment']) }}">{{ $row['date']->format('M j, Y') }}</a>@else{{ $row['date']->format('M j, Y') }}@endif<span class="d-block text-muted small">{{ $row['description'] }}</span>@if($row['reversal'])<span class="badge text-bg-light">Reversal</span>@endif</td>
                    <td>@foreach($row['invoices'] as $invoice)<a class="dashboard-plan-link" href="{{ route('admin.invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a>{{ !$loop->last ? ', ' : '' }}@endforeach</td>
                    <td class="money-cell">{{ $row['amount'] !== 0 ? \App\Support\Money::format($row['amount']) : '—' }}</td>
                    <td class="money-cell">{{ $row['fee'] !== 0 ? \App\Support\Money::format($row['fee']) : '—' }}</td>
                    <td class="money-cell">{{ $row['principal'] !== 0 ? \App\Support\Money::format($row['principal']) : '—' }}</td>
                    <td class="money-cell">{{ $row['credit'] !== 0 ? ($row['credit'] > 0 ? '+' : '-').\App\Support\Money::format(abs($row['credit'])) : '—' }}</td>
                    <td class="money-cell"><strong>{{ \App\Support\Money::format($row['balance']) }}</strong></td>
                </tr>
            @empty
                <tr><td colspan="7" class="dashboard-empty"><strong>No account activity yet</strong><span>Payments and balance-changing activity will appear here when posted.</span></td></tr>
            @endforelse
            </tbody>
            <tfoot class="table-group-divider">
                <tr class="fw-bold">
                    <th colspan="2">Totals / ending balance</th>
                    <th class="money-cell">{{ \App\Support\Money::format($ledgerPayments) }}</th>
                    <th class="money-cell">{{ \App\Support\Money::format($ledgerFees) }}</th>
                    <th class="money-cell">{{ \App\Support\Money::format($ledgerPrincipal) }}</th>
                    <th class="money-cell">{{ ($ledgerCredit > 0 ? '+' : ($ledgerCredit < 0 ? '-' : '')).\App\Support\Money::format(abs($ledgerCredit)) }}</th>
                    <th class="money-cell">{{ \App\Support\Money::format($ledgerRows->last()['balance'] ?? $contractBalance) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@else<div class="row g-4 mt-3">
    <div class="col-md-3"><article class="admin-summary-card"><span>Contract balance</span><strong>{{\App\Support\Money::format($contractBalance)}}</strong><span class="d-block text-muted fs-6">{{ $contractBalance <= 0 ? 'Paid off' : '('.\App\Support\Money::format($currentPayoff).' payoff)' }}</span></article></div>
    <div class="col-md-3"><article class="admin-summary-card"><span>Account credit</span><strong>{{\App\Support\Money::format($clientCredit)}}</strong><span class="d-block text-muted fs-6">{{ $clientCredit > 0 ? 'Available for open invoices' : 'No unapplied credit' }}</span>@if($clientCredit > 0 && $openInvoiceBalance > 0)<form class="mt-2" method="post" action="{{route('admin.plans.account-credit.apply',$plan)}}" onsubmit="return confirm('Apply available account credit to this plan’s oldest open invoices?');">@csrf<button class="btn btn-sm btn-outline-brand" type="submit">Apply to open invoices</button></form>@endif</article></div>
    <div class="col-md-3"><article class="admin-summary-card"><span>Paid-in value</span><strong>{{\App\Support\Money::format($paidInValue)}}</strong></article></div>
    <div class="col-md-3"><article class="admin-summary-card"><span>Monthly payment</span><strong>{{\App\Support\Money::format($plan->customary_monthly_payment)}}</strong></article></div>
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
        @if($terms = $plan->currentBillingTerms)
        @php
            $issueDate = \Illuminate\Support\Carbon::create(2000, 1, $terms->invoice_day);
            $dueDate = $issueDate->copy()->addDays($terms->due_days_after_issue);
            $stageOneDate = $dueDate->copy()->addDays($terms->stage_one_days_late);
            $dateLabel = function ($date) use ($issueDate) {
                $months = (($date->year - $issueDate->year) * 12) + ($date->month - $issueDate->month);
                return $date->format('jS').($months === 0 ? '' : ($months === 1 ? ' of the following month' : ' of the month '.$months.' months later'));
            };
            $feeLabel = fn ($type, $fixed, $rate, $minimum) => $type->value === 'fixed'
                ? \App\Support\Money::format($fixed)
                : rtrim(rtrim(number_format((float) $rate, 4), '0'), '.').'%'.($minimum > 0 ? ' (minimum '.\App\Support\Money::format($minimum).')' : '');
        @endphp
        <dt class="col-sm-4 col-lg-3">Billing schedule</dt>
        <dd class="col-sm-8 col-lg-9">
            Invoiced on the <strong>{{$dateLabel($issueDate)}}</strong>, must be paid by the <strong>{{$dateLabel($dueDate)}}</strong>.
            After a <strong>{{$terms->grace_days}}-day grace period</strong>, a <strong>{{$feeLabel($terms->stage_one_fee_type,$terms->stage_one_fixed_amount,$terms->stage_one_percentage_rate,$terms->stage_one_minimum_amount)}}</strong> late fee may be assessed on the <strong>{{$dateLabel($stageOneDate)}}</strong> if unpaid.
            @if($terms->stage_two_enabled)
                @php
                    $stageTwoDate = $dueDate->copy()->addDays($terms->stage_two_days_late);
                @endphp
                A second <strong>{{$feeLabel($terms->stage_two_fee_type,$terms->stage_two_fixed_amount,$terms->stage_two_percentage_rate,$terms->stage_two_minimum_amount)}}</strong> late fee may be assessed on the <strong>{{$dateLabel($stageTwoDate)}}</strong> if unpaid.
            @endif
        </dd>
        @endif
        <dt class="col-sm-4 col-lg-3">Contract start</dt><dd class="col-sm-8 col-lg-9">{{ $plan->plan_start_date?->format('M j, Y') }}</dd>
        <dt class="col-sm-4 col-lg-3">Notes</dt><dd class="col-sm-8 col-lg-9">{{ $plan->notes ?: 'None' }}</dd>
        <dt class="col-sm-4 col-lg-3">Estimated payoff</dt><dd class="col-sm-8 col-lg-9"><strong>{{ $estimatedPayoff }}</strong><span class="d-block text-muted small">Based on the current principal balance and payment schedule.</span></dd>

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
    @php
        $sortedInvoices = $plan->invoices->sortByDesc('issue_date');
        $currentInvoices = $sortedInvoices->reject(fn ($invoice) => $invoice->status->value === 'voided');
        $voidedInvoices = $sortedInvoices->filter(fn ($invoice) => $invoice->status->value === 'voided');
    @endphp
    <div class="table-responsive mt-3"><table class="table align-middle"><thead><tr><th>Invoice</th><th>Issued</th><th>Due</th><th>Status</th><th class="text-end">Original amount</th></tr></thead><tbody>
    @forelse($currentInvoices as $invoice)
    <tr><td><a class="dashboard-plan-link" href="{{route('admin.invoices.show',$invoice)}}">{{$invoice->invoice_number}}</a></td><td>{{$invoice->issue_date->format('M j, Y')}}</td><td>{{$invoice->due_date->format('M j, Y')}}</td><td><span class="dashboard-status status-{{str($invoice->status->value)->replace('_','-')}}">{{str($invoice->status->value)->replace('_',' ')->title()}}</span></td><td class="money-cell">{{\App\Support\Money::format($invoice->items->sum('amount'))}}</td></tr>
    @empty
    <tr><td colspan="5" class="dashboard-empty"><strong>No current invoices</strong><span>Review and issue the next invoice when ready.</span></td></tr>
    @endforelse
    @if($voidedInvoices->isNotEmpty())
    <tr><td colspan="5" class="pt-4 pb-2 border-bottom"><strong>Voided / Inactive invoices</strong><span class="d-block text-muted small">Retained for account history; these amounts are not currently due.</span></td></tr>
    @foreach($voidedInvoices as $invoice)
    <tr class="text-muted"><td><a class="dashboard-plan-link" href="{{route('admin.invoices.show',$invoice)}}">{{$invoice->invoice_number}}</a></td><td>{{$invoice->issue_date->format('M j, Y')}}</td><td>{{$invoice->due_date->format('M j, Y')}}</td><td><span class="dashboard-status status-voided">Voided</span></td><td class="money-cell">{{\App\Support\Money::format($invoice->items->sum('amount'))}}</td></tr>
    @endforeach
    @endif
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
        <div><h2 class="mb-1">Plan Amendment history</h2><p class="text-muted mb-0">Current and previous billing terms are retained below.</p></div>
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
                    <td>{{ $plan->billingTerms->sortBy('effective_from')->first()?->is($term) ? 'Original terms' : ($term->reason ?: '—') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

</div>
@endif
</div></section>
@endsection
