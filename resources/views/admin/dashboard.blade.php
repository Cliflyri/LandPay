@extends('layouts.admin')
@section('title', 'Payment plan dashboard | LandPay')
@section('body_class', 'admin-page')
@section('content')
<section class="admin-section dashboard-section">
    <div class="container-fluid dashboard-container px-2">
        <div class="admin-heading dashboard-heading">
            <div>
                <span class="eyebrow eyebrow-dark">Administrator dashboard</span>
                <h1>Payment plans</h1>
                <p>Review account balances, billing status, and automated reminder activity.</p>
            </div>
            <div class="dashboard-actions">
                <a class="btn btn-outline-brand" href="{{ route('admin.clients.index') }}">Clients</a>
                <a class="btn btn-outline-brand" href="{{ route('admin.settings.index') }}">Settings</a>
                <a class="btn btn-sun" href="{{ route('admin.plans.create') }}">New payment plan</a>
                <form method="POST" action="{{ route('admin.logout') }}">@csrf<button class="btn btn-outline-brand">Sign out</button></form>
            </div>
        </div>

        <div class="dashboard-summary" aria-label="Portfolio summary">
            <span><strong>{{ $planCount }}</strong> active plans</span>
            <span><strong>{{ $clientCount }}</strong> clients</span>
            <span><strong>{{ $openInvoiceCount }}</strong> open invoices</span>
        </div>
        @if($notices->isNotEmpty())<div class="admin-next-card mb-4"><div class="d-flex justify-content-between align-items-center"><h2>Notices</h2><span class="dashboard-status status-due">{{$notices->count()}} open</span></div>@foreach($notices as $notice)<div class="amendment-entry"><div class="amendment-entry-heading"><div><strong>{{$notice->title}}</strong>@if($notice->type === 'online_payment_received' && $notice->client && $notice->paymentIntent?->payment)<p class="mb-0"><a href="{{route('admin.clients.show',$notice->client)}}">{{trim($notice->client->first_name.' '. $notice->client->last_name)}}</a> paid <a href="{{route('admin.payments.show',$notice->paymentIntent->payment)}}">{{\App\Support\Money::format($notice->paymentIntent->amount)}}</a> by {{str($notice->paymentIntent->provider)->title()}} on {{$notice->paymentIntent->payment->received_date->format('M j, Y')}}. Payment posted successfully.</p>@else<p class="mb-0">{{$notice->message}}</p>@endif @if($notice->paymentIntent?->overpayment_disposition)<p class="mb-0 mt-1"><strong>Client overpayment instruction:</strong> {{$notice->paymentIntent->overpayment_disposition === 'next_invoice_credit' ? 'Keep extra as account credit.' : 'Apply extra to principal.'}}</p>@endif</div><div class="d-flex align-items-start gap-2 flex-shrink-0">@if($notice->changeRequest)<a class="btn btn-sm btn-brand" href="{{route('admin.client-change-requests.show',$notice->changeRequest)}}">Review</a>@elseif($notice->paymentIntent?->status === 'announced')<a class="btn btn-sm btn-brand" href="{{route('admin.payment-intents.receive',$notice->paymentIntent)}}">Receive payment</a>@elseif($notice->client)<a class="btn btn-sm btn-outline-brand" href="{{route('admin.clients.show',$notice->client)}}">Open client</a>@endif<form method="post" action="{{route('admin.notices.dismiss',$notice)}}">@csrf<button class="btn btn-sm btn-outline-brand">Dismiss</button></form></div></div></div>@endforeach</div>@endif


        <div class="dashboard-table-card">
            <div class="table-responsive">
                <table class="table dashboard-table align-middle mb-0">
                    <thead><tr>
                        <th scope="col"><span class="visually-hidden">Actions</span></th><th scope="col">Client</th><th scope="col">APN / Plan #</th><th scope="col">Status</th><th scope="col" class="text-center dashboard-monthly-column">Monthly</th>
                        <th scope="col" class="text-end">Contract balance</th><th scope="col">Current Due</th>
                        <th scope="col">Last reminder</th><th scope="col">Next reminder</th><th scope="col">Next invoice</th><th scope="col">Email</th>
                    </tr></thead>
                    <tbody>
                    @forelse($plans as $row)
                        @php($statusClass = 'status-' . str($row['operational_status'])->slug())
                        <tr class="dashboard-plan-row">
                            <td class="dashboard-actions-menu"><div class="dropdown"><button class="btn btn-sm btn-light dashboard-menu-button" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false" aria-label="Actions for {{ $row['client_name'] }}"><span aria-hidden="true">&#8942;</span></button><ul class="dropdown-menu dropdown-menu-start">
                                <li><a class="dropdown-item" href="{{route('admin.plans.show',$row['plan'])}}">View plan</a></li>
                                @if($row['balance_invoice'])<li><a class="dropdown-item" href="{{route('admin.invoices.show',$row['balance_invoice'])}}">View invoice</a></li>@endif
                                <li><a class="dropdown-item" href="{{route('admin.plans.payments.create',$row['plan'])}}">Enter payment</a></li>
                                @if($row['primary_client']?->portalAccount?->enabled)<li><form method="post" action="{{route('admin.portal-access.store',$row['primary_client'])}}">@csrf<button class="dropdown-item" type="submit">Open client portal</button></form></li>@else<li><span class="dropdown-item disabled" aria-disabled="true">Portal not active</span></li>@endif
                                <li><a class="dropdown-item" href="{{route('admin.plans.invoices.create',$row['plan'])}}">Review next invoice</a></li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('admin.plans.invoices.manual.create', $row['plan']) }}">
                                        Create invoice
                                    </a>
                                </li>
                                <li><a class="dropdown-item" href="{{route('admin.plans.edit',$row['plan'])}}">Edit plan</a></li>
                                @if(in_array($row['plan']->status, ['active','paused'], true))<li><a class="dropdown-item" href="{{route('admin.plans.show',$row['plan'])}}#pause-plan-controls">{{$row['plan']->status === 'paused' ? 'Resume plan' : 'Pause plan'}}</a></li>@endif
                                @if($row['balance_invoice'] && $row['email'])<li><hr class="dropdown-divider"></li><li><form method="post" action="{{route('admin.invoices.reminders.store',$row['balance_invoice'])}}" onsubmit="return confirm('Send a payment reminder to {{$row['email']}}?');">@csrf<button class="dropdown-item" type="submit">Send reminder</button></form></li>@endif
                            </ul></div></td>
                            <td class="text-nowrap">@if($row['primary_client'])<a class="dashboard-client-link" href="{{ route('admin.clients.show', $row['primary_client']) }}">{{ $row['client_name'] }}</a>@else<span class="muted-value">{{ $row['client_name'] }}</span>@endif @if($row['co_client_count'] > 0)<span class="co-client-count" title="{{ $row['co_client_count'] }} co-client(s)">+{{ $row['co_client_count'] }}</span>@endif</td>
                            <td class="text-nowrap">
                                
                            <a class="dashboard-plan-link" href="{{ route('admin.plans.show', $row['plan']) }}">{{ $row['plan']->plan_number }}</a></td>
                            
<td>
    @if ($row['plan']->status === 'paused')
        <a
            href="{{ route('admin.plans.show', $row['plan']) }}#pause-plan-controls"
            class="text-decoration-none"
            title="Click to resume this payment plan"
        >
            <span class="dashboard-status status-current">
                ❚❚ Paused
            </span>
        </a>
    @else
        <span class="dashboard-status {{ $statusClass }}">
            {{ $row['operational_status'] }}
        </span>
    @endif

@if ($row['plan']->accelerated_testing_mode)
    <div class="mt-1">
            <a
            href="{{ route('admin.plans.edit', $row['plan']) }}#accelerated_testing_mode"
            class="text-decoration-none"
            title="Testing mode enabled — click to edit"
            >
        <span
            class="dashboard-status status-due"
            title="Accelerated testing mode (daily billing cycle)"
        >
            TEST: Daily billing
        </span>
        </a>
    </div>
@endif


</td>

<td class="money-cell dashboard-monthly-column text-center" title="Monthly payment (principal portion)">
    <div class="fw-bold fs-6">
        {{ \App\Support\Money::format($row['monthly_total']) }}
    </div>

    <div class="text-muted small">
        ({{ \App\Support\Money::format($row['monthly_principal']) }} principal)
    </div>
</td>

                            <td class="money-cell">{{ \App\Support\Money::format($row['contract_balance']) }}<span class="d-block text-muted" style="font-size: 1rem;">({{ \App\Support\Money::format($row['current_payoff']) }} payoff)</span></td>
                            <td class="current-balance-cell {{$row['current_balance_due'] > 0 ? 'balance-due' : ''}}">
                                <strong class="current-balance-total">{{\App\Support\Money::format($row['current_balance_due'])}}</strong>
                                @forelse($row['current_balance_items'] as $item)
                                    @php($dueDate=$item['due_date'])
                                    @php($dueLabel=$dueDate->year === now()->year ? $dueDate->format('n/j') : $dueDate->format('n/j/Y'))
                                    @php($isHidden=$loop->index >= 3)
                                    @php($isOverdue=$dueDate->isBefore(today()))
                                    @if($item['invoice'])
                                        <a class="current-balance-line {{$isOverdue ? 'is-overdue' : ''}} {{$isHidden ? 'd-none' : ''}}" @if($isHidden)data-current-balance-hidden="balance-items-{{$row['plan']->id}}"@endif href="{{route('admin.invoices.show',$item['invoice'])}}" aria-label="{{\App\Support\Money::format($item['amount'])}} due {{$dueDate->format('F j, Y')}} on invoice {{$item['label']}}">{{\App\Support\Money::format($item['amount'])}} due {{$dueLabel}}</a>
                                    @else
                                        <span class="current-balance-line {{$isOverdue ? 'is-overdue' : ''}} {{$isHidden ? 'd-none' : ''}}" @if($isHidden)data-current-balance-hidden="balance-items-{{$row['plan']->id}}"@endif title="First payment due {{$dueDate->format('F j, Y')}}">{{\App\Support\Money::format($item['amount'])}} due {{$dueLabel}} <span class="visually-hidden">(first payment)</span></span>
                                    @endif
                                @empty
                                    <span class="current-balance-empty">Nothing due</span>
                                @endforelse
                                @if($row['current_balance_items']->count() > 3)
                                    @php($hiddenCount=$row['current_balance_items']->count()-3)
                                    <button class="current-balance-more" type="button" data-current-balance-toggle="balance-items-{{$row['plan']->id}}" data-collapsed-label="+ {{$hiddenCount}} more {{$hiddenCount===1?'invoice':'invoices'}}" data-expanded-label="Show fewer" aria-expanded="false">+ {{$hiddenCount}} more {{$hiddenCount===1?'invoice':'invoices'}}</button>
                                @endif
                            </td>
                            <td><span class="muted-value">{{ $row['reminder_last_sent'] ?? 'Never' }}</span></td>
                            <td><span class="muted-value">{{ $row['reminder_next_send'] ?? 'Not scheduled' }}</span></td>
                            <td><span class="muted-value">{{ $row['next_invoice'] }}</span></td>
                            <td>@if($row['email'])<a class="dashboard-email" href="mailto:{{ $row['email'] }}">{{ $row['email'] }}</a>@else<span class="muted-value">No email</span>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="dashboard-empty"><strong>No payment plans yet.</strong><span>Create a payment plan to begin tracking client balances and billing.</span></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="dashboard-pagination">{{ $plans->links() }}</div>
    </div>
</section>
@endsection
