@extends('layouts.app')
@section('title','Make a payment | LandPay')
@section('body_class','admin-page')
@section('content')
<section class="admin-section"><div class="container site-container">
<div class="admin-heading d-flex justify-content-between align-items-end"><div><span class="eyebrow eyebrow-dark">Client portal</span><h1>Make a payment</h1><p class="mb-0">Choose the plan, amount, and payment method.</p></div><a class="btn btn-outline-brand" href="{{ route('portal.dashboard') }}">Dashboard</a></div>
@if($errors->any())<div class="alert alert-danger mt-4">{{ $errors->first() }}</div>@endif
<form class="admin-next-card mt-4" id="payment-form" method="post" action="{{ route('portal.make-payment.store') }}">@csrf
<div class="row g-3">
<div class="col-md-6"><label class="form-label" for="payment-plan">Payment plan</label><select class="form-select" id="payment-plan" name="payment_plan_id">@foreach($plans as $plan)<option value="{{ $plan->id }}" data-open-cents="{{ $planBalances[$plan->id] }}" data-open-balance="{{ number_format($planBalances[$plan->id]/100,2,'.','') }}" @selected((int)$input['payment_plan_id']===$plan->id)>{{ $plan->plan_number }}  {{ $plan->title }} ({{ \App\Support\Money::format($planBalances[$plan->id]) }} open)</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label" for="payment-amount">Amount</label><div class="input-group"><span class="input-group-text">$</span><input class="form-control" id="payment-amount" name="amount" inputmode="decimal" value="{{ $input['amount'] }}" @readonly(!$general['allow_custom_amount']) required></div><div class="form-text">Total open invoices for this plan. @if($general['allow_custom_amount'])You may enter a different amount.@endif</div></div>
</div>
<h2 class="mt-4">Choose a payment method</h2>
<input type="hidden" name="method" id="selected-method" value="{{ $input['method'] }}">
<div class="portal-payment-tabs" role="tablist">@foreach($methods as $method)<button type="button" class="portal-payment-tab" data-tab="{{ $method['key'] }}" aria-selected="false">{{ $method['name'] }} @if($method['recommended'])<span class="badge text-bg-success">Recommended</span>@endif</button>@endforeach</div>
@foreach($methods as $method)
<section class="portal-payment-panel mt-3" data-panel="{{ $method['key'] }}" hidden>
<h3>{{ $method['name'] }}</h3>
<p>Payment amount: <strong>$<span data-amount>{{ $input['amount'] }}</span></strong></p>
<div data-notice-state hidden class="alert alert-success"><div class="d-flex justify-content-between align-items-center"><span data-notice-message></span><button type="button" class="btn btn-sm btn-outline-secondary" data-cancel-notice>Cancel</button></div></div>
@if($method['key']==='card')
<button class="btn btn-brand" type="button" data-start-payment>Pay Now (Credit Card)</button>
<p class="form-text mt-2">You will continue to secure {{ ucfirst($general['card_provider']) }} checkout. LandPay posts the payment after the processor confirms it.</p>
@else
<button class="btn btn-brand" type="button" data-start-payment>Notify Admin of $<span data-amount>{{ $input['amount'] }}</span> {{ $method['name'] }} Payment</button>
<p class="form-text mt-2">Admin will post the payment once it is received and verified.<br><b>Note:</b> Notification is optional, you may simply send payment.</p>
@endif
<div class="border rounded p-3 mt-3" data-payment-drawer hidden>

<fieldset
    data-overpayment-choice
    hidden
    class="border bg-warning-subtle border-warning-subtle rounded-3 p-3 mb-3"
>
    <legend class="float-none w-auto px-2 fs-5 fw-semibold text-warning-emphasis">
        How should the extra $<span data-extra-amount>0.00</span> be used?
    </legend>

    <div class="d-flex flex-wrap gap-3">
        <label class="form-check mb-0">
            <input
                class="form-check-input"
                type="radio"
                name="overpayment_disposition"
                value="principal"
            >
            <span class="form-check-label">Apply to principal</span>
        </label>

        <label class="form-check mb-0">
            <input
                class="form-check-input"
                type="radio"
                name="overpayment_disposition"
                value="next_invoice_credit"
            >
            <span class="form-check-label">Keep as account credit toward next invoice</span>
        </label>
    </div>
</fieldset>




<label class="form-label mt-2" for="client-note-{{ $method['key'] }}">Payment note (optional)</label>
<input class="form-control" id="client-note-{{ $method['key'] }}" name="client_note" maxlength="1000" disabled placeholder="Example: This payment will arrive under the name Billy Jones.">
<button class="btn btn-brand mt-3" type="button" data-send-payment>{{ $method['key']==='card' ? 'Continue to secure checkout' : 'Send notification' }}</button>
</div>
@if($method['key']!=='card')
<div class="d-flex align-items-center gap-3 my-4"><hr class="flex-grow-1"><span class="small fw-bold text-muted">THEN</span><hr class="flex-grow-1"></div>
<div class="portal-payment-instructions text-left">
@if($method['image_url'] && $method['link']) Click the logo:<br><a href="{{ $method['link'] }}" target="_blank" rel="noopener"><img class="portal-payment-logo" src="{{ $method['image_url'] }}" alt="{{ $method['name'] }}"></a>
@elseif($method['image_url'])<img class="portal-payment-logo" src="{{ $method['image_url'] }}" alt="{{ $method['name'] }}">
@elseif($method['link'])<a class="btn btn-outline-brand" href="{{ $method['link'] }}" target="_blank" rel="noopener">Open {{ $method['name'] }}</a>@endif
@if($method['recipient'])<p class="mt-3">Send $<span data-amount>{{ $input['amount'] }}</span> to <strong>{{ $method['recipient'] }}</strong>.</p>@endif
@if($method['instructions'])<p>{!! nl2br(e($method['instructions'])) !!}</p>@endif
</div>
@endif
</section>
@endforeach
</form>
</div></section>
@push('scripts')
<script>
(()=>{
const form=document.getElementById('payment-form'),plan=document.getElementById('payment-plan'),amount=document.getElementById('payment-amount'),method=document.getElementById('selected-method');
const tabs=[...document.querySelectorAll('[data-tab]')],panels=[...document.querySelectorAll('[data-panel]')];
const csrf=form.querySelector('[name="_token"]').value;
const states=@json($activeStates);let active=null;
const cents=v=>Math.round((parseFloat(v)||0)*100),money=v=>(v/100).toFixed(2);
function sync(){document.querySelectorAll('[data-amount]').forEach(el=>el.textContent=money(cents(amount.value)));updateExtra();}
function updateExtra(){const panel=document.querySelector('[data-panel="'+method.value+'"]'),choice=panel?.querySelector('[data-overpayment-choice]');if(!choice)return;const extra=Math.max(0,cents(amount.value)-Number(plan.selectedOptions[0].dataset.openCents));choice.hidden=extra===0;choice.querySelector('[data-extra-amount]').textContent=money(extra);}
function lock(value){amount.readOnly=value||{{ $general['allow_custom_amount']?'false':'true' }};tabs.forEach(t=>t.disabled=value);}
function showActive(){panels.forEach(p=>{p.querySelector('[data-notice-state]').hidden=true;p.querySelector('[data-start-payment]').hidden=false});active=states[plan.value]||null;if(!active){lock(false);return}amount.value=active.amount;activate(active.method);lock(true);const panel=document.querySelector('[data-panel="'+active.method+'"]');panel.querySelector('[data-start-payment]').hidden=true;const state=panel.querySelector('[data-notice-state]');state.hidden=false;state.querySelector('[data-notice-message]').textContent=active.message;sync();}
function activate(key){method.value=key;tabs.forEach(t=>{const on=t.dataset.tab===key;t.classList.toggle('active',on);t.setAttribute('aria-selected',on?'true':'false')});panels.forEach(p=>{p.hidden=p.dataset.panel!==key;p.querySelector('[data-payment-drawer]').hidden=true;p.querySelectorAll('[name="client_note"]').forEach(i=>i.disabled=true)});sync();}
tabs.forEach(t=>t.addEventListener('click',()=>activate(t.dataset.tab)));
plan.addEventListener('change',()=>{amount.value=money(Number(plan.selectedOptions[0].dataset.openCents));activate(method.value);showActive();sync()});amount.addEventListener('input',sync);
document.querySelectorAll('[data-start-payment]').forEach(b=>b.addEventListener('click',()=>{const p=b.closest('[data-panel]'),d=p.querySelector('[data-payment-drawer]');d.hidden=false;d.querySelector('[name="client_note"]').disabled=false;updateExtra()}));
document.querySelectorAll('[data-send-payment]').forEach(b=>b.addEventListener('click',async()=>{
 const panel=b.closest('[data-panel]'),extra=cents(amount.value)>Number(plan.selectedOptions[0].dataset.openCents),choice=panel.querySelector('[name="overpayment_disposition"]:checked');
 if(extra&&!choice){alert('Choose how the extra amount should be used.');return}
 panel.querySelectorAll('[data-payment-drawer] input').forEach(i=>i.disabled=i.name==='overpayment_disposition'?!extra:false);
 if(method.value==='card'){form.submit();return}
 b.disabled=true;const response=await fetch(form.action,{method:'POST',headers:{Accept:'application/json','X-CSRF-TOKEN':csrf},body:new FormData(form)});const data=await response.json();b.disabled=false;
 if(!response.ok){alert(data.message||'Unable to notify the administrator.');return}
 active={id:data.intent_id,plan:plan.value,method:method.value,amount:money(cents(amount.value)),message:data.message,cancel_url:data.cancel_url};states[plan.value]=active;panel.querySelector('[data-payment-drawer]').hidden=true;showActive();
}));
document.querySelectorAll('[data-cancel-notice]').forEach(b=>b.addEventListener('click',async()=>{if(!active)return;b.disabled=true;const response=await fetch(active.cancel_url,{method:'DELETE',headers:{Accept:'application/json','X-CSRF-TOKEN':csrf}});b.disabled=false;if(!response.ok)return;delete states[active.plan];active=null;amount.value=money(Number(plan.selectedOptions[0].dataset.openCents));showActive();sync();}));
activate(method.value||tabs[0]?.dataset.tab);showActive();sync();
})();
</script>
@endpush
@endsection

