@extends('layouts.app')
@section('title','New secure message | LandPay')
@section('body_class','admin-page')
@section('content')
<section class="admin-section"><div class="container site-container"><div class="admin-heading"><span class="eyebrow eyebrow-dark">Client portal</span><h1>New secure message</h1><p>Send a private message to LandPay. Do not include payment-card or bank-account numbers.</p></div>
@if($errors->any())<div class="alert alert-danger mt-4">{{$errors->first()}}</div>@endif
<form class="admin-next-card h-auto mt-4" method="post" action="{{route('portal.messages.store')}}" enctype="multipart/form-data">@csrf
<label class="form-label" for="subject">Subject</label><input class="form-control" id="subject" name="subject" maxlength="150" value="{{old('subject')}}" required>
<label class="form-label mt-3" for="payment_plan_id">Plan reference (optional)</label><select class="form-select" id="payment_plan_id" name="payment_plan_id"><option value="">No plan reference</option>@foreach($plans as $plan)<option value="{{$plan->id}}" @selected((int)old('payment_plan_id')===$plan->id)>Plan {{$plan->plan_number}} &mdash; {{$plan->title}}</option>@endforeach</select>
<label class="form-label mt-3" for="body">Message</label><textarea class="form-control" id="body" name="body" rows="7" maxlength="10000" required>{{old('body')}}</textarea>
@include('shared.message-file-picker',['pickerId'=>'portal-new-message-files','fixedClientId'=>auth('client')->user()->client_id])
<div class="d-flex gap-2 mt-3"><button class="btn btn-brand">Send secure message</button><a class="btn btn-outline-brand" href="{{route('portal.messages.index')}}">Cancel</a></div></form></div></section>
@endsection
