@extends('layouts.admin')
@section('title','Client announcements | LandPay') @section('body_class','admin-page')
@section('content')
<section class="admin-section"><div class="container-fluid dashboard-container">
<div class="admin-heading d-flex flex-wrap justify-content-between align-items-end gap-3"><div><span class="eyebrow eyebrow-dark">Secure messages</span><h1>Client announcements</h1><p class="mb-0">Portal-wide notices for clients with active portal access.</p></div><div class="d-flex gap-2"><a class="btn btn-outline-brand" href="{{route('admin.messages.index')}}">Secure messages</a><a class="btn btn-sun" href="{{route('admin.announcements.create')}}">New announcement</a></div></div>
@if(session('success'))<div class="alert alert-success mt-4">{{session('success')}}</div>@endif
<div class="dashboard-table-card mt-4"><div class="table-responsive"><table class="table dashboard-table align-middle mb-0"><thead><tr><th>Announcement</th><th>Status</th><th>Schedule</th><th>Recipients</th><th>Created</th></tr></thead><tbody>
@forelse($announcements as $a) @php($status=$a->status()) @php($class=match($status){'active'=>'status-current','scheduled'=>'status-due-soon','draft'=>'status-draft','expired','deactivated','removed'=>'status-closed',default=>'status-draft'})
<tr><td><a class="dashboard-plan-link" href="{{route('admin.announcements.show',$a)}}">{{$a->title}}</a><div class="small text-muted">{{str($a->severity)->title()}}</div></td><td><span class="dashboard-status {{$class}}">{{str($status)->title()}}</span></td><td class="small">{{$a->starts_at?->format('M j, Y g:i A')??'Immediately'}}<br>@if($a->ends_at)through {{$a->ends_at->format('M j, Y g:i A')}}@else No expiration @endif</td><td>{{$a->recipients_count}}</td><td>{{$a->created_at->format('M j, Y')}}</td></tr>
@empty<tr><td colspan="5" class="dashboard-empty"><strong>No client announcements.</strong><span>Create a draft when you need to notify all eligible portal clients.</span></td></tr>@endforelse
</tbody></table></div></div><div class="dashboard-pagination">{{$announcements->links()}}</div></div></section>
@endsection
