@extends('layouts.admin')

@section('title','Add client | LandPay')
@section('body_class','admin-page')

@section('content')

<section class="admin-section">
    <div class="container-fluid dashboard-container">
        <h1>Add a client</h1>

    <form class="admin-form-card" method="POST" action="{{ route('admin.clients.store') }}">
        @csrf

        @if($errors->any())
            <div class="alert alert-danger">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="row g-3">

            <div class="col-md-4">
                <label>Type</label>
                <select class="form-select" name="client_type">
                    <option value="individual">Individual</option>
                    <option value="organization">Organization</option>
                </select>
            </div>

            <div class="col-md-8">
                <label>Organization name</label>
                <input class="form-control" name="organization_name" value="{{ old('organization_name') }}">
            </div>

            <div class="col-md-6">
                <label>First name</label>
                <input class="form-control" name="first_name" value="{{ old('first_name') }}">
            </div>

            <div class="col-md-6">
                <label>Last name</label>
                <input class="form-control" name="last_name" value="{{ old('last_name') }}">
            </div>

            <div class="col-md-6">
                <label>Email</label>
                <input class="form-control" name="email" value="{{ old('email') }}">
            </div>

            <div class="col-md-6">
                <label>Phone</label>
                <input class="form-control" name="primary_phone" value="{{ old('primary_phone') }}">
            </div>

            <div class="col-12">
                <label>Address</label>
                <input class="form-control" name="address_line_1" value="{{ old('address_line_1') }}">
            </div>

            <div class="col-md-5">
                <label>City</label>
                <input class="form-control" name="city" value="{{ old('city') }}">
            </div>

            <div class="col-md-3">
                <label>State</label>
                <input class="form-control" name="state_region" value="{{ old('state_region') }}">
            </div>

            <div class="col-md-2">
                <label>Postal code</label>
                <input class="form-control" name="postal_code" value="{{ old('postal_code') }}">
            </div>

            <div class="col-md-2">
                <label>Country</label>
                <input class="form-control" name="country_code" value="{{ old('country_code','US') }}">
            </div>

            <div class="col-12">
                <label>Notes</label>
                <textarea class="form-control" name="notes">{{ old('notes') }}</textarea>
            </div>

        </div>

        <button class="btn btn-brand mt-4">Create client</button>
    </form>
</div>
```

</section>
@endsection
