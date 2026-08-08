@extends('layouts.app')
@section('title','Choose a new password | LandPay')
@section('body_class','admin-page')
@section('content')
<section class="auth-section"><div class="container site-container"><div class="auth-card mx-auto"><div class="auth-card-heading"><span class="eyebrow eyebrow-dark">Client portal</span><h1>Choose a new password</h1></div>
@if($errors->any())<div class="alert alert-danger">{{$errors->first()}}</div>@endif
<form method="post" action="{{route('portal.password.update')}}">@csrf<input type="hidden" name="token" value="{{$token}}"><label class="form-label">Email</label><input class="form-control mb-3" name="email" type="email" value="{{old('email',$email)}}" required><label class="form-label">New password</label><input class="form-control mb-3" name="password" type="password" required><label class="form-label">Confirm password</label><input class="form-control mb-3" name="password_confirmation" type="password" required><button class="btn btn-brand w-100">Reset password</button></form></div></div></section>
@endsection
