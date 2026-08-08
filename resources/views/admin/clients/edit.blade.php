@extends('layouts.admin')
@section('title','Edit client | LandPay')
@section('body_class','admin-page')
@section('content')
<section class="admin-section"><div class="container site-container">
<div class="admin-heading"><span class="eyebrow eyebrow-dark">Client record</span><h1>Edit client</h1><p>Update contact, address, and internal account information.</p></div>
<form class="admin-form-card" method="POST" action="{{ route('admin.clients.update',$client) }}">@csrf @method('PUT')
@if($errors->any())<div class="alert alert-danger"><strong>The client was not updated.</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="row g-3">
<div class="col-md-4"><label class="form-label">Client type</label><select class="form-select" name="client_type"><option value="individual" @selected(old('client_type',$client->client_type)==='individual')>Individual</option><option value="organization" @selected(old('client_type',$client->client_type)==='organization')>Organization</option></select></div>
<div class="col-md-8"><label class="form-label">Organization name</label><input class="form-control" name="organization_name" value="{{ old('organization_name',$client->organization_name) }}"></div>
@foreach(['first_name'=>'First name','middle_name'=>'Middle name','last_name'=>'Last name','preferred_name'=>'Preferred name','email'=>'Email','primary_phone'=>'Primary phone','secondary_phone'=>'Secondary phone','address_line_1'=>'Address','address_line_2'=>'Address line 2','city'=>'City','state_region'=>'State / region','postal_code'=>'Postal code'] as $field=>$label)
<div class="col-md-4"><label class="form-label">{{ $label }}</label><input class="form-control @error($field) is-invalid @enderror" name="{{ $field }}" value="{{ old($field,$client->{$field}) }}">@error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
@endforeach
<div class="col-md-2"><label class="form-label">Country</label><input class="form-control" name="country_code" maxlength="2" value="{{ old('country_code',$client->country_code) }}"></div>
<div class="col-12"><label class="form-label">Internal notes</label><textarea class="form-control" name="notes" rows="4">{{ old('notes',$client->notes) }}</textarea></div>
</div><div class="d-flex gap-2 mt-4"><button class="btn btn-brand">Save changes</button><a class="btn btn-outline-brand" href="{{ route('admin.clients.show',$client) }}">Cancel</a></div></form>
</div></section>
@endsection