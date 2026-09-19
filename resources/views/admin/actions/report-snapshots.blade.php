@extends('layouts.admin')
@section('content')
<section class='admin-section'><div class='container-fluid dashboard-container'>
<div class='admin-heading'><div><span class='eyebrow eyebrow-dark'>Admin Actions</span><h1>Report Snapshots</h1><p>Create and download protected CSV snapshots of every report.</p></div></div>
@if(session('status'))<div class='alert alert-success'>{{session('status')}}</div>@endif
@if($errors->any())<div class='alert alert-danger'>{{$errors->first()}}</div>@endif
@php($latest=$snapshots[0]??null)
<div class='admin-next-card mt-4 py-3'><div class='d-flex flex-wrap gap-4'><span class='badge {{$settings['enabled']?'text-bg-success':'text-bg-secondary'}}'>Schedule {{$settings['enabled']?'enabled':'disabled'}}</span><span><strong>Last:</strong> {{$latest?\Carbon\Carbon::parse($latest['created_at'])->format('M j, Y g:i A'):'None'}}</span><span><strong>Next:</strong> {{$nextRun?$nextRun->format('M j, Y g:i A'):'Not scheduled'}}</span></div></div>
<nav class='nav nav-tabs settings-tabs mt-4'>@foreach(['overview'=>'Overview','snapshots'=>'Snapshots','schedule'=>'Schedule'] as $value=>$label)<a class='nav-link {{$tab===$value?'active':''}}' href='{{route('admin.report-snapshots.index',['tab'=>$value])}}'>{{$label}}</a>@endforeach</nav>
@if($tab==='overview')
<div class='row g-4 mt-1'><div class='col-lg-7'><div class='admin-next-card h-100'><h2>Create snapshot</h2><p>Generate current CSV files for all six reports without changing the next scheduled run.</p><form method='post' action='{{route('admin.report-snapshots.store')}}'>@csrf<button class='btn btn-brand'>Create snapshot now</button></form></div></div>
<div class='col-lg-5'><div class='admin-next-card h-100'><h2>Latest snapshot</h2>@if($latest)<p>{{\Carbon\Carbon::parse($latest['created_at'])->format('M j, Y g:i A')}} &middot; {{count($latest['files'])}} reports</p><a class='btn btn-outline-brand' href='{{route('admin.report-snapshots.download-zip',$latest['id'])}}'>Download all (.zip)</a>@else<p class='text-muted'>No snapshots yet.</p>@endif</div></div></div>
@elseif($tab==='snapshots')
<div class='admin-next-card mt-4'><h2>Available snapshots</h2>@forelse($snapshots as $snapshot)<div class='border rounded p-3 mb-3'><div class='d-flex flex-wrap justify-content-between gap-3'><div><strong>{{\Carbon\Carbon::parse($snapshot['created_at'])->format('M j, Y g:i A')}}</strong> <span class='badge text-bg-light'>{{ucfirst($snapshot['trigger'])}}</span><div class='small text-muted'>{{count($snapshot['files'])}} reports &middot; {{number_format($snapshot['size']/1024,1)}} KB</div></div><a class='btn btn-sm btn-brand' href='{{route('admin.report-snapshots.download-zip',$snapshot['id'])}}'>Download all</a></div><div class='d-flex flex-wrap gap-3 mt-3'>@foreach($snapshot['files'] as $file)<a href='{{route('admin.report-snapshots.download',[$snapshot['id'],$file['name']])}}'>{{str($file['name'])->beforeLast('.')->replace('-',' ')->title()}}</a>@endforeach</div></div>@empty<p class='text-muted'>No snapshots yet.</p>@endforelse</div>
@else
@include('admin.actions.report-snapshot-schedule')
@endif
</div></section>
@endsection
