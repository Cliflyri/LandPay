@extends('layouts.admin')
@section('title','Add improvement | LandPay')
@section('body_class','admin-page')
@section('content')
<section class="admin-section"><div class="container-fluid dashboard-container px-2">
<div class="admin-heading d-flex flex-wrap justify-content-between align-items-end gap-3"><div><span class="eyebrow eyebrow-dark">{{$plan->plan_number}}</span><h1>Add improvement</h1><p class="mb-0">{{$plan->title}}</p></div><a class="btn btn-outline-brand" href="{{route('admin.plans.show',$plan)}}#improvements">Back to plan</a></div>
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{$error}}</li>@endforeach</ul></div>@endif
<div class="admin-next-card">
<p class="small text-muted">Record a notification received by phone, email, text, or in person. Include how and when the client notified you in the description. Receipt will be acknowledged automatically; no email will be sent.</p>
@if($clients->isNotEmpty())
<form method="post" action="{{route('admin.improvements.store',$plan)}}" enctype="multipart/form-data">@csrf
<label class="form-label" for="client_id">Client</label>
@if($clients->count()===1)
<input type="hidden" name="client_id" value="{{$clients->first()->id}}"><p class="fw-semibold">{{$clients->first()->organization_name ?: trim($clients->first()->first_name.' '.$clients->first()->last_name)}}</p>
@else
<select class="form-select mb-3" name="client_id" id="client_id" required><option value="">Select client</option>@foreach($clients as $client)<option value="{{$client->id}}" @selected(old('client_id')==$client->id)>{{$client->organization_name ?: trim($client->first_name.' '.$client->last_name)}}</option>@endforeach</select>
@endif
@include('improvements.form-fields',['descriptionLabel'=>'Improvement description / notification details'])
<button class="btn btn-brand mt-3">Record improvement</button>
</form>
@else<p class="mb-0">This plan has no current clients. Associate a client with the plan before recording an improvement.</p>@endif
</div></div></section>
@endsection
