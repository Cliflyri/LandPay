@extends('layouts.admin')
@section('title','Clients | LandPay')
@section('body_class','admin-page')
@section('content')
<section class="admin-section"><div class="container-fluid dashboard-container px-2">
<div class="admin-heading d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3">
    <div><h1>Clients</h1><span class="eyebrow eyebrow-dark">Administration</span></div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <form method="get" action="{{route('admin.clients.index')}}">
            <input type="hidden" name="plans" value="{{request('plans','active')}}">
            <label class="visually-hidden" for="clients">Clients shown</label>
            <select class="form-select" id="clients" name="clients" onchange="this.form.submit()">
                <option value="active" @selected($clientStatus==='active')>Active clients</option>
                <option value="archived" @selected($clientStatus==='archived')>Archived clients</option>
                <option value="all" @selected($clientStatus==='all')>All clients</option>
            </select>
        </form>
        <form method="get" action="{{route('admin.clients.index')}}">
            <input type="hidden" name="clients" value="{{$clientStatus}}">
            <label class="visually-hidden" for="plans">Plans shown</label>
            <select class="form-select" id="plans" name="plans" onchange="this.form.submit()">
                <option value="active" @selected(!$showAllPlans)>Active plans</option>
                <option value="all" @selected($showAllPlans)>All plans</option>
            </select>
        </form>
        <a class="btn btn-sun" href="{{ route('admin.clients.create') }}">Add client</a>
    </div>
</div>
@if(session('success'))<div class="alert alert-success mt-4">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger mt-4">{{ $errors->first() }}</div>@endif
<div class="dashboard-table-card mt-4"><div class="table-responsive"><table class="table dashboard-table client-plan-table align-middle mb-0">
<thead><tr><th><span class="visually-hidden">Actions</span></th><th>Name</th><th>APN / Plan #</th><th class="text-end">Contract Balance</th><th class="text-end">Paid-in Value</th><th>Email</th><th>Last Login</th><th>Private Notes</th></tr></thead>
<tbody>
@forelse($rows as $row)
@php($client=$row['client'])
@php($plan=$row['plan'])
@php($name=$client->organization_name ?: collect([$client->first_name,$client->middle_name,$client->last_name])->filter()->join(' '))
<tr class="dashboard-plan-row">
<td class="dashboard-actions-menu">
    <div class="d-flex align-items-center gap-1">
        <a class="client-edit-icon" href="{{route('admin.clients.edit',$client)}}" aria-label="Edit {{$name}}" title="Edit client"><span aria-hidden="true">&#9998;</span></a>
        <div class="dropdown"><button class="btn btn-sm btn-light dashboard-menu-button" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false" aria-label="Actions for {{$name}}"><span aria-hidden="true">&#8942;</span></button>
            <ul class="dropdown-menu dropdown-menu-start">
                <li><a class="dropdown-item" href="{{route('admin.clients.show',$client)}}">View client</a></li>
                @if($plan)
                    <li><a class="dropdown-item" href="{{route('admin.plans.show',$plan)}}">View plan</a></li>
                    <li><a class="dropdown-item" href="{{route('admin.plans.payments.create',$plan)}}">Enter payment</a></li>
                @endif
                <li><hr class="dropdown-divider"></li>
                @if($client->portalAccount?->enabled)
                    <li><form method="post" action="{{route('admin.portal-access.store',$client)}}">@csrf<button class="dropdown-item" type="submit">Open client portal</button></form></li>
                @else
                    <li><span class="dropdown-item disabled" aria-disabled="true">Portal not active</span></li>
                @endif
                <li><hr class="dropdown-divider"></li>
                @if($client->archived_at)
                    <li><form method="post" action="{{route('admin.clients.restore',$client)}}">@csrf<button class="dropdown-item" type="submit">Restore client</button></form></li>
                @else
                    <li><form method="post" action="{{route('admin.clients.archive',$client)}}" onsubmit="return confirm('Archive this client? Historical records will remain available.');">@csrf<button class="dropdown-item text-danger" type="submit">Archive client</button></form></li>
                @endif
            </ul>
        </div>
    </div>
</td>
<td class="text-nowrap"><a class="dashboard-client-link" href="{{route('admin.clients.show',$client)}}">{{$name}}</a>@if($client->archived_at)<span class="dashboard-status status-closed ms-2">Archived</span>@endif</td>
<td>@if($plan)<a class="dashboard-plan-link text-nowrap" href="{{route('admin.plans.show',$plan)}}">{{$plan->apn ?: $plan->plan_number}}</a>@else<span class="muted-value">&mdash;</span>@endif</td>

<td class="money-cell">
    @if($plan)
        <span style="font-size: 1rem;">{{ \App\Support\Money::format($row['current_payoff']) }}</span> <span class="text-muted">payoff</span>
        <span class="d-block text-muted">
            ({{ \App\Support\Money::format($row['contract_balance']) }} balance)
        </span>
    @else
        <span class="muted-value">&mdash;</span>
    @endif
</td>
<td class="money-cell">@if($plan){{\App\Support\Money::format($row['paid_in_value'])}}@else<span class="muted-value">&mdash;</span>@endif</td>
<td>@if($client->email)<a class="dashboard-email" href="mailto:{{$client->email}}">{{$client->email}}</a>@else<span class="muted-value">Not provided</span>@endif</td>
<td class="text-nowrap"><span class="muted-value">{{$client->portalAccount?->last_login_at?->format('M j, Y g:i A') ?? 'Never'}}</span></td>
<td class="client-private-notes" @if($client->notes) title="{{$client->notes}}" @endif>{{$client->notes ? \Illuminate\Support\Str::limit($client->notes,80) : ''}}</td>
</tr>
@empty
<tr><td colspan="8" class="dashboard-empty"><strong>No clients yet.</strong><span>Add a client to begin managing payment plans.</span></td></tr>
@endforelse
</tbody></table></div></div>
<div class="dashboard-pagination">{{$rows->links()}}</div>
</div></section>
@endsection
