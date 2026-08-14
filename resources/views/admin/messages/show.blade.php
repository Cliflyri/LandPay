@extends('layouts.admin')
@section('title',$thread->subject.' | LandPay')
@section('body_class','admin-page')
@section('content')
<section class="admin-section"><div class="container-fluid dashboard-container">
<div class="admin-heading d-flex flex-wrap justify-content-between align-items-end gap-3"><div><span class="eyebrow eyebrow-dark">{{str($thread->category)->replace('_',' ')->title()}}</span><h1>{{$thread->subject}}</h1><p class="mb-0">{{$thread->client->organization_name ?: trim($thread->client->first_name.' '.$thread->client->last_name)}} @if($thread->paymentPlan)&middot; <a href="{{route('admin.plans.show',$thread->paymentPlan)}}">Plan {{$thread->paymentPlan->plan_number}}</a>@endif</p></div><div class="d-flex gap-2"><form method="post" action="{{route('admin.messages.star',$thread)}}">@csrf<button class="btn btn-outline-brand">{{$thread->starred_at?'★ Starred':'☆ Follow up'}}</button></form><a class="btn btn-outline-brand" href="{{route('admin.messages.index')}}">Messages</a><div class="dropdown"><button class="btn btn-outline-brand" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Conversation actions">&#8942;</button><ul class="dropdown-menu dropdown-menu-end"><li><form method="post" action="{{route('admin.messages.destroy',$thread)}}" onsubmit="return confirm('Permanently delete this conversation with {{$thread->client->organization_name ?: trim($thread->client->first_name.' '.$thread->client->last_name)}}: {{$thread->subject}} and all attachments?')">@csrf @method('DELETE')<button class="dropdown-item text-danger" type="submit">Delete conversation</button></form></li></ul></div></div></div>
@if(session('success'))<div class="alert alert-success mt-4">{{session('success')}}</div>@endif
@if(session('error'))<div class="alert alert-danger mt-4">{{session('error')}}</div>@endif
@if($errors->any())<div class="alert alert-danger mt-4">{{$errors->first()}}</div>@endif
<div class="admin-next-card h-auto mt-4">
@include('shared.secure-message-thread', ['portal' => false])
@include('shared.secure-message-image-modal')
<form method="post" action="{{route('admin.messages.reply',$thread)}}" enctype="multipart/form-data">@csrf<label class="form-label" for="body">Reply</label><textarea class="form-control" id="body" name="body" rows="4" maxlength="10000" required>{{old('body')}}</textarea>@include('shared.message-file-picker',['pickerId'=>'admin-reply-files','fixedClientId'=>$thread->client_id])<button class="btn btn-brand mt-3" type="submit">Send reply</button></form>
</div>
<div class="border rounded p-3 mt-4 d-flex flex-wrap justify-content-between align-items-center gap-3"><div><strong>Email notification</strong><div class="text-muted small">Last sent: {{$thread->notification_last_sent_at?->format('M j, Y g:i A') ?? 'Not sent'}} &middot; Reminders: {{$thread->reminder_count}} &middot; Status: {{str($thread->notification_status ?? 'not sent')->title()}}</div></div><form method="post" action="{{route('admin.messages.remind',$thread)}}" onsubmit="return confirm('Send another email reminder that a secure message is waiting?')">@csrf<button class="btn btn-outline-brand" @disabled(blank($thread->client->email))>Send email reminder</button></form></div>
</div></section>
@endsection
