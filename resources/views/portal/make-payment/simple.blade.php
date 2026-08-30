@extends('layouts.app')
@section('title','Make a payment | LandPay')
@section('body_class','admin-page')
@section('content')
<section class="admin-section"><div class="container site-container">
<div class="admin-heading d-flex justify-content-between align-items-end"><div><span class="eyebrow eyebrow-dark">Client portal</span><h1>Make a payment</h1><p class="mb-0">Choose the plan, amount, and payment method.</p></div><a class="btn btn-outline-brand" href="{{ route('portal.dashboard') }}">Dashboard</a></div>
@if($errors->any())<div class="alert alert-danger mt-4">{{ $errors->first() }}</div>@endif
<form class="admin-next-card mt-4" id="payment-form" method="post" action="{{ route('portal.make-payment.store') }}">@csrf
<div class="row g-3">
<div class="col-md-6"><label class="form-label" for="payment-plan">Payment plan</label><select class="form-select" id="payment-plan" name="payment_plan_id">@foreach($plans as $plan)<option value="{{ $plan->id }}" data-open-cents="{{ $planBalances[$plan->id] }}" data-open-balance="{{ number_format($planBalances[$plan->id]/100,2,'.','') }}" @selected((int)$input['payment_plan_id']===$plan->id)>{{ $plan->plan_number }} &mdash; {{ $plan->title }} ({{ \App\Support\Money::format($planBalances[$plan->id]) }} open)</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label" for="payment-amount">Amount</label><div class="input-group"><span class="input-group-text">$</span><input class="form-control" id="payment-amount" name="amount" inputmode="decimal" value="{{ $input['amount'] }}" @readonly(!$general['allow_custom_amount']) required></div><div class="form-text">Total open invoices for this plan. @if($general['allow_custom_amount'])You may enter a different amount.@endif</div></div>
</div>
@if($pendingNotifications->isNotEmpty())
<div data-pending-notifications>
@foreach($plans as $noticePlan)
<div data-notification-plan="{{ $noticePlan->id }}" @if($noticePlan->id !== $selectedPlan->id) hidden @endif>
@foreach($pendingNotifications->where('payment_plan_id',$noticePlan->id) as $pending)
<div class="alert alert-success py-2 px-3 d-flex justify-content-between align-items-center gap-3" data-payment-notification="{{ $pending->uuid }}"><span>Admin notified of {{ \App\Support\Money::format($pending->amount) }} {{ $pending->method_name }} payment.</span><button class="btn btn-sm btn-outline-success flex-shrink-0" type="button" data-cancel-payment-notification="{{ route('portal.make-payment.cancel',$pending) }}">Cancel</button></div>
@endforeach
</div>
@endforeach
</div>
@endif
<h2 class="mt-4">Choose a payment method</h2>
<input type="hidden" name="method" id="selected-method" value="{{ $input['method'] }}">
<div class="portal-payment-tabs" role="tablist">@foreach($methods as $method)<button type="button" class="portal-payment-tab" data-tab="{{ $method['key'] }}" aria-selected="false">{{ $method['name'] }} 
    
@if($method['recommended'])
<span class="badge rounded-pill bg-success-subtle text-success-emphasis border border-success-subtle ms-1"
      style="font-size: 0.8rem; position: relative; top: -2px;">
    ✓ Preferred
</span>
@endif

</button>@endforeach</div>
@foreach($methods as $method)
<section class="portal-payment-panel mt-3" data-panel="{{ $method['key'] }}" hidden>

<h3>{{ $method['name'] }}</h3>
<p class="fs-5">Payment amount: <strong>$<span data-amount>{{ $input['amount'] }}</span></strong></p>

<div data-notice-state hidden class="alert alert-success">
    <span data-notice-message></span>
</div>

@if($method['key']==='card')
@if($general['card_provider']==='square' && $square['experience']==='landpay')
<div id='square-card-container' class='mt-3'></div>
<input type='hidden' name='square_source_id' id='square-source-id'>
<input type='hidden' name='square_card_type' id='square-card-type'>
<div class='border rounded-3 p-3 mt-3' id='square-payment-summary' hidden>
 <div class='d-flex justify-content-between'><span>Payment amount</span><strong>$<span data-square-base>0.00</span></strong></div>
 <div class='d-flex justify-content-between mt-2'><span>Processing Fee:</span><strong>$<span data-square-fee>0.00</span></strong></div>
 <hr><div class='d-flex justify-content-between fs-5'><span>Total card payment</span><strong>$<span data-square-total>0.00</span></strong></div>
</div>
<p class='form-text mt-2'>Any applicable Processing Fee is shown before you confirm. Debit cards are not charged a Processing Fee.</p>
@endif
<p class="form-text fs-6 mt-2 mb-3">
    If convenient, please consider one of the different direct payment methods above.
    Direct payments help avoid fees.
</p>

<p class="form-text mt-2">
    You will continue to secure {{ ucfirst($general['card_provider']) }} checkout.
    LandPay posts the payment after the processor confirms it.
</p>
@else
<div class="border rounded-3 border-start border-1 p-3 mt-3 mb-3 bg-light">
    <div class="d-flex flex-wrap gap-2 mb-2"><span class="badge text-bg-secondary">STEP 1</span><span class="badge text-bg-light border">OPTIONAL</span></div>
    <strong class="fs-5">Notify us about your payment</strong>
    <p class="text-muted mb-0 mt-1">Let us know to watch for your payment and how you would like any overpayment applied. You may skip this step.</p>
</div>

<button  class="btn btn-outline-brand py-2" style="--bs-btn-bg: #f2f4ed;" type="button" data-start-payment>
    Notify Admin I plan to pay $<span data-amount>{{ $input['amount'] }}</span> by {{ $method['name'] }}</button>

<p class="form-text mt-2">
    Admin will post the payment once it is received and verified.
</p>
@endif


<div class="border rounded p-3 mt-3" data-payment-drawer @if($method['key']!=='card') hidden @endif>

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
                @checked(($input['overpayment_disposition'] ?? null) === 'principal')
            >
            <span class="form-check-label">Apply to principal</span>
        </label>

        <label class="form-check mb-0">
            <input
                class="form-check-input"
                type="radio"
                name="overpayment_disposition"
                value="next_invoice_credit"
                @checked(($input['overpayment_disposition'] ?? null) === 'next_invoice_credit')
            >
            <span class="form-check-label">Keep as account credit toward next invoice</span>
        </label>
    </div>
</fieldset>




<label class="form-label mt-2" for="client-note-{{ $method['key'] }}">Payment note (optional)</label>
<input class="form-control" id="client-note-{{ $method['key'] }}" name="client_note" maxlength="1000" value="{{ $input['client_note'] ?? '' }}" @disabled($method['key']!=='card') placeholder="Example: This payment will arrive under the name Billy Jones.">
<button class="btn btn-brand mt-3" type="button" data-send-payment>{{ $method['key']==='card' ? 'Pay Now (Credit Card)' : 'Send notification' }}</button>
</div>
@if($method['key']!=='card')
<div class="border border-warning rounded-3 border-start border-1 p-3 mt-4 mb-3 bg-warning-subtle">
    <span class="badge text-bg-warning mb-2">STEP 2</span>
    <strong class="d-block fs-5 text-warning-emphasis">Make your payment</strong>
    <p class="mb-0 mt-1 text-warning-emphasis">Use the payment information below to complete your payment.</p>
</div>
<div class="portal-payment-instructions text-left border-0 pt-0">
<div class="alert alert-success fw-semibold mt-3 py-2 d-none" data-copy-payment-status role="status" aria-live="polite"></div>
@if($method['key']==='zelle' && $method['recipient'])
<p class="small text-muted">Click the Zelle logo or payment address to copy it.</p>
@if($method['image_url'])<button class="btn border-0 bg-transparent p-0" type="button" data-copy-payment-handle="{{ $method['recipient'] }}" aria-label="Copy Zelle payment address"><img class="portal-payment-logo" src="{{ $method['image_url'] }}" alt="Zelle"></button>@endif
@if($method['link'])<p class="mt-2"><a class="btn btn-sm btn-outline-brand" href="{{ $method['link'] }}" target="_blank" rel="noopener">Open Zelle</a></p>@endif
<p class="mt-3 fs-5">Send $<span data-amount>{{ $input['amount'] }}</span> to <button class="btn btn-link p-0 fw-bold align-baseline" type="button" data-copy-payment-handle="{{ $method['recipient'] }}">{{ $method['recipient'] }}</button> in your banking app.</p>

@else
@if($method['image_url'] && $method['link']) Click the logo:<br><a href="{{ $method['link'] }}" target="_blank" rel="noopener"><img class="portal-payment-logo" src="{{ $method['image_url'] }}" alt="{{ $method['name'] }}"></a>
@elseif($method['image_url'])<img class="portal-payment-logo" src="{{ $method['image_url'] }}" alt="{{ $method['name'] }}">
@elseif($method['link'])<a class="btn btn-outline-brand" href="{{ $method['link'] }}" target="_blank" rel="noopener">Open {{ $method['name'] }}</a>@endif
@if($method['recipient'])<p class="mt-3">Send $<span data-amount>{{ $input['amount'] }}</span> to <strong>{{ $method['recipient'] }}</strong>.</p>@endif
@endif

@if($method['instructions'])<p>{!! nl2br(e($method['instructions'])) !!}</p>@endif
</div>
@endif
</section>
@endforeach
</form>
</div></section>
@if($general['card_provider']==='square' && $square['experience']==='landpay')
@push('scripts')<script src='{{$square['environment']==='live'?'https://web.squarecdn.com/v1/square.js':'https://sandbox.web.squarecdn.com/v1/square.js'}}'></script>@endpush
@endif
@push('scripts')
<script>
(()=>{
const form=document.getElementById('payment-form'),plan=document.getElementById('payment-plan'),amount=document.getElementById('payment-amount'),method=document.getElementById('selected-method');
const tabs=[...document.querySelectorAll('[data-tab]')],panels=[...document.querySelectorAll('[data-panel]')];
const csrf=form.querySelector('[name="_token"]').value;
const squareConfig=@json($square),squareLandPay={{$general['card_provider']==='square'&&$square['experience']==='landpay'?'true':'false'}};let squareCard=null,pendingSquareToken=null;
if(squareLandPay){Square.payments(squareConfig.application_id,squareConfig.location_id).then(async payments=>{squareCard=await payments.card();await squareCard.attach('#square-card-container')}).catch(()=>alert('Secure card entry could not be loaded. Please refresh the page.'))}
const notificationArea=document.querySelector('[data-pending-notifications]');
function syncNotificationGroups(){document.querySelectorAll('[data-notification-plan]').forEach(group=>group.hidden=group.dataset.notificationPlan!==plan.value)}
function notificationGroup(){let area=document.querySelector('[data-pending-notifications]');if(!area){area=document.createElement('div');area.dataset.pendingNotifications='';document.querySelector('h2.mt-4').before(area)}let group=area.querySelector('[data-notification-plan="'+plan.value+'"]');if(!group){group=document.createElement('div');group.dataset.notificationPlan=plan.value;area.appendChild(group)}return group}
function appendNotification(data){const banner=document.createElement('div');banner.className='alert alert-success py-2 px-3 d-flex justify-content-between align-items-center gap-3';banner.dataset.paymentNotification=data.intent_id;const message=document.createElement('span');message.textContent=data.message;const cancel=document.createElement('button');cancel.type='button';cancel.className='btn btn-sm btn-outline-success flex-shrink-0';cancel.dataset.cancelPaymentNotification=data.cancel_url;cancel.textContent='Cancel';banner.append(message,cancel);notificationGroup().prepend(banner);syncNotificationGroups()}
document.addEventListener('click',async event=>{const button=event.target.closest('[data-cancel-payment-notification]');if(!button)return;button.disabled=true;const response=await fetch(button.dataset.cancelPaymentNotification,{method:'DELETE',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':csrf}});if(response.ok){const group=button.closest('[data-notification-plan]');button.closest('[data-payment-notification]').remove();if(group&&!group.querySelector('[data-payment-notification]'))group.remove()}else{button.disabled=false;alert('Unable to cancel this notification.')}});
async function copyPaymentHandle(button){const value=button.dataset.copyPaymentHandle;try{if(navigator.clipboard&&window.isSecureContext){await navigator.clipboard.writeText(value)}else{const area=document.createElement('textarea');area.value=value;area.style.position='fixed';area.style.opacity='0';document.body.appendChild(area);area.select();document.execCommand('copy');area.remove()}const status=button.closest('[data-panel]').querySelector('[data-copy-payment-status]');
        if(status){
            status.textContent='Copied '+value;
            status.classList.remove('d-none');

            setTimeout(()=>{
                status.textContent='';
                status.classList.add('d-none');
            },2500);
        }
    }catch{alert('Unable to copy automatically. Select and copy '+value+'.')}}
document.querySelectorAll('[data-copy-payment-handle]').forEach(button=>button.addEventListener('click',()=>copyPaymentHandle(button)));
const states=@json($activeStates);let active=null;
const cents=v=>Math.round((parseFloat(v)||0)*100),money=v=>(v/100).toFixed(2);
function sync(){document.querySelectorAll('[data-amount]').forEach(el=>el.textContent=money(cents(amount.value)));updateExtra();}
function resetSquare(){pendingSquareToken=null;const summary=document.getElementById('square-payment-summary');if(summary)summary.hidden=true;const button=document.querySelector('[data-panel=card] [data-send-payment]');if(button)button.textContent='Pay Now (Credit Card)'}
function squareFee(base,type){if(!squareConfig.enabled||type!=='CREDIT')return 0;const rate=Number(squareConfig.basis_points),fixed=Number(squareConfig.fixed_amount);let fee=squareConfig.adjust?Math.ceil(((base+fixed)*10000)/(10000-rate))-base:Math.round((base*rate)/10000)+fixed;if(squareConfig.cap!==null)fee=Math.min(fee,Number(squareConfig.cap));return Math.max(0,fee)}
function updateExtra(){const panel=document.querySelector('[data-panel="'+method.value+'"]'),choice=panel?.querySelector('[data-overpayment-choice]');if(!choice)return;const extra=Math.max(0,cents(amount.value)-Number(plan.selectedOptions[0].dataset.openCents));choice.hidden=extra===0;choice.querySelector('[data-extra-amount]').textContent=money(extra);}
function lock(value){amount.readOnly=value||{{ $general['allow_custom_amount']?'false':'true' }};tabs.forEach(t=>t.disabled=value);}
function showActive(){panels.forEach(p=>{p.querySelector('[data-notice-state]').hidden=true;const start=p.querySelector('[data-start-payment]');if(start)start.hidden=false});active=states[plan.value]||null;if(!active){lock(false);return}amount.value=active.amount;activate(active.method);lock(true);const panel=document.querySelector('[data-panel="'+active.method+'"]');panel.querySelector('[data-start-payment]').hidden=true;const state=panel.querySelector('[data-notice-state]');state.hidden=false;state.querySelector('[data-notice-message]').textContent=active.message;sync();}
function activate(key){method.value=key;tabs.forEach(t=>{const on=t.dataset.tab===key;t.classList.toggle('active',on);t.setAttribute('aria-selected',on?'true':'false')});panels.forEach(p=>{const card=p.dataset.panel==='card',start=p.querySelector('[data-start-payment]');p.hidden=p.dataset.panel!==key;if(start)start.hidden=false;p.querySelector('[data-payment-drawer]').hidden=!card;p.querySelectorAll('[name="client_note"]').forEach(i=>i.disabled=!card)});sync();}
tabs.forEach(t=>t.addEventListener('click',()=>activate(t.dataset.tab)));
plan.addEventListener('change',()=>{amount.value=money(Number(plan.selectedOptions[0].dataset.openCents));resetSquare();activate(method.value);showActive();syncNotificationGroups();sync()});amount.addEventListener('input',()=>{resetSquare();sync()});
document.querySelectorAll('[data-start-payment]').forEach(b=>b.addEventListener('click',()=>{const p=b.closest('[data-panel]'),d=p.querySelector('[data-payment-drawer]');b.hidden=true;d.hidden=false;d.querySelector('[name="client_note"]').disabled=false;updateExtra()}));
document.querySelectorAll('[data-send-payment]').forEach(b=>b.addEventListener('click',async()=>{
 const panel=b.closest('[data-panel]'),extra=cents(amount.value)>Number(plan.selectedOptions[0].dataset.openCents),choice=panel.querySelector('[name="overpayment_disposition"]:checked');
 if(extra&&!choice){alert('Choose how the extra amount should be used.');return}
 panel.querySelectorAll('[data-payment-drawer] input').forEach(i=>i.disabled=i.name==='overpayment_disposition'?!extra:false);
 if(method.value==='card'){if(!squareLandPay){form.submit();return}if(pendingSquareToken){form.submit();return}if(!squareCard){alert('Secure card entry is still loading.');return}b.disabled=true;const result=await squareCard.tokenize();b.disabled=false;if(result.status!=='OK'){alert(result.errors?.[0]?.message||'Check the card information and try again.');return}const type=result.details?.card?.cardType||'UNKNOWN',base=cents(amount.value),fee=squareFee(base,type),total=base+fee;pendingSquareToken=result.token;document.getElementById('square-source-id').value=result.token;document.getElementById('square-card-type').value=type;document.querySelector('[data-square-base]').textContent=money(base);document.querySelector('[data-square-fee]').textContent=money(fee);document.querySelector('[data-square-total]').textContent=money(total);document.getElementById('square-payment-summary').hidden=false;b.textContent='Pay $'+money(total);return}
 b.disabled=true;const response=await fetch(form.action,{method:'POST',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':csrf},body:new FormData(form)});
 if(response.redirected){window.location.assign(response.url);return}
 const contentType=response.headers.get('content-type')||'';if(!contentType.includes('application/json')){b.disabled=false;alert(response.status===403?'This portal is open in administrator read-only mode. Payment notifications can only be submitted when the client is signed in with their own account.':'Unable to notify the administrator (HTTP '+response.status+'). Please refresh the page and try again.');return}
 const data=await response.json();b.disabled=false;
 if(!response.ok){alert(data.message||'Unable to notify the administrator.');return}
 panel.querySelector('[data-payment-drawer]').hidden=true;appendNotification(data);panel.querySelector('[name="client_note"]').value='';
}));
document.querySelectorAll('[data-cancel-notice]').forEach(b=>b.addEventListener('click',async()=>{if(!active)return;b.disabled=true;const response=await fetch(active.cancel_url,{method:'DELETE',headers:{Accept:'application/json','X-CSRF-TOKEN':csrf}});b.disabled=false;if(!response.ok)return;delete states[active.plan];active=null;amount.value=money(Number(plan.selectedOptions[0].dataset.openCents));showActive();sync();}));
activate(method.value||tabs[0]?.dataset.tab);showActive();syncNotificationGroups();sync();
})();
</script>
@endpush
@endsection
