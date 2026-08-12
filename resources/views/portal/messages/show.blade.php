@extends('layouts.app')
@section('title',$thread->subject.' | LandPay')
@section('body_class','admin-page')
@section('content')
<section class="admin-section"><div class="container site-container">
<div class="admin-heading d-flex flex-wrap justify-content-between align-items-end gap-3"><div><span class="eyebrow eyebrow-dark">{{str($thread->category)->replace('_',' ')->title()}}</span><h1>{{$thread->subject}}</h1>@if($thread->paymentPlan)<p class="mb-0">Plan {{$thread->paymentPlan->plan_number}} &middot; {{$thread->paymentPlan->title}}</p>@endif</div><a class="btn btn-outline-brand" href="{{route('portal.messages.index')}}">All messages</a></div>
@if(session('success'))<div class="alert alert-success mt-4">{{session('success')}}</div>@endif
@if($errors->any())<div class="alert alert-danger mt-4">{{$errors->first()}}</div>@endif
<div class="admin-next-card h-auto mt-4">
@foreach($thread->messages as $message)<article class="border-bottom pb-3 mb-3"><div class="d-flex justify-content-between gap-3"><strong>{{$message->sender_type==='admin'?'LandPay':'You'}}</strong><span class="text-muted">{{$message->created_at->format('M j, Y g:i A')}}</span></div><div style="white-space:pre-wrap">{{$message->body}}</div>@if($message->attachment_path)<p class="mt-3 mb-0"><a class="btn btn-sm btn-outline-brand" href="{{route('portal.messages.download',[$thread,$message])}}">Download {{$message->attachment_name}}</a></p>@endif</article>@endforeach
<form method="post" action="{{route('portal.messages.reply',$thread)}}">@csrf<label class="form-label" for="body">Reply securely</label><textarea class="form-control" id="body" name="body" rows="4" maxlength="10000" required></textarea><button class="btn btn-brand mt-3">Send reply</button></form>
</div></div></section>
@endsection
