@extends('layouts.admin')
@section('title','Reports | LandPay')
@section('body_class','admin-page')
@section('content')
@php
$defs=['payments'=>['Payment report','Payments received and how funds were applied.'],'receivables'=>['Accounts receivable','Open invoice balances organized by age.'],'contracts'=>['Contract balances','Principal, invoice, and credit positions by plan.'],'fees'=>['Fees report','Non-principal fees assessed, waived, collected, and outstanding.'],'client-portals'=>['Client portal status','Invitations, clients without active access, and active portals.'],'client-sms'=>['Client SMS opt-in','Client consent, phone, and delivery status.']];
[$title,$description]=$defs[$report];$query=request()->except('page');
if(in_array($report,['contracts','client-portals','client-sms'])){
    $scope='Current snapshot';
}else{
    $from=$filters['from'] ? \Illuminate\Support\Carbon::parse($filters['from'])->format('M j, Y') : null;
    $to=$filters['to'] ? \Illuminate\Support\Carbon::parse($filters['to'])->format('M j, Y') : null;
    $scope=$from&&$to ? $from.' through '.$to : ($from ? 'From '.$from : ($to ? 'Through '.$to : 'All dates'));
}
if($report==='receivables' && $filters['aging']) $scope.=' · '.$filters['aging'];
if($report==='contracts' && $filters['status']!=='all') $scope.=' · '.str($filters['status'])->title();
if($filters['search']) $scope.=' · Search: '.$filters['search'];
@endphp
<section class="admin-section report-page"><div class="container-fluid dashboard-container">
<div class="admin-heading d-flex flex-wrap justify-content-between align-items-end gap-3 report-heading"><div><span class="eyebrow eyebrow-dark">Administration</span><h1>Reports</h1><p class="mb-1">Financial activity and current account positions.</p><p class="report-scope mb-0"><strong>{{$title}}</strong> · {{$scope}}</p></div><div class="d-flex gap-2 report-actions">@unless($report==='client-portals')<a class="btn btn-outline-brand" href="{{route('admin.reports.export',array_merge(['report'=>$report],$query))}}">Export CSV</a>@endunless<button class="btn btn-outline-brand" onclick="window.print()">Print report</button></div></div>
<nav class="nav nav-tabs settings-tabs mt-4 report-tabs">@foreach($defs as $value=>$def)<a class="nav-link {{$report===$value?'active':''}}" href="{{route('admin.reports.show',['report'=>$value])}}">{{$def[0]}}</a>@endforeach</nav>
<div class="admin-next-card mt-4 report-filter-card"><div class="d-flex justify-content-between gap-3"><div><h2 class="mb-1">{{$title}}</h2><p class="text-muted mb-0">{{$description}}</p></div><small class="text-muted">{{$rows->total()}} records</small></div>
<form class="row g-3 align-items-end mt-1">@if($report==='receivables' && $filters['aging'])<input type="hidden" name="aging" value="{{$filters['aging']}}">@endif
@if(!in_array($report,['contracts','client-portals','client-sms']))<div class="col-sm-6 col-lg-2"><label class="form-label">From</label><input class="form-control" type="date" name="from" value="{{$filters['from']}}"></div><div class="col-sm-6 col-lg-2"><label class="form-label">To</label><input class="form-control" type="date" name="to" value="{{$filters['to']}}"></div>@endif
<div class="col-lg-4"><label class="form-label">Client, plan, APN, or reference</label><input class="form-control" name="search" value="{{$filters['search']}}" placeholder="Search report"></div>
@if($report==='contracts')<div class="col-sm-6 col-lg-2"><label class="form-label">Plan status</label><select class="form-select" name="status">@foreach(['all'=>'All','active'=>'Active','paused'=>'Paused','draft'=>'Draft','terminated'=>'Terminated','closed'=>'Closed'] as $v=>$label)<option value="{{$v}}" @selected($filters['status']===$v)>{{$label}}</option>@endforeach</select></div>@endif
<div class="col-auto"><button class="btn btn-brand">Apply filters</button></div><div class="col-auto"><a class="btn btn-outline-brand" href="{{route('admin.reports.show',['report'=>$report])}}">Clear</a></div></form></div>

@if($report==='client-portals')
<nav class="nav nav-tabs settings-tabs mt-4" id="portal-status">@foreach(['pending'=>'Pending activation','none'=>'No active portal','active'=>'Active portals'] as $value=>$label)<a class="nav-link {{$filters['portal_status']===$value?'active':''}}" href="{{route('admin.reports.show',['report'=>$report,'portal_status'=>$value,'search'=>$filters['search']])}}" data-portal-status-tab>{{$label}} ({{$totals[$label]}})</a>@endforeach</nav>@endif


@if($report==='receivables' && $filters['aging'])<div class="report-aging-state mt-4">Showing {{$filters['aging']}} <a href="{{route('admin.reports.show',array_merge(['report'=>$report],request()->except(['aging','page'])))}}">Show all aging</a></div>@endif

@if($report !== 'client-portals')
<div class="report-summary-grid mt-4">@foreach($totals as $label=>$amount)


@php $cardQuery=request()->except(['aging','page']); $cardActive=$report==='receivables' && $filters['aging']===$label; @endphp
@if($report==='receivables' && $amount>0)<a class="report-summary-card report-summary-link {{$cardActive?'active':''}}" href="{{route('admin.reports.show',array_merge(['report'=>$report],$cardQuery,$cardActive?[]:['aging'=>$label]))}}"><span>{{$label}}</span><strong>{{$report==='client-portals'||$report==='client-sms'?$amount:\App\Support\Money::format($amount)}}</strong></a>
@else<div class="report-summary-card"><span>{{$label}}</span><strong>{{$report==='client-portals'||$report==='client-sms'?$amount:\App\Support\Money::format($amount)}}</strong></div>@endif
@endforeach</div>
@endif
<div class="admin-next-card mt-4 report-table-card"><div class="table-responsive" data-drag-scroll><table class="table table-sm align-middle report-table mb-0">
@if($report==='payments')
<thead><tr><th>Date</th><th>Client / plan</th><th>Method</th>@foreach(['Gross','Fees','Invoices','Principal','Credit'] as $h)<th class="text-end">{{$h}}</th>@endforeach<th>Status</th><th class="text-end">Net</th><th>Reference</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td><a href="{{route('admin.payments.show',$row['model'])}}">{{$row['date']->format('M j, Y')}}</a></td><td>@include('admin.reports.partials.client-plan',['client'=>$row['client'],'plan'=>$row['plan']])</td><td>{{$row['method']}}</td>@foreach(['gross','fees','invoice','principal','credit'] as $m)<td class="money-cell">{{\App\Support\Money::format($row[$m])}}</td>@endforeach<td>{{$row['reversed']?'Reversed':'Posted'}}</td><td class="money-cell">{{\App\Support\Money::format($row['net'])}}</td><td>{{$row['reference']?:'-'}}</td></tr>@empty<tr><td colspan="11" class="report-empty">No payments match these filters.</td></tr>@endforelse
@elseif($report==='receivables')
<thead><tr><th>Client / plan</th><th>Invoice</th><th>Issued</th><th>Due</th><th class="text-end">Original</th><th class="text-end">Paid / credited</th><th class="text-end">Balance</th><th class="text-end">Days overdue</th><th>Aging</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td>@include('admin.reports.partials.client-plan',['client'=>$row['client'],'plan'=>$row['plan']])</td><td><a href="{{route('admin.invoices.show',$row['model'])}}">{{$row['model']->invoice_number}}</a></td><td>{{$row['issue']->format('M j, Y')}}</td><td>{{$row['due']->format('M j, Y')}}</td>@foreach(['amount','paid','balance'] as $m)<td class="money-cell {{$m==='balance'?'balance-due':''}}">{{\App\Support\Money::format($row[$m])}}</td>@endforeach<td class="text-end">{{$row['days']}}</td><td>{{$row['bucket']}}</td></tr>@empty<tr><td colspan="9" class="report-empty">No outstanding invoices match these filters.</td></tr>@endforelse
@elseif($report==='contracts')
<thead><tr><th>Client / plan</th>@foreach(['Purchase price','Documentation','Principal paid','Contract balance','Open invoices','Account credit'] as $h)<th class="text-end">{{$h}}</th>@endforeach<th>Next due</th><th>Status</th><th>Estimated payoff</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td>@include('admin.reports.partials.client-plan',['client'=>$row['client'],'plan'=>$row['model']])</td>@foreach(['purchase','documentation','principal_paid','contract','open','credit'] as $m)<td class="money-cell">{{\App\Support\Money::format($row[$m])}}</td>@endforeach<td>{{$row['next_due']?->format('M j, Y')??'-'}}</td><td>{{$row['status']}}</td><td>{{$row['payoff']}}</td></tr>@empty<tr><td colspan="10" class="report-empty">No contracts match these filters.</td></tr>@endforelse
@elseif($report==='fees')
<thead><tr><th>Date</th><th>Client / plan</th><th>Invoice</th><th>Fee type</th><th>Description</th>@foreach(['Assessed','Waived','Collected','Outstanding'] as $h)<th class="text-end">{{$h}}</th>@endforeach</tr></thead><tbody>
@forelse($rows as $row)<tr><td>{{$row['date']->format('M j, Y')}}</td><td>@include('admin.reports.partials.client-plan',['client'=>$row['client'],'plan'=>$row['plan']])</td><td>@if($row['source_type']==='invoice')<a href="{{route('admin.invoices.show',$row['source'])}}">{{$row['source_label']}}</a>@else<a href="{{route('admin.payments.show',$row['source'])}}">{{$row['source_label']}}</a>@endif</td><td>{{$row['type']}}</td><td>{{$row['description']}}</td>@foreach(['assessed','waived','collected','outstanding'] as $m)<td class="money-cell">{{\App\Support\Money::format($row[$m])}}</td>@endforeach</tr>@empty<tr><td colspan="9" class="report-empty">No fees match these filters.</td></tr>@endforelse
@elseif($report==='client-sms')
<thead><tr><th>Client</th><th>Primary phone</th><th>SMS status</th><th>Consent history</th><th>Delivery totals</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td><a href="{{route('admin.clients.show',$row['client'])}}">{{$row['client']->organization_name ?: trim($row['client']->first_name.' '.$row['client']->last_name)}}</a></td><td>{{$row['client']->primary_phone ?: '-'}}</td><td>{{$row['preference']?->enabled ? 'Opted in' : ($row['preference']?->stopped_at ? 'STOP blocked' : 'Not opted in')}}<br><small>{{$row['preference']?->sms_phone_e164}}</small></td><td><small>In: {{$row['preference']?->opted_in_at?->format('M j, Y g:i A') ?: '-'}} ({{$row['preference']?->opt_in_source ?: '-'}})<br>Out: {{$row['preference']?->opted_out_at?->format('M j, Y g:i A') ?: '-'}} ({{$row['preference']?->opt_out_source ?: '-'}})</small></td><td>{{$row['sent']}} sent / {{$row['failed']}} failed</td></tr>@empty<tr><td colspan="5" class="report-empty">No clients match this search.</td></tr>@endforelse
@else
<thead><tr><th>Client</th><th>Status</th><th>Email</th><th>Sent / created</th><th>Expires</th><th>Invited by</th><th>Last login</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td><a href="{{route('admin.clients.show',$row['client'])}}">{{$row['client']->organization_name ?: trim($row['client']->first_name.' '.$row['client']->last_name)}}</a></td><td>@if($row['group']==='pending'){{$row['invitation']->expires_at->isFuture()?'Open':'Expired'}}@elseif($row['group']==='active')Active @else{{$row['reason']}}@endif</td><td>{{$row['account']?->email ?: $row['invitation']?->email ?: $row['client']->email ?: 'Email required'}}</td><td>{{$row['invitation']?->created_at?->format('M j, Y') ?: $row['account']?->created_at?->format('M j, Y') ?: '-'}}</td><td>{{$row['invitation']?->expires_at?->format('M j, Y g:i A') ?: '-'}}</td><td>{{$row['invitation']?->invitedBy?->name ?: '-'}}</td><td>{{$row['account']?->last_login_at?->format('M j, Y g:i A') ?: ($row['group']==='active'?'Never logged in':'-')}}</td></tr>@empty<tr><td colspan="7" class="report-empty">No clients match this portal status.</td></tr>@endforelse
@endif</tbody></table></div>@if($rows->hasPages())<div class="mt-3 report-pagination">{{$rows->links()}}</div>@endif</div>
</div></section>
@endsection
@push('scripts')
<script>
document.querySelectorAll('[data-portal-status-tab]').forEach(function(link){
    link.addEventListener('click',function(){sessionStorage.setItem('portalStatusScroll',String(window.scrollY));});
});
var portalStatusScroll=sessionStorage.getItem('portalStatusScroll');
if(portalStatusScroll!==null){sessionStorage.removeItem('portalStatusScroll');requestAnimationFrame(function(){window.scrollTo(0,Number(portalStatusScroll));});}
</script>
@endpush
