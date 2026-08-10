@extends('layouts.admin')
@section('title','Client | LandPay')
@section('body_class','admin-page')
@section('content')
@php($name=$client->organization_name ?: collect([$client->first_name,$client->middle_name,$client->last_name])->filter()->join(' '))
@php($cityLine=collect([$client->city,$client->state_region,$client->postal_code])->filter()->join(', '))
<section class="admin-section"><div class="container-fluid dashboard-container">
<div class="admin-heading d-flex flex-wrap justify-content-between align-items-end gap-3"><div><span class="eyebrow eyebrow-dark">Client record</span><h1>{{$name}}</h1>@if($client->preferred_name)<p class="mb-0">Preferred name: {{$client->preferred_name}}</p>@endif</div><div class="d-flex gap-2"><a class="btn btn-outline-brand" href="{{route('admin.clients.edit',$client)}}">Edit client</a><a class="btn btn-sun" href="{{route('admin.plans.create',['client'=>$client->id])}}">Create plan</a></div></div>
<div class="row g-4 mt-2"><div class="col-lg-6"><div class="admin-next-card h-100"><h2>Contact information</h2><dl class="row mb-0 mt-3"><dt class="col-sm-4">Email</dt><dd class="col-sm-8">@if($client->email)<a href="mailto:{{$client->email}}">{{$client->email}}</a>@else Not provided @endif</dd><dt class="col-sm-4">Primary phone</dt><dd class="col-sm-8">{{$client->primary_phone ?: 'Not provided'}}</dd><dt class="col-sm-4">Secondary phone</dt><dd class="col-sm-8">{{$client->secondary_phone ?: 'Not provided'}}</dd><dt class="col-sm-4">Address</dt><dd class="col-sm-8">@if($client->address_line_1 || $client->address_line_2 || $cityLine)<address class="mb-0">{{$client->address_line_1}}@if($client->address_line_2)<br>{{$client->address_line_2}}@endif @if($cityLine)<br>{{$cityLine}}@endif @if($client->country_code)<br>{{$client->country_code}}@endif</address>@else Not provided @endif</dd></dl></div></div>
<div class="col-lg-6"><div class="admin-next-card h-100"><h2>Record details</h2><dl class="row mb-0 mt-3"><dt class="col-sm-4">Client type</dt><dd class="col-sm-8">{{ucfirst($client->client_type)}}</dd><dt class="col-sm-4">Organization</dt><dd class="col-sm-8">{{$client->organization_name ?: 'Not applicable'}}</dd><dt class="col-sm-4">Internal notes</dt><dd class="col-sm-8" style="white-space:pre-line">{{$client->notes ?: 'None'}}</dd></dl></div></div></div>
<div class="admin-next-card mt-4"><h2>Payment plans</h2>@forelse($client->memberships as $membership)<p><a href="{{route('admin.plans.show',$membership->paymentPlan)}}">{{$membership->paymentPlan->title}}</a> &mdash; {{str($membership->role)->replace('_',' ')->title()}}</p>@empty<p class="mb-0">No payment plans yet.</p>@endforelse</div>
</div></section>
@include('admin.clients._portal-account')
@endsection
