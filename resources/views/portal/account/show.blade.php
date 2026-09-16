@extends('layouts.app')

@section('title', 'Account | LandPay')
@section('body_class', 'admin-page')

@section('content')
<section class="admin-section">
    <div class="container site-container">

        <div class="admin-heading d-flex justify-content-between align-items-end">
            <div>
                <span class="eyebrow eyebrow-dark">Client portal</span>
                <h1>Account</h1>
                <p>Contact information, contract details, and account security.</p>
            </div>

            <a class="btn btn-outline-brand" href="{{ route('portal.dashboard') }}">
                Dashboard
            </a>
        </div>

        @if(session('status'))
            <div class="alert alert-success mt-4">
                {{ session('status') }}
            </div>
        @endif

        @include('portal.account._contact-notice')

        <div class="row g-4 mt-2">

            {{-- Contact Details --}}
<div class="col-lg-6">
    <div class="admin-next-card">
        <h2>Contact details</h2>

        <p>
            {{ $account->client->email }}<br>
            {{ $account->client->primary_phone }}<br>
            {{ $account->client->address_line_1 }}
            {{ $account->client->address_line_2 }}<br>
            {{ $account->client->city }}
            {{ $account->client->state_region }}
            {{ $account->client->postal_code }}
        </p>

        @if($pending && !$contactUpdatePending)
            <div class="alert alert-info">
                A contact update is pending administrator review.
            </div>
        @endif

        <a class="btn btn-brand" href="{{ route('portal.account.edit') }}">
            Update Information
        </a>


{{-- Text Message Notifications --}}
<div
    class="border-top mt-4 pt-4"
    style="
        background: #f8fcfb;
        margin-left: -1.5rem;
        margin-right: -1.5rem;
        margin-bottom: -1.5rem;
        padding-left: 1.5rem;
        padding-right: 1.5rem;
        padding-bottom: 1.5rem;
    "
>
    {{-- Heading --}}
    <div class="d-flex align-items-start gap-3 mb-4">

        {{-- Message bubble icon --}}
        <div class="flex-shrink-0" style="margin-top: 2px;">
            <svg
                width="50"
                height="50"
                viewBox="-4 0 72 64"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
                aria-hidden="true"
            >
                <path
                    d="M13 8H47C55.3 8 62 14.7 62 23V33C62 41.3 55.3 48 47 48H28L15 57V48H13C4.7 48 -2 41.3 -2 33V23C-2 14.7 4.7 8 13 8Z"
                    stroke="#174b48"
                    stroke-width="2.3"
                    stroke-linejoin="round"
                />

                <circle cx="19" cy="28" r="3" fill="#174b48"/>
                <circle cx="30" cy="28" r="3" fill="#174b48"/>
                <circle cx="41" cy="28" r="3" fill="#174b48"/>
            </svg>
        </div>

        <div>
            <h2 class="mb-1">
                Text message notifications
            </h2>

            <p
                class="text-muted mb-0"
                style="font-size: .95rem;"
            >
                Get important LandPay notices by text, even if an email is missed or your inbox is full.
            </p>
        </div>
    </div>

    {{-- SMS Preference --}}
    <form method="post" action="{{route('portal.account.sms-preference.update')}}">
        @csrf
        @method('put')

        <input type="hidden" name="sms_enabled" value="0">

        <div class="d-flex align-items-center gap-3 mb-3">

            <div class="form-check form-switch p-0 m-0 flex-shrink-0">
                <input
                    class="form-check-input m-0"
                    type="checkbox"
                    role="switch"
                    id="sms_enabled"
                    name="sms_enabled"
                    value="1"
                    @checked($account->client->smsPreference?->enabled)
                    onchange="this.form.submit()"
                    style="
                        width: 3.75rem;
                        height: 1.9rem;
                        float: none;
                        margin-left: 0;
                        cursor: pointer;
                    "
                >
            </div>

            <label
                class="form-check-label fw-semibold mb-0"
                for="sms_enabled"
                style="
                    font-size: .95rem;
                    line-height: 1.4;
                    cursor: pointer;
                "
            >
                Send me invoice and account-related text messages.
            </label>
        </div>

        <p
            class="text-muted mb-4"
            style="
                font-size: .9rem;
                line-height: 1.4;
            "
        >
            We only send short messages about your account when necessary and do not share your phone number for marketing.
            Your phone message and data rates may apply.
            Reply STOP to opt out or uncheck this switch at any time.
        </p>

        <div
            class="d-flex align-items-start gap-3 rounded-3 p-3"
            style="
                background: #f2f9f8;
                border: 1px solid #c8dfdc;
                color: #27726d;
            "
        >
            <div
                class="d-flex align-items-center justify-content-center flex-shrink-0"
                style="
                    width: 28px;
                    height: 28px;
                    border-radius: 50%;
                    background: #2c807a;
                    color: #fff;
                    font-weight: 700;
                    font-size: .9rem;
                    line-height: 1;
                "
                aria-hidden="true"
            >
                i
            </div>

            <div
                style="
                    font-size: .925rem;
                    line-height: 1.55;
                "
            >
                This setting is saved automatically when you change it.
                No need to click Update Information.
            </div>
        </div>

        @error('sms_enabled')
            <div class="text-danger small mt-2">
                {{$message}}
            </div>
        @enderror
    </form>
</div>
        

        

    </div>
</div>

            {{-- Security --}}
            <div class="col-lg-6">
                <div class="admin-next-card">
                    <h2>Security</h2>

                    <form method="post" action="{{ route('portal.account.password') }}">
                        @csrf
                        @method('PUT')

                        <label class="form-label">Current password</label>
                        <input
                            class="form-control mb-2"
                            name="current_password"
                            type="password"
                            required
                        >

                        <label class="form-label">New password</label>
                        <input
                            class="form-control mb-2"
                            name="password"
                            type="password"
                            required
                        >

                        <label class="form-label">Confirm new password</label>
                        <input
                            class="form-control mb-3"
                            name="password_confirmation"
                            type="password"
                            required
                        >

                        <button class="btn btn-outline-brand">
                            Update password
                        </button>
                    </form>
                </div>
            </div>

        </div>

        {{-- Contract Details --}}
        <div class="admin-next-card mt-4">
            <h2>Contract details</h2>

            <p class="text-muted text-decoration-underline">
                Current Payoff includes any unpaid base fee for the current billing month.
            </p>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>APN / Plan #</th>
                            <th>Plan</th>
                            <th class="text">Purchase Price</th>
                            <th>Start date</th>
                            <th class="text-end">Principal Paid</th>
                            <th class="text-end">Current Payoff</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($plans as $row)
                            <tr>
                                <td>
                                    {{ $row['plan']->apn ?: $row['plan']->plan_number }}
                                </td>

                                <td>
                                    {{ $row['plan']->title }}
                                </td>

                                <td>
                                    {{ \App\Support\Money::format($row['plan']->purchase_price) }}
                                </td>

                                <td>
                                    {{ $row['plan']->plan_start_date->format('M j, Y') }}
                                </td>

                                <td class="money-cell">
                                    <strong>
                                        {{ \App\Support\Money::format($row['principal_paid']) }}
                                    </strong>
                                    <span class="d-block text-muted small">
                                        (Excludes fees)
                                    </span>
                                </td>

                                <td class="money-cell">
                                    {{ \App\Support\Money::format($row['current_payoff']) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">No active contracts.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</section>
@endsection