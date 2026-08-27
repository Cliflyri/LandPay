@extends('layouts.app')

@section('title', 'Administrator sign in | LandPay')
@section('meta_description', 'Secure administrator access to LandPay.')
@section('body_class', 'auth-page')

@section('content')
<section class="auth-section">
    <div class="container site-container">
        <div class="auth-card mx-auto">
            <div class="auth-card-heading">
                <span class="eyebrow eyebrow-dark">Secure administrator access</span>
                <h1>Welcome back.</h1>
                <p>Sign in to manage LandPay clients and payment plans.</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <strong>We could not sign you in.</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.store') }}" novalidate>
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="email">Email address</label>
                    <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control @error('password') is-invalid @enderror" id="password" name="password" type="password" autocomplete="current-password" required>
                </div>
                <div class="form-check mb-4">
                    <input class="form-check-input" id="remember" name="remember" type="checkbox" value="1">
                    <label class="form-check-label" for="remember">Keep me signed in</label>
                </div>
                <button class="btn btn-brand btn-lg w-100" type="submit">Sign in securely</button>
            </form>

            <p class="auth-help mb-0">Accounts are created by an authorized LandPay administrator. Public registration is disabled.</p>
        </div>
    </div>
</section>
@endsection