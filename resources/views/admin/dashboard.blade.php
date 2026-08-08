@extends('layouts.app')

@section('title', 'Administrator dashboard | LandPay')
@section('meta_description', 'LandPay administrator dashboard.')
@section('body_class', 'admin-page')

@section('content')
<section class="admin-section">
    <div class="container site-container">
        <div class="admin-heading d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3">
            <div>
                <span class="eyebrow eyebrow-dark">Administrator dashboard</span>
                <h1>Welcome, {{ auth()->user()->name }}.</h1>
                <p class="mb-0">Your secure LandPay workspace is ready for the approved identity-schema phase.</p>
            </div>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button class="btn btn-outline-brand" type="submit">Sign out</button>
            </form>
        </div>

        <div class="row g-4 mt-3">
            <div class="col-md-4">
                <article class="admin-summary-card"><span>Clients</span><strong>—</strong><small>Available after schema approval</small></article>
            </div>
            <div class="col-md-4">
                <article class="admin-summary-card"><span>Payment plans</span><strong>—</strong><small>Available after schema approval</small></article>
            </div>
            <div class="col-md-4">
                <article class="admin-summary-card"><span>Open invoices</span><strong>—</strong><small>Available after financial design</small></article>
            </div>
        </div>

        <div class="admin-next-card mt-4">
            <span class="coming-soon-label">Next approved milestone</span>
            <h2>Identity schema review</h2>
            <p class="mb-0">The next deliverable will present the proposed client, payment-plan, co-client, and general contact tables—including relationships, indexes, and constraints—before any migrations are created.</p>
        </div>
    </div>
</section>
@endsection