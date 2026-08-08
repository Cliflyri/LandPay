@extends('layouts.app')
@section('title','Client sign in | LandPay')
@section('body_class','admin-page')
@section('content')
<section class="auth-section"><div class="container site-container"><div class="auth-card mx-auto"><div class="auth-card-heading"><span class="eyebrow eyebrow-dark">Client portal</span><h1>Welcome back</h1><p>Sign in to review your plans, invoices, and receipts.</p></div>
@if(session('status'))<div class="alert alert-success">{{session('status')}}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{$errors->first()}}</div>@endif
<form method="post" action="{{route('portal.login.store')}}">@csrf
<label class="form-label" for="email">Email</label><input class="form-control mb-3" id="email" name="email" type="email" value="{{old('email')}}" required autofocus autocomplete="username">
<label class="form-label" for="password">Password</label><input class="form-control mb-3" id="password" name="password" type="password" required autocomplete="current-password">
<div class="d-flex justify-content-between align-items-center mb-3"><label><input type="checkbox" name="remember" value="1"> Remember me</label><a href="{{route('portal.password.request')}}">Forgot password?</a></div>
<button class="btn btn-brand w-100" type="submit">Sign in</button></form>
<p class="auth-help mb-0 mt-3">Accounts are created by an authorized {{ \App\Models\AppSetting::valueFor('company_name', config('app.name', 'LandPay')) }} administrator. Please contact us for information.</p></div></div></section>
@endsection
