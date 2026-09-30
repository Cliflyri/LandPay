@extends('layouts.admin')
@section('title','Charge Customer | LandPay')
@section('body_class','admin-page')
@section('content')
@php $clientOptions = $clients->map(fn ($client) => ['id'=>$client->id,'name'=>$client->organization_name ?: trim($client->first_name.' '.$client->last_name),'email'=>$client->email,'plans'=>$client->memberships->filter(fn ($m) => $m->effective_to === null && $m->effective_from->lte(today()) && $m->paymentPlan && in_array($m->paymentPlan->status,['active','paused'],true))->map(fn ($m) => ['id'=>$m->payment_plan_id,'number'=>$m->paymentPlan->plan_number,'title'=>$m->paymentPlan->title])->values()])->values(); @endphp
<section class="admin-section"><div class="container-fluid dashboard-container">
<div class="admin-heading"><span class="eyebrow eyebrow-dark">Admin Actions</span><h1>Charge Customer</h1><p>Find a client by name, email, or plan number, then continue to secure card entry.</p></div>
<div class="admin-next-card mt-4">
<label class="form-label" for="payment-client-search">Client or plan</label>
<input id="payment-client-search" class="form-control" type="search" autocomplete="off" placeholder="Start typing a name, email, or plan number">
<div id="payment-client-results" class="list-group mt-3"></div>
<p id="payment-client-empty" class="text-muted mt-3" hidden>No matching clients with active or paused plans.</p>
</div></div></section>
@endsection
@push('scripts')
<script @isset($cspNonce) nonce='{{$cspNonce}}' @endisset>
(() => {
 const clients = @json($clientOptions);
 const input=document.getElementById('payment-client-search'), results=document.getElementById('payment-client-results');
 input.addEventListener('input',()=>{
   results.replaceChildren();
   const query=input.value.trim().toLowerCase();
   if(query)clients.filter(c=>[c.name,c.email,...c.plans.map(p=>p.number+' '+p.title)].join(' ').toLowerCase().includes(query)).slice(0,10).forEach(client=>{
     client.plans.forEach(plan=>{
       const link=document.createElement('a');
       link.className='list-group-item list-group-item-action';
       link.href=@json(route('admin.take-payment.create'))+'?client_id='+client.id+'&plan='+plan.id;
       link.textContent=client.name+' — '+plan.number+' — '+plan.title+(client.email?' ('+client.email+')':'');
       results.appendChild(link);
     });
   });
   document.getElementById('payment-client-empty').hidden=!query||results.children.length>0;
 });
})();
</script>
@endpush
