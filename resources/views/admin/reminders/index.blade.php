@extends('layouts.admin')
@section('title','Admin Reminders | LandPay')
@section('body_class','admin-page')
@section('content')
<section class="admin-section"><div class="container-fluid dashboard-container">
<div class="admin-heading"><div><span class="eyebrow eyebrow-dark">Admin Actions</span><h1>Admin Reminders</h1><p class="mb-0">Manage monthly operational reminders shown on the admin dashboard.</p></div><div><a class="btn btn-brand" href="{{route('admin.reminders.create')}}">Add Reminder</a> <a class="btn btn-outline-brand" href="{{route('admin.actions.index')}}">Back to Admin Actions</a></div></div>
@if(session('success'))<div class="alert alert-success mt-4">{{session('success')}}</div>@endif
<div class="admin-next-card mt-4"><div class="table-responsive"><table class="table align-middle mb-0">
<thead><tr><th>Reminder</th><th>Monthly Schedule</th><th>Destination</th><th>Email</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($reminders as $reminder)
<tr><td><strong>{{$reminder->title}}</strong>@if($reminder->message)<div class="small text-muted">{{str($reminder->message)->limit(100)}}</div>@endif</td>
<td>Day {{$reminder->day_of_month}} at {{\Illuminate\Support\Carbon::createFromFormat('H:i:s',$reminder->display_time)->format('g:i A')}}</td>
<td>{{$reminder->destinationLabel()}}</td><td>{{$reminder->send_email?'Requested':'Dashboard only'}}</td>
<td><span class="dashboard-status {{$reminder->active?'status-current':'status-draft'}}">{{$reminder->active?'Active':'Disabled'}}</span></td>
<td class="text-end text-nowrap"><a class="btn btn-sm btn-outline-brand" href="{{route('admin.reminders.edit',$reminder)}}">Edit</a>
<form class="d-inline" method="post" action="{{route('admin.reminders.destroy',$reminder)}}" onsubmit="return confirm('Delete this reminder and its history?');">@csrf @method('delete')<button class="btn btn-sm btn-outline-danger">Delete</button></form></td></tr>
@empty<tr><td colspan="6">No admin reminders have been created.</td></tr>@endforelse
</tbody></table></div></div>
</div></section>
@endsection
