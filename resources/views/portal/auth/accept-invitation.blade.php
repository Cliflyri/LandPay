@extends('layouts.app')
@section('title','Create your portal password | LandPay')
@section('body_class','admin-page')
@section('content')
<section class="auth-section"><div class="container site-container"><div class="auth-card mx-auto"><div class="auth-card-heading"><span class="eyebrow eyebrow-dark">Client portal invitation</span><h1>Create your password</h1><p>This one-time invitation for {{$invitation->email}} expires {{$invitation->expires_at->diffForHumans()}}.</p></div>@if($errors->any())<div class="alert alert-danger">{{$errors->first()}}</div>@endif<form method="post" action="{{route('portal.invitation.accept',$token)}}">@csrf<label class="form-label">Password</label><input class="form-control mb-3" name="password" type="password" required autocomplete="new-password"><label class="form-label">Confirm password</label><input class="form-control mb-3" name="password_confirmation" type="password" required autocomplete="new-password"><button class="btn btn-brand w-100">Activate my account</button></form></div></div></section>
@endsection
