@extends('layouts.app')
@section('title','Create your portal password | LandPay')
@section('body_class','admin-page')
@section('content')
<section class="auth-section"><div class="container site-container"><div class="auth-card mx-auto"><div class="auth-card-heading"><span class="eyebrow eyebrow-dark">Client portal invitation</span>
@if($invitation->client->portalAccount?->enabled)
<h1>Your portal is active</h1><p>This invitation is no longer needed. You may sign in to your portal.</p></div><a class="btn btn-brand w-100" href="{{route('portal.login')}}">Sign in</a>
@elseif(!$invitation->isUsable())
<h1>This invitation has expired</h1><p>Request a new invitation below, or continue using the secure links in your invoice emails without creating an account.</p></div>@if(session('status'))<div class="alert alert-info">{{session('status')}}</div>@endif<form method="post" action="{{route('portal.invitation.resend',$token)}}">@csrf<button class="btn btn-brand w-100">Request a new invitation</button></form>
@else
<h1>Create your password</h1><p>This one-time invitation for {{$invitation->email}} expires {{$invitation->expires_at->diffForHumans()}}.</p></div>@if($errors->any())<div class="alert alert-danger">{{$errors->first()}}</div>@endif<form method="post" action="{{route('portal.invitation.accept',$token)}}">@csrf<label class="form-label">Password</label><input class="form-control mb-3" name="password" type="password" required autocomplete="new-password"><label class="form-label">Confirm password</label><input class="form-control mb-3" name="password_confirmation" type="password" required autocomplete="new-password"><button class="btn btn-brand w-100">Activate my account</button></form>
@endif
</div></div></section>
@endsection
