@extends('layouts.admin')
@section('title','Notices | LandPay')
@section('body_class','admin-page')
@section('content')
<section class="admin-section"><div class="container-fluid dashboard-container">
<div class="admin-heading d-flex flex-wrap justify-content-between align-items-end gap-3"><div><span class="eyebrow eyebrow-dark">Administration</span><h1>Notices</h1><p class="mb-0">Review current alerts and retained notice history.</p></div><a class="btn btn-outline-brand" href="{{route('admin.dashboard')}}">Back to dashboard</a></div>
@if(session('success'))<div class="alert alert-success mt-4">{{session('success')}}</div>@endif
<nav class="d-flex flex-wrap gap-2" aria-label="Notice views">
@foreach(['open'=>'Open','history'=>'History','all'=>'All'] as $value=>$label)
<a class="btn btn-sm {{$filter===$value?'btn-brand':'btn-outline-brand'}}" style="padding-top:.15rem; padding-bottom:.15rem;" href="{{route('admin.notices.index',['view'=>$value])}}" @if($filter===$value) aria-current="page" @endif>{{$label}}</a>
@endforeach
</nav>

@if($filter==='open')
    <div class="mt-4">
    @if($notices->isNotEmpty())
        @include('admin.partials.dashboard-notices',['notices'=>$notices])
    @else
        <div class="admin-next-card"><h2 class="mb-1">No open notices</h2><p class="text-muted mb-0">New administrator notices will appear here and on the dashboard.</p></div>
    @endif
    </div>
@else
<div class="admin-next-card mt-4">
<div class="d-flex flex-wrap justify-content-between align-items-end gap-2"><div><h2 class="mb-1">{{$filter==='history'?'Notice history':'All notices'}}</h2><p class="text-muted mb-0">{{$filter==='history'?'Dismissed notices, newest first.':'Open and dismissed notices, newest first.'}}</p></div><small class="text-muted">{{$notices->total()}} {{str('notice')->plural($notices->total())}}</small></div>
<div class="table-responsive mt-3" data-drag-scroll><table class="table table-sm align-middle notice-ledger mb-0">
<thead><tr><th>Date</th><th>Category</th><th>Notice</th><th>Client</th><th>Status</th></tr></thead>
<tbody>
@forelse($notices as $notice)
@php
    $category = match($notice->type) {
        'invoice_first_viewed','billing_automation_failure' => 'Invoice',
        'online_payment_received','provider_payment_exception','square_payment_anomaly','client_payment_announced' => 'Payment',
        'secure_message_reply' => 'Secure message',
        'shared_document_uploaded' => 'Document',
        'client_contact_change','portal_invitation_accepted' => 'Account / portal',
        default => 'System',
    };
    $url = match(true) {
        (bool) $notice->invoice => route('admin.invoices.show',$notice->invoice),
        (bool) $notice->paymentIntent?->payment => route('admin.payments.show',$notice->paymentIntent->payment),
        'draft_contract_setup' => 'Plan',
        $notice->paymentIntent?->status === 'announced' => route('admin.payment-intents.receive',$notice->paymentIntent),
        (bool) $notice->secureMessageThread => route('admin.messages.show',$notice->secureMessageThread),
        (bool) $notice->changeRequest => route('admin.client-change-requests.show',$notice->changeRequest),
        (bool) $notice->paymentPlan => route('admin.plans.show',$notice->paymentPlan),
        (bool) $notice->client => route('admin.clients.show',$notice->client),
        default => null,
    };
    $clientName = $notice->client ? ($notice->client->organization_name ?: trim($notice->client->first_name.' '.$notice->client->last_name)) : null;
@endphp
<tr>
<td class="text-nowrap">{{$notice->created_at->format('M j, Y g:i A')}}</td>
<td class="text-nowrap"><span class="notice-ledger-category">{{$category}}</span></td>
<td class="notice-ledger-summary" title="{{$notice->message}}">@if($url)<a href="{{$url}}">{{$notice->title}}</a>@else{{$notice->title}}@endif <span class="text-muted"> {{str($notice->message)->limit(90)}}</span></td>
<td class="text-nowrap">@if($notice->client)<a href="{{route('admin.clients.show',$notice->client)}}">{{$clientName}}</a>@else@endif</td>
<td class="text-nowrap">@if($notice->dismissed_at)Dismissed {{$notice->dismissed_at->format('M j, Y g:i A')}}@if($notice->dismissedBy) by {{$notice->dismissedBy->name}}@endif @else<strong>Open</strong>@endif</td>
</tr>
@empty
<tr><td colspan="5" class="py-4 text-center text-muted">No notices in this view.</td></tr>
@endforelse
</tbody></table></div>
@if($notices->hasPages())<div class="mt-3">{{$notices->links()}}</div>@endif
</div>
@endif
</div></section>
@endsection
