@extends('layouts.app')
@section('title','Make a payment | LandPay')
@section('body_class','admin-page')
@section('content')
<section class="admin-section"><div class="container site-container">
<div class="admin-heading d-flex justify-content-between align-items-end"><div><span class="eyebrow eyebrow-dark">Client portal</span><h1>Make a payment</h1><p class="mb-0">Choose the plan, amount, and payment method.</p></div><a class="btn btn-outline-brand" href="{{route('portal.dashboard')}}">Dashboard</a></div>
@if(session('success'))<div class="alert alert-success mt-4">{{session('success')}}</div>@endif
@if($errors->any())<div class="alert alert-danger mt-4">{{$errors->first()}}</div>@endif
<form class="admin-next-card mt-4 portal-payment-form" method="post" action="{{route('portal.make-payment.preview')}}">@csrf
<div class="row g-3">
<div class="col-md-6"><label class="form-label" for="payment-plan">Payment plan</label><select class="form-select" id="payment-plan" name="payment_plan_id">@foreach($plans as $plan)<option value="{{$plan->id}}" data-open-balance="{{number_format($planBalances[$plan->id]/100,2,'.','')}}" @selected((int)$input['payment_plan_id']===$plan->id)>{{$plan->plan_number}} &mdash; {{$plan->title}} ({{\App\Support\Money::format($planBalances[$plan->id])}} open)</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label" for="payment-amount">Amount</label><div class="input-group"><span class="input-group-text">$</span><input class="form-control" id="payment-amount" name="amount" inputmode="decimal" value="{{$input['amount']}}" @readonly(!$general['allow_custom_amount']) required></div><div class="form-text">Includes the total open invoices for the selected plan. @if($general['allow_custom_amount'])You may enter a different amount.@endif</div></div>
</div>
@if($preview)
<hr><h2>Allocation preview</h2><ul class="payment-allocation-list">@foreach($preview['allocations'] as $allocation)<li><span>{{$allocation['label']}}</span><strong>{{\App\Support\Money::format($allocation['amount'])}}</strong></li>@endforeach</ul>
@if($preview['overpayment_amount']>0)<fieldset class="payment-choice mt-3"><legend>How should the extra {{\App\Support\Money::format($preview['overpayment_amount'])}} be used?</legend><label><input type="radio" name="overpayment_disposition" value="principal" @checked(($input['overpayment_disposition']??'')==='principal')> Apply to principal</label><br><label><input type="radio" name="overpayment_disposition" value="next_invoice_credit" @checked(($input['overpayment_disposition']??'')==='next_invoice_credit')> Carry forward as credit</label></fieldset>@endif
@endif
<h2 class="mt-4">Choose a payment method</h2>
<input type="hidden" name="method" id="selected-payment-method" value="{{$input['method']??$methods[0]['key']??''}}">
<div class="portal-payment-tabs" role="tablist">@forelse($methods as $method)<button type="button" class="portal-payment-tab" data-payment-tab="{{$method['key']}}" role="tab" aria-selected="{{($input['method']??$methods[0]['key'])===$method['key']?'true':'false'}}">{{$method['name']}}@if($method['recommended'])<span class="badge text-bg-success">Recommended</span>@endif</button>@empty<p>No payment methods are currently available. Please contact us.</p>@endforelse</div>
@foreach($methods as $method)
<section class="portal-payment-panel" data-payment-panel="{{$method['key']}}" role="tabpanel">
<h3>{{$method['name']}}</h3>
<p class="portal-payment-amount">Payment amount: <strong>$<span data-payment-amount>{{$input['amount']}}</span></strong></p>
@if($method['key']==='card')
@if($preview)<button class="btn btn-brand" type="submit" formaction="{{route('portal.make-payment.store')}}">Pay Now (Credit Card)</button><p class="form-text mt-2">You will continue to secure {{$general['card_provider']==='disabled'?'card':ucfirst($general['card_provider'])}} checkout. LandPay posts the payment after the processor confirms it.</p>@else<p class="alert alert-light border">Preview the payment before continuing to secure card checkout.</p>@endif
@else
@if($preview)
<button class="btn btn-brand" type="button" data-notify-toggle>Notify Admin of $<span data-payment-amount>{{$input['amount']}}</span> {{$method['name']}} Payment</button>
<p class="form-text mt-2">Admin will post the payment after it is received and verified.</p>
<div class="offline-payment-confirmation mt-3" data-notify-confirmation hidden>
<div class="row g-3">
<div class="col-md-6"><label class="form-label">Reference or confirmation number @if($method['reference_required'])<span class="text-danger">(required)</span>@endif</label><input class="form-control" name="client_reference" maxlength="150" value="{{$input['client_reference']??''}}" @required($method['reference_required']) disabled></div>
<div class="col-md-6"><label class="form-label">Payment note (optional)</label><input class="form-control" name="client_note" maxlength="1000" value="{{$input['client_note']??''}}" placeholder="Example: Payment will arrive under the name Billy Jones." disabled><div class="form-text">Add details that may help us identify your payment.</div></div>
</div>
<button class="btn btn-brand mt-3" type="submit" formaction="{{route('portal.make-payment.store')}}" disabled data-send-notification>Send notification</button>
</div>
@else<p class="alert alert-light border">Preview the payment before notifying the administrator.</p>@endif
@endif
<div class="portal-payment-instructions mt-4">
@if($method['image_url'])@if($method['link'])<a href="{{$method['link']}}" target="_blank" rel="noopener"><img class="portal-payment-logo" src="{{$method['image_url']}}" alt="{{$method['name']}}"></a>@else<img class="portal-payment-logo" src="{{$method['image_url']}}" alt="{{$method['name']}}">@endif @endif
@if($method['recipient'])<p><strong>{{$method['recipient']}}</strong></p>@endif
@if($method['instructions'])<p>{!!nl2br(e($method['instructions']))!!}</p>@endif
@if($method['link'])<p><a class="btn btn-outline-brand" href="{{$method['link']}}" target="_blank" rel="noopener">Open {{$method['name']}}</a></p>@endif
</div>
</section>
@endforeach
<button class="btn btn-outline-brand mt-4" type="submit">{{$preview?'Refresh payment preview':'Preview payment'}}</button>
</form>
</div></section>
@push('scripts')
<script>
(function(){
const plan=document.getElementById('payment-plan');
const amount=document.getElementById('payment-amount');
const methodInput=document.getElementById('selected-payment-method');
const tabs=[...document.querySelectorAll('[data-payment-tab]')];
const panels=[...document.querySelectorAll('[data-payment-panel]')];
const syncAmount=()=>document.querySelectorAll('[data-payment-amount]').forEach(el=>el.textContent=amount.value||'0.00');
const activate=key=>{
 methodInput.value=key;
 tabs.forEach(tab=>{const active=tab.dataset.paymentTab===key;tab.classList.toggle('active',active);tab.setAttribute('aria-selected',active?'true':'false')});
 panels.forEach(panel=>{const active=panel.dataset.paymentPanel===key;panel.hidden=!active;const confirmation=panel.querySelector('[data-notify-confirmation]');const toggle=panel.querySelector('[data-notify-toggle]');if(confirmation){confirmation.hidden=true;confirmation.querySelectorAll('input,button').forEach(el=>el.disabled=true)}if(toggle)toggle.hidden=false});
};
tabs.forEach(tab=>tab.addEventListener('click',()=>activate(tab.dataset.paymentTab)));
plan.addEventListener('change',()=>{amount.value=plan.selectedOptions[0].dataset.openBalance;syncAmount()});
amount.addEventListener('input',syncAmount);
document.querySelectorAll('[data-notify-toggle]').forEach(button=>button.addEventListener('click',()=>{
 const confirmation=button.parentElement.querySelector('[data-notify-confirmation]');
 confirmation.hidden=false;
 confirmation.querySelectorAll('input,button').forEach(el=>el.disabled=false);
 button.hidden=true;
}));
activate(methodInput.value||tabs[0]?.dataset.paymentTab);
syncAmount();
})();
</script>
@endpush
@endsection
