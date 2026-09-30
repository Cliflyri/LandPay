@extends('layouts.admin')
@section('title','Payment result | LandPay')
@section('body_class','admin-page')
@section('content')
<section class="admin-section"><div class="container-fluid dashboard-container">
<div class="admin-heading"><span class="eyebrow eyebrow-dark">Charge Customer</span><h1>{{ $intent->status === 'received' ? 'Payment successful' : ($intent->status === 'failed' ? 'Payment declined or failed' : 'Payment requires review') }}</h1>
<p>{{ $intent->client->organization_name ?: trim($intent->client->first_name.' '.$intent->client->last_name) }} &middot; Plan {{ $intent->paymentPlan->plan_number }} @if($intent->invoice)&middot; Invoice {{ $intent->invoice->invoice_number }}@endif</p></div>
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('warning'))<div class="alert alert-warning">{{ session('warning') }}</div>@endif
<div class="admin-next-card mt-4">
<p>Payment amount: <strong>{{ \App\Support\Money::format($intent->base_amount) }}</strong><br>Processing fee: {{ \App\Support\Money::format($intent->processing_fee_amount) }}<br>Total: <strong>{{ \App\Support\Money::format($intent->amount) }}</strong></p>
@if($intent->status === 'received' && $intent->payment)
<p>The payment has been recorded and applied to the plan.</p>
<a class="btn btn-outline-brand" href="{{ route('admin.payments.show',$intent->payment) }}">View payment and receipt</a>
<form class="mt-4" method="post" action="{{ route('admin.take-payment.receipt',$intent) }}">
@csrf
<label class="form-label" for="receipt-recipients">Receipt email recipients</label>
<input class="form-control" id="receipt-recipients" name="recipients" type="email" multiple maxlength="1500" value="{{ old('recipients',$intent->client->email) }}" required>
<p class="form-text">Replace the address or add addresses separated by commas (up to five). This sends the LandPay receipt without changing the client's saved email or portal access.</p>
<button class="btn btn-brand">Email receipt</button>
</form>
@if($intent->payment->emailDeliveries->isNotEmpty())
<h2 class="h5 mt-4">Receipt delivery history</h2>
<ul>@foreach($intent->payment->emailDeliveries->sortByDesc('id') as $delivery)<li>{{ $delivery->recipient_email }} — {{ ucfirst($delivery->status) }} @if($delivery->sent_at)({{ $delivery->sent_at->format('M j, Y g:i A') }})@endif</li>@endforeach</ul>
@endif
@elseif($intent->status === 'failed')
<p>The processor did not report a completed payment. Check the error before starting a new attempt.</p>
<a class="btn btn-outline-brand" href="{{ route('admin.take-payment.create',['client_id'=>$intent->client_id,'plan'=>$intent->payment_plan_id]) }}">New payment attempt</a>
@else
<div class="alert alert-warning">Payment confirmation needs review. The card may already have been charged. Check Square and LandPay before trying another charge.</div>
<p>Reference: {{ $intent->uuid }} @if($intent->provider_payment_id)&middot; Square: {{ $intent->provider_payment_id }}@endif</p>
<a class="btn btn-outline-brand" href="{{ route('admin.take-payment.show',$intent) }}">Refresh status</a>
@endif
</div>
<div class="mt-4"><a href="{{ route('admin.plans.show',$intent->paymentPlan) }}">Back to plan</a> &middot; <a href="{{ route('admin.actions.index') }}">Admin Actions</a></div>
</div></section>
@endsection
