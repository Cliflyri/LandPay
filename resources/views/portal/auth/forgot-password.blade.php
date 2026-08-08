@extends('layouts.app')
@section('title','Reset client password | LandPay')
@section('body_class','admin-page')
@section('content')
<section class="auth-section"><div class="container site-container"><div class="auth-card mx-auto"><div class="auth-card-heading"><span class="eyebrow eyebrow-dark">Client portal</span><h1>Reset password</h1><p>Enter your portal email and we will send a secure reset link.</p></div>
@if(session('status'))<div class="alert alert-success">{{session('status')}}</div>@endif @error('email')<div class="alert alert-danger">{{$message}}</div>@enderror
<form method="post" action="{{route('portal.password.email')}}">@csrf<label class="form-label" for="email">Email</label><input class="form-control mb-3" id="email" name="email" type="email" value="{{old('email')}}" required><button class="btn btn-brand w-100">Send reset link</button></form><p class="auth-help"><a href="{{route('portal.login')}}">Back to sign in</a></p></div></div></section>
@endsection
