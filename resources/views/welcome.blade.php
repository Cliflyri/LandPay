@extends('layouts.app')

@section('title', 'LandPay | Your payment plan, made simple')
@section('meta_description', 'View payments, receipts, account activity, and upcoming payment details securely with LandPay.')

@section('content')
<section class="hero-section">
    <div class="hero-texture" aria-hidden="true"></div>
    <div class="container site-container position-relative">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <span class="eyebrow">A clearer path to ownership</span>
                <h1>Your payment plan,<br><span>made simple.</span></h1>
                <p class="hero-lead">See what is due, make a payment, and keep every receipt in one secure place—without the paperwork shuffle.</p>
                <div class="hero-actions">
                    <a class="btn btn-sun btn-lg" href="#portal">Open client portal</a>
                    <a class="text-link" href="#how-it-works">See how LandPay works <span aria-hidden="true">→</span></a>
                </div>
                <div class="hero-trust" aria-label="LandPay benefits">
                    <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4.5 6v5.4c0 4.7 3.2 8.8 7.5 9.6 4.3-.8 7.5-4.9 7.5-9.6V6L12 3Zm3.6 7-4.2 4.5-2.3-2.3"/></svg> Secure account access</span>
                    <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12a8 8 0 1 0 2.3-5.7L4 8.6M4 4v4.6h4.6M12 7v5l3.2 1.8"/></svg> Complete payment history</span>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="account-preview" aria-label="Example account overview">
                    <div class="preview-top">
                        <span>Account overview</span>
                        <span class="status-pill"><i></i> On track</span>
                    </div>
                    <div class="preview-body">
                        <p class="preview-label">Next payment</p>
                        <div class="payment-row">
                            <strong>$425.00</strong>
                            <span>Due Aug 1</span>
                        </div>
                        <div class="progress" role="progressbar" aria-label="Monthly payment progress" aria-valuenow="68" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" style="width: 68%"></div>
                        </div>
                        <div class="preview-meta"><span>Paid this month</span><strong>$290.00</strong></div>
                        <a class="btn btn-brand w-100" href="#portal">Make a payment</a>
                    </div>
                    <div class="preview-foot">
                        <span class="receipt-icon" aria-hidden="true">✓</span>
                        <span><strong>Payment received</strong><small>Receipt #LP-2841 · Jul 12</small></span>
                        <strong class="ms-auto">$145.00</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="assurance-strip" aria-label="Service assurances">
    <div class="container site-container">
        <div class="row g-3">
            <div class="col-md-4"><div class="assurance-item"><span>01</span><p><strong>Know what is due</strong>Clear monthly payment details.</p></div></div>
            <div class="col-md-4"><div class="assurance-item"><span>02</span><p><strong>Pay your way</strong>Flexible amounts and timing.</p></div></div>
            <div class="col-md-4"><div class="assurance-item"><span>03</span><p><strong>Keep the record</strong>Receipts and history in one place.</p></div></div>
        </div>
    </div>
</section>

<section class="section-pad" id="features">
    <div class="container site-container">
        <div class="section-heading text-center mx-auto">
            <span class="eyebrow eyebrow-dark">Built for real-life payment plans</span>
            <h2>Everything you need.<br>Nothing you don’t.</h2>
            <p>LandPay keeps the details simple and organized so clients and administrators can focus on moving forward.</p>
        </div>
        <div class="row g-4 feature-grid">
            <div class="col-md-6 col-lg-4">
                <article class="feature-card">
                    <div class="feature-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4m10-4v4M3 10h18m-14 4h3m3 0h4m-10 3h5"/></svg></div>
                    <h3>Simple monthly view</h3><p>See the customary payment, fees, due date, and current status at a glance.</p>
                </article>
            </div>
            <div class="col-md-6 col-lg-4">
                <article class="feature-card feature-card-accent">
                    <div class="feature-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16v12H4zM4 10h16M8 15h3"/><path d="M7 7V5h10v2"/></svg></div>
                    <h3>Flexible payments</h3><p>Record partial, extra, principal-only, and multiple payments at any time.</p>
                </article>
            </div>
            <div class="col-md-6 col-lg-4">
                <article class="feature-card">
                    <div class="feature-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"/><path d="M9 8h6m-6 4h6m-6 4h3"/></svg></div>
                    <h3>Receipts that stay put</h3><p>Every posted payment has a permanent history, so your status remains clear and simple.</p>
                </article>
            </div>
        </div>
    </div>
</section>

<section class="process-section" id="how-it-works">
    <div class="container site-container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-5">
                <span class="eyebrow">Easy from day one</span>
                <h2>Stay on top of your plan in three steps.</h2>
                <p>No spreadsheets, lost receipts, or guessing what happened to a payment.</p>
                <a class="btn btn-sun" href="#portal">Go to your account</a>
            </div>
            <div class="col-lg-7">
                <ol class="process-list">
                    <li><span>1</span><div><h3>Sign in securely</h3><p>Use your private portal to view only your account information.</p></div></li>
                    <li><span>2</span><div><h3>Review your payment</h3><p>Confirm the amount due and see how recent payments were applied.</p></div></li>
                    <li><span>3</span><div><h3>Pay and keep your receipt</h3><p>Submit a payment and return anytime to review your permanent history.</p></div></li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="portal-section" id="portal">
    <div class="container site-container">
        <div class="portal-panel">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <span class="eyebrow eyebrow-dark">Client portal</span>
                    <h2>Your plan is private.<br>Your progress is personal.</h2>
                    <p>Sign in securely to review active plans, current balances, invoices, payment history, and downloadable receipts.</p>
                </div>
                <div class="col-lg-5" id="admin">
                    <div class="coming-soon-card">
                        <span class="coming-soon-label">Secure access</span>
                        <h3>Your account documents</h3>
                        <p>See balances and due dates, open invoices, and keep a permanent payment receipt history.</p>
                        <a class="btn btn-brand w-100" href="{{route('portal.login')}}">Portal sign in</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection