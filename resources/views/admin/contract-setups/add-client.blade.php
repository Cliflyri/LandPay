@extends('layouts.admin')
@section('title','Add co-client | LandPay')
@section('body_class','admin-page')
@section('content')
@php
$value = fn ($key, $default = null) => old($key, $default);
$addingClient = true;
$coHeading = 'Co-client';
$contractClientOptions = $clients->map(fn ($client) => ['id'=>$client->id,'label'=>$client->organization_name ?: trim($client->first_name.' '.$client->last_name),'email'=>$client->email,'phone'=>$client->primary_phone])->values();
@endphp
<section class="admin-section"><div class="container-fluid dashboard-container px-2">
<div class="admin-heading"><h1>Add co-client</h1><p>Plan {{ $plan->plan_number }}. A co-client with portal access can see this plan's financial history and make payments. Messages and shared documents remain individual.</p></div>
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<form id="add-client-form" method="post" action="{{ route('admin.plans.co-clients.store',$plan) }}">
@csrf
@include('admin.contract-setups.co-client')
<div class="d-flex flex-wrap gap-2 mt-4">
<button class="btn btn-brand" name="next" value="plan">Save co-client</button>
<button class="btn btn-outline-brand" name="next" value="contracts">Save and revise contracts</button>
<a class="btn btn-outline-brand" href="{{ route('admin.plans.show',$plan) }}">Cancel</a>
</div>
</form></div></section>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{
 const form=document.getElementById('add-client-form');
 const field=n=>form.elements[n];
 @include('admin.contract-setups.client-picker')
 form.addEventListener('submit',event=>{
   if(field('co_mode').value==='existing'&&!field('co_client_id').value){event.preventDefault();alert('Select a co-client from the search results.');}
 });
});
</script>
@endpush
