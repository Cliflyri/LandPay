@extends('layouts.app')
@section('title',$thread->subject.' | LandPay')
@section('body_class','admin-page')
@section('content')
<section class="admin-section"><div class="container site-container"><div class="admin-heading d-flex flex-wrap justify-content-between align-items-end gap-3"><div><span class="eyebrow eyebrow-dark">{{str($thread->category)->replace('_',' ')->title()}}</span><h1>{{$thread->subject}}</h1>@if($thread->paymentPlan)<p class="mb-0">Plan {{$thread->paymentPlan->plan_number}} &middot; {{$thread->paymentPlan->title}}</p>@endif</div><a class="btn btn-outline-brand" href="{{route('portal.messages.index')}}">All messages</a></div>
@if(session('success'))<div class="alert alert-success mt-4">{{session('success')}}</div>@endif @if($errors->any())<div class="alert alert-danger mt-4">{{$errors->first()}}</div>@endif
<div class="admin-next-card h-auto mt-4"><div class="secure-message-thread">@foreach($thread->messages as $message)@include('shared.secure-message',['portal'=>true])@endforeach</div>@include('shared.secure-message-image-modal')
<form method="post" action="{{route('portal.messages.reply',$thread)}}" enctype="multipart/form-data">@csrf<label class="form-label" for="body">Reply securely</label><textarea class="form-control" id="body" name="body" rows="4" maxlength="10000" required></textarea><label class="form-label mt-3" for="attachment">Image (optional)</label><input class="form-control" id="attachment" name="attachment" type="file" accept="image/jpeg,image/png,.jpg,.jpeg,.png"><div class="form-text">JPG or PNG, up to 10 MB.</div><button class="btn btn-brand mt-3">Send reply</button></form></div></div></section>
@endsection
