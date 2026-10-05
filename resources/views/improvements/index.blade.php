@extends('layouts.app')
@section('title','My Improvements | LandPay')
@section('body_class','admin-page')
@section('content')
<section class="admin-section"><div class="container site-container">
<div class="admin-heading d-flex flex-wrap justify-content-between align-items-end gap-3"><div><span class="eyebrow eyebrow-dark">Client portal</span><h1>My Improvements</h1></div><a class="btn btn-outline-brand" href="{{route('portal.dashboard')}}">Back to dashboard</a></div>
@if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{$error}}</li>@endforeach</ul></div>@endif
<details class="admin-next-card" id="notify" @if(request()->boolean('notify') || $errors->any()) open @endif>
<summary class="fw-semibold" style="cursor:pointer;font-size:1.25rem">Notify admin of planned improvement</summary>
<div class="mt-3">
<p>Use this form to notify us of your planned improvement as required by your agreement. You remain responsible for complying with all applicable county guidelines and requirements.</p>
@if($plans->isNotEmpty())
<form method="post" action="{{route('portal.improvements.store')}}" enctype="multipart/form-data">@csrf
<label class="form-label" for="payment_plan_id">Property / plan</label>
@if($plans->count()===1)
<input type="hidden" name="payment_plan_id" value="{{$plans->first()->id}}"><p><strong>{{$plans->first()->plan_number}} &middot; {{$plans->first()->title}}</strong></p>
@else
<select class="form-select mb-3" id="payment_plan_id" name="payment_plan_id" required><option value="">Select a plan</option>@foreach($plans as $plan)<option value="{{$plan->id}}" @selected(old('payment_plan_id')==$plan->id)>{{$plan->plan_number}} &middot; {{$plan->title}}</option>@endforeach</select>
@endif
@include('improvements.form-fields')
<button class="btn btn-brand mt-3">Notify admin</button>
</form>
@else<p class="text-muted mb-0">An active or paused plan is needed to submit a new improvement.</p>@endif
</div>
</details>
<div class="admin-next-card mt-4"><h2>Your Improvements</h2>
@forelse($improvements as $improvement)
<div class="border-bottom py-3"><a href="{{route('portal.improvements.show',$improvement)}}">{{$improvement->title}}</a><small class="d-block text-muted">{{$improvement->paymentPlan->plan_number}} &middot; {{$improvement->created_at->format('M j, Y')}}</small>@include('improvements.badge',['update'=>$improvement->latestUpdate])</div>
@empty<p class="text-muted mb-0">Your planned improvements and progress photos will appear here.</p>@endforelse
<div class="mt-3">{{$improvements->links()}}</div>
</div></div></section>
@endsection
