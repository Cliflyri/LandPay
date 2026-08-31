@extends('layouts.app')
@section('title','Payment notification | LandPay')
@section('body_class','admin-page')
@section('content')
@include('portal._secure-invoice-notice')
<section class="admin-section"><div class="container site-container">
<div class="admin-heading"><div><span class="eyebrow eyebrow-dark">Client portal</span><h1>{{$intent->status==='announced'?'Administrator notified':'Payment status'}}</h1></div></div>
@if(session('success'))<div class="alert alert-success mt-4">{{session('success')}}</div>@endif
<div class="admin-next-card mt-4">
<span class="dashboard-status {{$intent->status==='received'?'status-current':'status-due'}}">{{$intent->status==='received'?'Payment received':($intent->status==='checkout_pending'?'Payment processing':'Admin notified')}}</span>
<h2 class="mt-3">{{\App\Support\Money::format($intent->amount)}} by {{$method['name']}}</h2>
@if($intent->processing_fee_amount>0)<p>Payment amount: <strong>{{\App\Support\Money::format($intent->base_amount)}}</strong><br>Processing Fee: <strong>{{\App\Support\Money::format($intent->processing_fee_amount)}}</strong></p>@endif
@if($intent->status==='received')
<p>Your payment has been received and posted to your account.</p>
@elseif($intent->status==='checkout_pending')
<p>Your card payment is being confirmed. Your account will update automatically when processing is complete.</p>
@else
<p>Admin has been notified of your intended payment. The payment will be posted after it is received and verified.</p>
@endif
<p><strong>Plan:</strong> {{$intent->paymentPlan->plan_number}}</p>
@if($intent->client_note)<p><strong>Your note:</strong> {{$intent->client_note}}</p>@endif
<div class="d-flex flex-wrap gap-2">
<a class="btn btn-outline-brand" href="{{($secureAccess ?? false) ? route('secure-invoice.show') : route('portal.dashboard')}}">{{($secureAccess ?? false) ? 'Return to invoice' : 'Return to dashboard'}}</a>
@if($intent->status==='announced')<form method="post" action="{{route(($secureAccess ?? false) ? 'secure-invoice.payment.cancel' : 'portal.make-payment.cancel',$intent)}}">@csrf @method('delete')<button class="btn btn-outline-danger" type="submit">Cancel notification</button></form>@endif
</div>
</div>
</div></section>
@endsection
