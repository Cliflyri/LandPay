@extends('layouts.admin')
@section('title',isset($plan) ? 'Revise contracts | LandPay' : 'New contract setup | LandPay')
@section('body_class','admin-page')
@section('content')
@php
$value = fn ($key, $default = null) => old($key, array_key_exists($key, $prefill ?? []) ? $prefill[$key] : $default);
$contractClientOptions = $clients->map(function ($client) {
    return ['id' => $client->id, 'label' => $client->organization_name ?: trim($client->first_name.' '.$client->last_name), 'email' => $client->email, 'phone' => $client->primary_phone];
})->values();
$contractStageOneType = $defaults?->stage_one_fee_type?->value ?? 'fixed';
$contractStageOneValue = $contractStageOneType === 'percentage' ? ($defaults?->stage_one_percentage_rate ?? '0.0000') : number_format(($defaults?->stage_one_fixed_amount ?? 2500) / 100, 2, '.', '');
$contractStageTwoType = $defaults?->stage_two_fee_type?->value ?? 'fixed';
$contractStageTwoValue = $contractStageTwoType === 'percentage' ? ($defaults?->stage_two_percentage_rate ?? '0.0000') : number_format(($defaults?->stage_two_fixed_amount ?? 5000) / 100, 2, '.', '');
@endphp

<section class="admin-section"><div class="container-fluid dashboard-container px-2">
<div class="admin-heading"><span class="eyebrow eyebrow-dark">Clients and plans</span><h1>{{ isset($plan) ? 'Revise setup & regenerate contracts' : 'New contract setup' }}</h1><p class="mb-0">{{ isset($plan) ? 'Update this plan and upload Word templates to generate revised contracts. Existing clients stay attached; a co-client can be added.' : 'Create the client, draft plan, billing schedule, and ready-to-download contracts in one step.' }}</p></div>

@if($errors->any())<div class="alert alert-danger mt-4"><strong>Changes were not saved.</strong><div>{{ $errors->first() }}</div></div>@endif

<form id="contract-setup-form" class="mt-4" method="post" action="{{ isset($plan) ? route('admin.contract-setups.update',$plan) : route('admin.contract-setups.store') }}" enctype="multipart/form-data">
@csrf
@if(isset($plan)) @method('PUT') @endif
<div class="admin-next-card">
    <h2>1. Primary client</h2>
    <div class="row g-3 mt-1">
        <div class="col-md-4"><label class="form-label">Use</label><select class="form-select mode-select" name="primary_mode" data-prefix="primary"><option value="existing" @selected($value('primary_mode', $selectedClient ? 'existing' : 'new') === 'existing')>Existing client</option><option value="new" @selected($value('primary_mode', $selectedClient ? 'existing' : 'new') === 'new')>New client</option></select></div>
        <div class="d-none" aria-hidden="true">
        <input type="hidden" name="primary_client_id" value="{{ $value('primary_client_id',$selectedClient) }}">
        </div>
        <div class="col-md-8 mode-existing" data-owner="primary"><label class="form-label" for="contract_primary_client_search">Client</label><div class="client-picker" data-contract-client-picker="primary"><input class="form-control client-search" id="contract_primary_client_search" autocomplete="off" placeholder="Start typing a client name, email, or phone"><div class="client-results list-group"></div><div class="selected-client mt-2"></div></div></div>
    </div>
    <div class="row g-3 mt-1 mode-new" data-owner="primary">
        <div class="col-md-3"><label class="form-label">Type</label><select class="form-select" name="primary_client_type"><option value="individual" @selected($value('primary_client_type','individual')==='individual')>Individual</option><option value="organization" @selected($value('primary_client_type')==='organization')>Organization</option></select></div>
        <div class="col-md-9"><label class="form-label">Organization name</label><input class="form-control" name="primary_organization_name" value="{{$value('primary_organization_name')}}"></div>
        <div class="col-md-6"><label class="form-label">First name</label><input class="form-control" name="primary_first_name" value="{{$value('primary_first_name')}}"></div>
        <div class="col-md-6"><label class="form-label">Last name</label><input class="form-control" name="primary_last_name" value="{{$value('primary_last_name')}}"></div>
        <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="primary_email" value="{{$value('primary_email')}}"></div>
        <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="primary_phone" value="{{$value('primary_phone')}}"></div>
        <div class="col-md-8"><label class="form-label">Address</label><input class="form-control" name="primary_address_line_1" value="{{$value('primary_address_line_1')}}"></div>
        <div class="col-md-4"><label class="form-label">Address line 2</label><input class="form-control" name="primary_address_line_2" value="{{$value('primary_address_line_2')}}"></div>
        <div class="col-md-5"><label class="form-label">City</label><input class="form-control" name="primary_city" value="{{$value('primary_city')}}"></div>
        <div class="col-md-3"><label class="form-label">State</label><input class="form-control" name="primary_state_region" value="{{$value('primary_state_region','AZ')}}"></div>
        <div class="col-md-4"><label class="form-label">ZIP code</label><input class="form-control" name="primary_postal_code" value="{{$value('primary_postal_code')}}"></div>
    </div>
</div>

@include('admin.contract-setups.co-client')

<div class="admin-next-card mt-4">
    <h2>3. Property and contract</h2>
    <div class="row g-3 mt-1">
        <div class="col-md-4"><label class="form-label">APN / Plan number</label><input class="form-control" name="plan_number" value="{{$value('plan_number')}}" required></div>
        <div class="col-md-8"><label class="form-label">Property title</label><input class="form-control" name="property_title" value="{{$value('property_title')}}" required></div>
        <div class="col-md-8"><label class="form-label">Property description / commonly known as</label><textarea class="form-control" rows="2" name="property_description">{{$value('property_description')}}</textarea></div>
        <div class="col-md-4"><label class="form-label" for="property-county">County <span class="text-muted fw-normal">(optional)</span></label><input class="form-control" id="property-county" name="property_county" list="property-counties" maxlength="100" value="{{$value('property_county')}}" placeholder="Choose or type a county"><datalist id="property-counties">@foreach($counties as $county)<option value="{{$county}}"></option>@endforeach</datalist></div>
        <div class="col-md-4"><label class="form-label">Purchase price</label><div class="input-group"><span class="input-group-text">$</span><input class="form-control money-input" name="purchase_price" inputmode="decimal" value="{{$value('purchase_price')}}" required></div></div>
        <div class="col-md-4"><label class="form-label">Down/first payment</label><div class="input-group"><span class="input-group-text">$</span><input class="form-control money-input" name="down_payment" inputmode="decimal" value="{{$value('down_payment','0.00')}}" required></div><small class="text-muted">Contract term only; no payment is recorded.</small></div>
        <div class="col-md-4"><label class="form-label">Documentation fee</label><div class="input-group"><span class="input-group-text">$</span><input class="form-control money-input" name="documentation_fee" inputmode="decimal" value="{{$value('documentation_fee','0.00')}}" required></div></div>
        <div class="col-md-4"><label class="form-label">Plan payment</label><div class="input-group"><span class="input-group-text">$</span><input class="form-control money-input" name="plan_payment" inputmode="decimal" value="{{$value('plan_payment',$defaults ? number_format($defaults->scheduled_payment_amount/100,2,'.','') : '')}}" required></div></div>
        <div class="col-md-4"><label class="form-label">Monthly service fee</label><div class="input-group"><span class="input-group-text">$</span><input class="form-control money-input" name="service_fee" inputmode="decimal" value="{{$value('service_fee',$defaults ? number_format($defaults->monthly_service_fee/100,2,'.','') : '15.00')}}" required></div></div>
        <div class="col-md-4"><label class="form-label">Total monthly payment</label><div class="input-group"><span class="input-group-text">$</span><input class="form-control" id="total_monthly_payment" value="0.00" readonly tabindex="-1"></div><small class="text-muted">Plan payment plus monthly service fee.</small></div>
        <div class="col-md-4"><label class="form-label">HOA fee <span class="text-muted">(optional)</span></label><div class="input-group"><span class="input-group-text">$</span><input class="form-control" name="hoa_fee" inputmode="decimal" value="{{$value('hoa_fee')}}"></div></div>
        <div class="col-md-4"><label class="form-label">HOA term <span class="text-muted">(optional)</span></label><input class="form-control" name="hoa_term" value="{{$value('hoa_term')}}"></div>
        <div class="col-md-4 d-flex align-items-end"><div class="form-check mb-2"><input class="form-check-input" type="checkbox" value="1" name="govdeals" id="govdeals" @checked($value('govdeals'))><label class="form-check-label" for="govdeals">Purchased through GovDeals</label></div></div>
        <div class="col-md-4"><label class="form-label">Contract start date</label><input class="form-control" type="date" name="contract_start_date" value="{{$value('contract_start_date',today()->format('Y-m-d'))}}" required></div>
        <div class="col-md-4"><label class="form-label">First recurring invoice date</label><input class="form-control" type="date" name="first_invoice_date" value="{{$value('first_invoice_date')}}" required><small class="text-muted">The first regular monthly invoice date; its day becomes the recurring invoice day.</small></div>
        <div class="col-md-4"><label class="form-label">Down/first payment invoice due date <span class="text-muted">(optional)</span></label><input class="form-control" type="date" name="first_payment_due_date" value="{{$value('first_payment_due_date')}}"><small class="text-muted">Leave blank to use the standard payment window after activation.</small></div>
        <div class="col-md-4"><label class="form-label">Payment due after</label><div class="input-group"><input class="form-control" type="number" name="due_days_after_issue" min="0" max="60" value="{{$value('due_days_after_issue',$defaults?->due_days_after_issue ?? 5)}}" required><span class="input-group-text">days</span></div></div>
        <div class="col-md-4"><label class="form-label">Grace period</label><div class="input-group"><input class="form-control" type="number" name="grace_days" min="0" max="60" value="{{$value('grace_days',$defaults?->grace_days ?? 0)}}" required><span class="input-group-text">days</span></div></div>
        <div class="col-md-4"><label class="form-label">Stage-one late fee</label><select class="form-select" name="stage_one_fee_type"><option value="fixed" @selected($value('stage_one_fee_type',$contractStageOneType)==='fixed')>Fixed amount</option><option value="percentage" @selected($value('stage_one_fee_type',$contractStageOneType)==='percentage')>Percentage</option></select></div>
        <div class="col-md-4"><label class="form-label">Stage-one value</label><input class="form-control" name="stage_one_fee_value" value="{{$value('stage_one_fee_value',$contractStageOneValue)}}" required></div>
        <div class="col-md-4"><label class="form-label">Stage-one percentage minimum</label><input class="form-control" name="stage_one_minimum_amount" value="{{$value('stage_one_minimum_amount',number_format(($defaults?->stage_one_minimum_amount ?? 0)/100,2,'.',''))}}"></div>
        <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" value="1" name="stage_two_enabled" id="contract_stage_two_enabled" @checked($value('stage_two_enabled',$defaults?->stage_two_enabled ?? true))><label class="form-check-label" for="contract_stage_two_enabled">Enable stage-two late fee</label></div></div>
        <div class="col-md-4"><label class="form-label">Stage-two timing</label><div class="input-group"><input class="form-control" type="number" name="stage_two_days_late" min="1" max="365" value="{{$value('stage_two_days_late',$defaults?->stage_two_days_late ?? 30)}}"><span class="input-group-text">days late</span></div></div>
        <div class="col-md-4"><label class="form-label">Stage-two calculation</label><select class="form-select" name="stage_two_fee_type"><option value="fixed" @selected($value('stage_two_fee_type',$contractStageTwoType)==='fixed')>Fixed amount</option><option value="percentage" @selected($value('stage_two_fee_type',$contractStageTwoType)==='percentage')>Percentage</option></select></div>
        <div class="col-md-4"><label class="form-label">Stage-two value</label><input class="form-control" name="stage_two_fee_value" value="{{$value('stage_two_fee_value',$contractStageTwoValue)}}"><input type="hidden" name="stage_two_minimum_amount" value="{{$value('stage_two_minimum_amount',number_format(($defaults?->stage_two_minimum_amount ?? 0)/100,2,'.',''))}}"><input type="hidden" name="default_eligibility_days" value="{{$value('default_eligibility_days',$defaults?->default_eligibility_days ?? 60)}}"></div>
        <div class="col-12 mt-2">
            <div class="alert alert-warning mb-0">
                <h3 class="h6 mb-2">Down/first payment invoice on activation</h3>
                <div class="form-check"><input class="form-check-input" type="checkbox" value="1" name="create_first_payment_invoice" id="create_first_payment_invoice" @checked($value('create_first_payment_invoice', true))><label class="form-check-label" for="create_first_payment_invoice"><strong>Create a down/first payment invoice when this plan is activated</strong></label></div>
                <small class="d-block text-muted ms-4">Lists the down payment and documentation fee separately and allows at least three days before it is late.</small>
                <div class="form-check mt-2"><input class="form-check-input" type="checkbox" value="1" name="email_first_payment_invoice" id="email_first_payment_invoice" @checked($value('email_first_payment_invoice', false))><label class="form-check-label" for="email_first_payment_invoice"><strong>Email this invoice when the plan is activated</strong></label></div>
            </div>
        </div>
    </div>
</div>

@if(isset($plan))
<div class="admin-next-card mt-4"><h2>Revision details</h2><div class="row g-3 mt-1">
<div class="col-md-4"><label class="form-label" for="effective_from">Amendment effective date</label><input class="form-control" id="effective_from" name="effective_from" type="date" value="{{ $value('effective_from',today()->toDateString()) }}" required></div>
<div class="col-md-8"><label class="form-label" for="amendment_reason">Reason for revision</label><input class="form-control" id="amendment_reason" name="amendment_reason" maxlength="500" value="{{ $value('amendment_reason') }}" required></div>
</div><p class="text-muted mt-3 mb-0">Existing invoices and payments are preserved. Changes to purchase price or documentation fee amend the contract balance. Draft changes use the contract start date.</p></div>
@endif
<div class="admin-next-card mt-4">
    <h2>4. Contract templates</h2>
    <p class="text-muted">Upload one or more Word templates using the existing placeholders. Generated contracts are private and automatically deleted after 30 days.</p>
    <input class="form-control" type="file" name="contract_templates[]" accept=".docx" multiple required>
</div>

@if(isset($plan))
<div class="alert alert-info mt-4 mb-0">This updates plan {{ $plan->plan_number }} and keeps its status and history. Previous contracts are marked superseded and keep their original 30-day expiry. Re-upload your templates whenever you regenerate.</div>
@else
<div class="alert alert-info mt-4 mb-0"><strong>No money is recorded by this setup.</strong> It creates an opening contract balance, but no payment, credit, adjustment, or invoice. The plan remains a draft until you activate it.</div>
@endif
<div class="d-flex flex-wrap gap-2 mt-4"><button class="btn btn-brand" type="button" id="review-setup">Review setup</button><a class="btn btn-outline-brand" href="{{isset($plan) ? route('admin.plans.show',$plan) : route('admin.clients.index')}}">Cancel</a></div>
</form>
</div></section>

<div class="modal fade" id="contractReview" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h2 class="modal-title fs-4">Confirm contract setup</h2><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p class="mb-3">Review the clients and terms before saving and generating contracts.</p><dl class="row mb-0" id="review-details"></dl><div class="alert alert-warning mt-3 mb-0">{{ isset($plan) ? 'This updates the existing plan and generates new contracts. Review any financial changes carefully.' : 'This creates a draft plan. It does not record any money or issue an invoice.' }}</div></div><div class="modal-footer"><button class="btn btn-outline-brand" type="button" data-bs-dismiss="modal">Go back</button><button class="btn btn-brand" type="submit" form="contract-setup-form">{{ isset($plan) ? 'Save changes and regenerate contracts' : 'Create setup and contracts' }}</button></div></div></div></div>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{
 const form=document.getElementById('contract-setup-form');
 const field=n=>form.elements[n];
 // Use a page-owned dropdown; native datalist popups are unreliable on this form.
 const countyInput=document.getElementById('property-county');
 const countyNames=Array.from(document.getElementById('property-counties').options,option=>option.value);
 const countyResults=document.createElement('div');
 countyResults.id='county-suggestions';countyResults.className='list-group mt-1';countyResults.hidden=true;
 countyResults.setAttribute('role','listbox');countyResults.setAttribute('aria-label','Existing counties');
 countyInput.after(countyResults);countyInput.removeAttribute('list');countyInput.autocomplete='off';
 countyInput.setAttribute('role','combobox');countyInput.setAttribute('aria-autocomplete','list');
 countyInput.setAttribute('aria-controls',countyResults.id);countyInput.setAttribute('aria-expanded','false');
 let countyIndex=-1;
 const closeCounties=()=>{countyResults.hidden=true;countyInput.setAttribute('aria-expanded','false');countyInput.removeAttribute('aria-activedescendant');countyIndex=-1;};
 const chooseCounty=name=>{countyInput.value=name;closeCounties();countyInput.dispatchEvent(new Event('change',{bubbles:true}));};
 const showCounties=()=>{
   closeCounties();countyResults.replaceChildren();
   const query=countyInput.value.trim().toLowerCase();
   if(!query)return;
   countyNames.filter(name=>name.toLowerCase().includes(query)).slice(0,8).forEach((name,index)=>{
     const option=document.createElement('button');option.type='button';option.tabIndex=-1;
     option.id='county-option-'+index;option.className='list-group-item list-group-item-action';option.textContent=name;
     option.setAttribute('role','option');option.setAttribute('aria-selected','false');
     option.addEventListener('mousedown',event=>event.preventDefault());
     option.addEventListener('click',()=>chooseCounty(name));countyResults.appendChild(option);
   });
   countyResults.hidden=!countyResults.children.length;countyInput.setAttribute('aria-expanded',String(!countyResults.hidden));
 };
 countyInput.addEventListener('input',showCounties);countyInput.addEventListener('focus',showCounties);
 countyInput.addEventListener('blur',closeCounties);
 countyInput.addEventListener('keydown',event=>{
   if(event.key==='Escape'){if(!countyResults.hidden){event.preventDefault();closeCounties();}return;}
   if(event.key==='Enter'&&!countyResults.hidden&&countyIndex>=0){event.preventDefault();chooseCounty(countyResults.children[countyIndex].textContent);return;}
   if(!['ArrowDown','ArrowUp'].includes(event.key))return;
   if(countyResults.hidden)showCounties();
   const options=Array.from(countyResults.children);if(countyResults.hidden||!options.length)return;
   event.preventDefault();countyIndex=countyIndex<0?(event.key==='ArrowDown'?0:options.length-1):(countyIndex+(event.key==='ArrowDown'?1:-1)+options.length)%options.length;
   options.forEach((option,index)=>{option.classList.toggle('active',index===countyIndex);option.setAttribute('aria-selected',String(index===countyIndex));});
   countyInput.setAttribute('aria-activedescendant',options[countyIndex].id);
 });

 const escapeHtml=value=>String(value).replace(/[&<>"']/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
 @include('admin.contract-setups.client-picker')
 @if(!empty($prefill['co_client_id']))
 field('co_effective_from').readOnly=true;
 document.getElementById('co_receives_invoices').disabled=true;
 @endif
 const createFirstPaymentInvoice=field('create_first_payment_invoice'),emailFirstPaymentInvoice=field('email_first_payment_invoice');
 const syncFirstPaymentInvoice=()=>{
   emailFirstPaymentInvoice.disabled=!createFirstPaymentInvoice.checked;
   if(!createFirstPaymentInvoice.checked)emailFirstPaymentInvoice.checked=false;
 };
 createFirstPaymentInvoice.addEventListener('change',syncFirstPaymentInvoice);syncFirstPaymentInvoice();
 @if(isset($plan) && $plan->status !== 'draft')
 field('first_invoice_date').readOnly=true;
 ['first_payment_due_date','create_first_payment_invoice','email_first_payment_invoice'].forEach(name=>{field(name).disabled=true;});
 @endif
 const monthlyTotal=document.getElementById('total_monthly_payment');
 const updateMonthlyTotal=()=>{
   const value=input=>Number(String(input.value||0).replace(/[$,]/g,''))||0;
   monthlyTotal.value=(value(field('plan_payment'))+value(field('service_fee'))).toFixed(2);
 };
 ['plan_payment','service_fee'].forEach(name=>field(name).addEventListener('input',updateMonthlyTotal));updateMonthlyTotal();
 document.getElementById('review-setup').addEventListener('click',()=>{
   if(!form.reportValidity())return;
   if(field('primary_mode').value==='existing'&&!field('primary_client_id').value){alert('Select a primary client from the search results.');document.getElementById('contract_primary_client_search').focus();return;}
   if(field('co_mode').value==='existing'&&!field('co_client_id').value){alert('Select a second client from the search results.');document.getElementById('contract_co_client_search').focus();return;}
   if(field('primary_mode').value==='existing'&&field('co_mode').value==='existing'&&field('primary_client_id').value===field('co_client_id').value){alert('Primary and second client must be different.');document.getElementById('contract_co_client_search').focus();return;}
   const primary=field('primary_mode').value==='existing'
     ? (clientById(field('primary_client_id').value)?.label||'')
     : (field('primary_organization_name').value||[field('primary_first_name').value,field('primary_last_name').value].join(' '));
   const amount=name=>Number(String(field(name).value||0).replace(/[$,]/g,''))||0;
   const money=value=>new Intl.NumberFormat('en-US',{style:'currency',currency:'USD'}).format(value);
   const purchase=amount('purchase_price'),down=amount('down_payment'),documentation=amount('documentation_fee');
   const planPayment=amount('plan_payment'),serviceFee=amount('service_fee'),firstPaymentAmount=down;
   const financed=Math.max(0,purchase-down),term=planPayment>0?Math.ceil(financed/planPayment):0;
   const rows=[
     ['Primary client',primary],
     ['Second client',field('co_mode').value==='existing' ? (clientById(field('co_client_id').value)?.label||'') : field('co_mode').value==='new' ? (field('co_organization_name').value||[field('co_first_name').value,field('co_last_name').value].join(' ')) : 'None'],
     ['APN / Plan',field('plan_number').value],
     ['Property',field('property_title').value],
     ['Property description',field('property_description').value||'Not provided'],
     ['Purchase price',money(purchase)],
     ['Down payment',money(down)+' (not recorded)'],
     ['Down/first payment invoice',field('create_first_payment_invoice').checked ? money(firstPaymentAmount)+' down payment + '+money(documentation)+' documentation fee' : 'Not created on activation'],
     ['Documentation fee',money(documentation)],
     ['Financed principal',money(financed)],
     ['Plan payment',money(planPayment)],
     ['Monthly service fee',money(serviceFee)],
     ['Total monthly payment',money(planPayment+serviceFee)],
     ['Estimated contract term',term+' payment'+(term===1?'':'s')],
     ['Contract start',field('contract_start_date').value],
     ['First recurring invoice',field('first_invoice_date').value],
     ['Recurring timing',field('due_days_after_issue').value+' days to due; '+field('grace_days').value+' grace days'],     ['Stage-one late fee',field('stage_one_fee_type').value+' '+field('stage_one_fee_value').value],     ['Stage-two late fee',field('stage_two_enabled').checked ? field('stage_two_fee_type').value+' '+field('stage_two_fee_value').value+' at '+field('stage_two_days_late').value+' days late' : 'Disabled'],
     ['Templates',form.querySelector('[name="contract_templates[]"]').files.length]
   ];
   @if(isset($prefill))
   const original=@json($prefill);
   const changes=[];
   const labels={plan_number:'APN / Plan',property_title:'Property',property_description:'Description',property_county:'County',purchase_price:'Purchase price',down_payment:'Down payment',documentation_fee:'Documentation fee',plan_payment:'Plan payment',service_fee:'Service fee',hoa_fee:'HOA fee',hoa_term:'HOA term',govdeals:'GovDeals',contract_start_date:'Contract start',first_invoice_date:'First invoice',due_days_after_issue:'Days to due',grace_days:'Grace days',stage_one_fee_type:'Stage-one type',stage_one_fee_value:'Stage-one value',stage_two_enabled:'Stage two enabled',stage_two_fee_type:'Stage-two type',stage_two_fee_value:'Stage-two value',stage_two_days_late:'Stage-two timing'};
   Object.entries(labels).forEach(([key,label])=>{
     const input=field(key);
     const before=input.type==='checkbox' ? (original[key] ? 'Yes' : 'No') : String(original[key]??'');
     const after=input.type==='checkbox' ? (input.checked ? 'Yes' : 'No') : input.value;
     if(before!==after)changes.push(label+': '+(before||'blank')+' → '+(after||'blank'));
   });
   if(String(original.co_client_id||'')!==String(field('co_client_id').value||'')||field('co_mode').value==='new')changes.push('Add co-client');
   rows.unshift(['Changes',changes.length ? changes.join('; ') : 'No changes to the displayed plan terms']);
   rows.push(['Revision reason',field('amendment_reason').value]);
   @endif
   document.getElementById('review-details').innerHTML=rows.map(r=>'<dt class="col-sm-4">'+r[0]+'</dt><dd class="col-sm-8">'+escapeHtml(r[1])+'</dd>').join('');
   bootstrap.Modal.getOrCreateInstance(document.getElementById('contractReview')).show();
 });
});
</script>
@endpush
