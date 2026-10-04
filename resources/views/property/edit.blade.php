@extends('layouts.admin')
@section('title','Property details | LandPay')
@section('body_class','admin-page')
@section('content')
<section class="admin-section"><div class="container-fluid dashboard-container px-2">
<div class="admin-heading d-flex flex-wrap justify-content-between align-items-end gap-3"><div><span class="eyebrow eyebrow-dark">{{$plan->plan_number}}</span><h1>Property details</h1><p class="mb-0">{{$plan->title}}</p></div><a class="btn btn-outline-brand" href="{{route('admin.plans.show',$plan)}}">Back to plan</a></div>
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{$error}}</li>@endforeach</ul></div>@endif
<form class="admin-next-card" method="post" action="{{route('admin.property.update',$plan)}}" enctype="multipart/form-data">@csrf @method('PUT')
<p class="small text-muted">All fields are optional. Clients see My Property only when there is a map pin, photo, or note.</p>
<h2>Map pin</h2><p class="small text-muted">Paste your coordinates below, or enter latitude and longitude separately. N/E are positive; S/W are negative. Leave the paste field empty and clear both separate fields to remove the map.</p>
<label class="form-label" for="coordinates">Paste coordinates (optional)</label>
<input class="form-control mb-3" type="text" id="coordinates" name="property_coordinates" value="{{old('property_coordinates')}}" placeholder="35.674744&#176; N 114.156267&#176; W">
<div class="row g-3"><div class="col-sm-6"><label class="form-label" for="latitude">Latitude</label><input class="form-control" type="text" id="latitude" name="property_latitude" value="{{old('property_latitude',$plan->property_latitude)}}" placeholder="35.674744&#176; N"></div>
<div class="col-sm-6"><label class="form-label" for="longitude">Longitude</label><input class="form-control" type="text" id="longitude" name="property_longitude" value="{{old('property_longitude',$plan->property_longitude)}}" placeholder="114.156267&#176; W"></div></div>
<h2 class="mt-4">Photos</h2><p class="small text-muted">The first photo is the hero image. Set display order below; new photos are added at the end. Up to 20 photos total.</p>
<div class="row g-3">@foreach($plan->property_photos??[] as $photo)<div class="col-6 col-md-3 col-xl-2">
<img class="rounded w-100" style="height:100px;object-fit:cover" src="{{route('admin.property.photo',[$plan,$photo['id']])}}" alt="Property photo {{$loop->iteration}}">
<label class="form-label small mt-1" for="order-{{$photo['id']}}">Display order</label><input class="form-control form-control-sm" type="number" min="1" max="1000" name="order[{{$photo['id']}}]" id="order-{{$photo['id']}}" value="{{old('order.'.$photo['id'],$loop->iteration)}}">
<div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="remove[]" value="{{$photo['id']}}" id="remove-{{$photo['id']}}" @checked(in_array($photo['id'],old('remove',[])))><label class="form-check-label small" for="remove-{{$photo['id']}}">Remove</label></div>
</div>@endforeach</div>
@include('improvements.photo-picker',['pickerId'=>'property-photos','photoField'=>'photos'])
<label class="form-label fw-semibold mt-4" for="property-notes">Notes / instructions / directions</label>
<textarea class="form-control" id="property-notes" name="property_notes" rows="4" maxlength="10000">{{old('property_notes',$plan->property_notes)}}</textarea>
<button class="btn btn-brand mt-3">Save property details</button>
</form></div></section>
@endsection
