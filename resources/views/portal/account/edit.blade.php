@extends('layouts.app')
@section('title','Update contact details | LandPay')
@section('body_class','admin-page')
@section('content')

<section class="admin-section">
    <div class="container site-container">

        <div class="admin-heading">
            <span class="eyebrow eyebrow-dark">Account</span>
            <h1>Contact Information Updates</h1>
            <p>Changes are sent to your plan administrator for review.</p>
        </div>

        <form class="admin-form-card" method="post" action="{{route('portal.account.update')}}">
            @csrf
            @method('PUT')

            @if($errors->any())
                <div class="alert alert-danger">
                    {{$errors->first()}}
                </div>
            @endif

            <div class="row g-3">

                <div class="col-12">
                    <hr class="my-2">
                    <h5 class="mb-1">
                        <span aria-hidden="true" class="me-1">✉</span>
                        Contact information
                    </h5>
                    <div class="text-muted small mb-2">
                        Update the email address and phone numbers associated with your account.
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label">
                        <span aria-hidden="true" class="me-1">✉</span>
                        Email
                    </label>
                    <input
                        class="form-control"
                        name="email"
                        type="email"
                        value="{{old('email',$account->client->email)}}"
                        required
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        <span aria-hidden="true" class="me-1">☎</span>
                        Primary phone
                    </label>
                    <input
                        class="form-control"
                        name="primary_phone"
                        type="tel"
                        value="{{old('primary_phone',$account->client->primary_phone)}}"
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        <span aria-hidden="true" class="me-1">☎</span>
                        Secondary phone
                    </label>
                    <input
                        class="form-control"
                        name="secondary_phone"
                        type="tel"
                        value="{{old('secondary_phone',$account->client->secondary_phone)}}"
                    >
                </div>

                <div class="col-12 mt-4">
                    <hr class="my-2">
                    <h5 class="mb-1">
                        <span aria-hidden="true" class="me-1">⌂</span>
                        Mailing address
                    </h5>
                    <div class="text-muted small mb-2">
                        Update the mailing address associated with your account.
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label">Address</label>
                    <input
                        class="form-control"
                        name="address_line_1"
                        value="{{old('address_line_1',$account->client->address_line_1)}}"
                    >
                </div>

                <div class="col-12">
                    <label class="form-label">Address line 2</label>
                    <input
                        class="form-control"
                        name="address_line_2"
                        value="{{old('address_line_2',$account->client->address_line_2)}}"
                    >
                </div>

                <div class="col-md-5">
                    <label class="form-label">City</label>
                    <input
                        class="form-control"
                        name="city"
                        value="{{old('city',$account->client->city)}}"
                    >
                </div>

                <div class="col-md-3">
                    <label class="form-label">State / region</label>
                    <input
                        class="form-control"
                        name="state_region"
                        value="{{old('state_region',$account->client->state_region)}}"
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label">Postal code</label>
                    <input
                        class="form-control"
                        name="postal_code"
                        value="{{old('postal_code',$account->client->postal_code)}}"
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label">Country</label>
                    <input
                        class="form-control"
                        name="country_code"
                        value="{{old('country_code',$account->client->country_code)}}"
                        required
                    >
                </div>

            </div>

            <button class="btn btn-brand mt-4">Submit Updates</button>
            <a class="btn btn-outline-brand mt-4" href="{{route('portal.account.show')}}">Cancel</a>

        </form>

    </div>
</section>

@endsection