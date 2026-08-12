@extends('layouts.app')
@section('title','Messages & documents | LandPay')
@section('body_class','admin-page')
@section('content')
<section class="admin-section"><div class="container site-container"><div class="admin-heading d-flex flex-wrap justify-content-between align-items-end gap-3"><div><span class="eyebrow eyebrow-dark">Client portal</span><h1>Messages &amp; documents</h1></div><div class="d-flex gap-2"><a class="btn btn-sun" href="{{route('portal.messages.create')}}">New secure message</a><a class="btn btn-outline-brand" href="{{route('portal.dashboard')}}">Dashboard</a></div></div>
<div class="admin-next-card h-auto mt-4"><div class="list-group list-group-flush">@forelse($threads as $thread)<a class="list-group-item list-group-item-action px-0 py-3" href="{{route('portal.messages.show',$thread)}}"><div class="d-flex justify-content-between gap-3"><strong>{{$thread->subject}}</strong><span class="text-muted">{{$thread->latest_message_at->format('M j, Y')}}</span></div><div class="small text-muted">{{str($thread->category)->replace('_',' ')->title()}} @if($thread->paymentPlan)&middot; Plan {{$thread->paymentPlan->plan_number}}@endif @if($thread->unread_count)<span class="dashboard-status status-due ms-2">Unread</span>@endif</div></a>@empty<p class="mb-0">No secure messages yet.</p>@endforelse</div></div>{{$threads->links()}}</div></section>
@endsection
