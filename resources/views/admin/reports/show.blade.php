@extends('layouts.admin')
@section('title','Reports | LandPay')
@section('body_class','admin-page')
@section('content')
@php
$defs=['payments'=>['Payment report','Payments received and how funds were applied.'],'receivables'=>['Accounts receivable','Open invoice balances organized by age.'],'contracts'=>['Contract balances','Principal, invoice, and credit positions by plan.'],'fees'=>['Fees report','Non-principal fees assessed, waived, collected, and outstanding.']];
[$title,$description]=$defs[$report];$query=request()->except('page');
if($report==='contracts'){
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
<div class="admin-heading d-flex flex-wrap justify-content-between align-items-end gap-3 report-heading"><div><span class="eyebrow eyebrow-dark">Administration</span><h1>Reports</h1><p class="mb-1">Financial activity and current account positions.</p><p class="report-scope mb-0"><strong>{{$title}}</strong> · {{$scope}}</p></div><div class="d-flex gap-2 report-actions"><a class="btn btn-outline-brand" href="{{route('admin.reports.export',array_merge(['report'=>$report],$query))}}">Export CSV</a><button class="btn btn-outline-brand" onclick="window.print()">Print report</button></div></div>
<nav class="nav nav-tabs settings-tabs mt-4 report-tabs">@foreach($defs as $value=>$def)<a class="nav-link {{$report===$value?'active':''}}" href="{{route('admin.reports.show',['report'=>$value])}}">{{$def[0]}}</a>@endforeach</nav>
<div class="admin-next-card mt-4 report-filter-card"><div class="d-flex justify-content-between gap-3"><div><h2 class="mb-1">{{$title}}</h2><p class="text-muted mb-0">{{$description}}</p></div><small class="text-muted">{{$rows->total()}} records</small></div>
<form class="row g-3 align-items-end mt-1">@if($report==='receivables' && $filters['aging'])<input type="hidden" name="aging" value="{{$filters['aging']}}">@endif
@if($report!=='contracts')<div class="col-sm-6 col-lg-2"><label class="form-label">From</label><input class="form-control" type="date" name="from" value="{{$filters['from']}}"></div><div class="col-sm-6 col-lg-2"><label class="form-label">To</label><input class="form-control" type="date" name="to" value="{{$filters['to']}}"></div>@endif
<div class="col-lg-4"><label class="form-label">Client, plan, APN, or reference</label><input class="form-control" name="search" value="{{$filters['search']}}" placeholder="Search report"></div>
@if($report==='contracts')<div class="col-sm-6 col-lg-2"><label class="form-label">Plan status</label><select class="form-select" name="status">@foreach(['all'=>'All','active'=>'Active','paused'=>'Paused','draft'=>'Draft','terminated'=>'Terminated','closed'=>'Closed'] as $v=>$label)<option value="{{$v}}" @selected($filters['status']===$v)>{{$label}}</option>@endforeach</select></div>@endif
<div class="col-auto"><button class="btn btn-brand">Apply filters</button></div><div class="col-auto"><a class="btn btn-outline-brand" href="{{route('admin.reports.show',['report'=>$report])}}">Clear</a></div></form></div>
@if($report==='receivables' && $filters['aging'])<div class="report-aging-state mt-4">Showing {{$filters['aging']}} <a href="{{route('admin.reports.show',array_merge(['report'=>$report],request()->except(['aging','page'])))}}">Show all aging</a></div>@endif
<div class="report-summary-grid mt-4">@foreach($totals as $label=>$amount)
@php $cardQuery=request()->except(['aging','page']); $cardActive=$report==='receivables' && $filters['aging']===$label; @endphp
@if($report==='receivables' && $amount>0)<a class="report-summary-card report-summary-link {{$cardActive?'active':''}}" href="{{route('admin.reports.show',array_merge(['report'=>$report],$cardQuery,$cardActive?[]:['aging'=>$label]))}}"><span>{{$label}}</span><strong>{{\App\Support\Money::format($amount)}}</strong></a>
@else<div class="report-summary-card"><span>{{$label}}</span><strong>{{\App\Support\Money::format($amount)}}</strong></div>@endif
@endforeach</div>
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
@else
<thead><tr><th>Date</th><th>Client / plan</th><th>Invoice</th><th>Fee type</th><th>Description</th>@foreach(['Assessed','Waived','Collected','Outstanding'] as $h)<th class="text-end">{{$h}}</th>@endforeach</tr></thead><tbody>
@forelse($rows as $row)<tr><td>{{$row['date']->format('M j, Y')}}</td><td>@include('admin.reports.partials.client-plan',['client'=>$row['client'],'plan'=>$row['plan']])</td><td>@if($row['source_type']==='invoice')<a href="{{route('admin.invoices.show',$row['source'])}}">{{$row['source_label']}}</a>@else<a href="{{route('admin.payments.show',$row['source'])}}">{{$row['source_label']}}</a>@endif</td><td>{{$row['type']}}</td><td>{{$row['description']}}</td>@foreach(['assessed','waived','collected','outstanding'] as $m)<td class="money-cell">{{\App\Support\Money::format($row[$m])}}</td>@endforeach</tr>@empty<tr><td colspan="9" class="report-empty">No fees match these filters.</td></tr>@endforelse
@endif
</tbody></table></div>@if($rows->hasPages())<div class="mt-3 report-pagination">{{$rows->links()}}</div>@endif</div>
</div></section>
@endsection
