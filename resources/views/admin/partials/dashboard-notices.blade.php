<div class="admin-next-card mb-4 {{$notices->isEmpty() ? 'd-none' : ''}}" id="admin-notices" data-admin-notices data-admin-notices-revision="{{sha1($notices->pluck('updated_at', 'id')->toJson())}}">
    <div class="d-flex align-items-center gap-4"><h2 class="mb-0">Notices</h2><span class="dashboard-status status-due position-relative" style="top: 8px;">{{$notices->count()}} open</span></div>
    @foreach($notices as $notice)
        <div class="amendment-entry"><div class="amendment-entry-heading"><div>
            <strong>{{$notice->title}}</strong>
            @if($notice->type === 'invoice_first_viewed' && $notice->client && $notice->invoice)
                @php($noticeClientName = $notice->client->organization_name ?: trim($notice->client->first_name.' '.$notice->client->last_name))
                <p class="mb-0"><a href="{{route('admin.clients.show',$notice->client)}}">{{$noticeClientName}}</a> first viewed invoice
                    <a href="{{route('admin.invoices.show',$notice->invoice)}}">{{$notice->invoice->invoice_number}}</a>
                    on {{$notice->invoice->first_viewed_at?->format('M j, Y \a\t g:i A') ?? $notice->created_at->format('M j, Y \a\t g:i A')}}.
                </p>
            @elseif($notice->type === 'online_payment_received' && $notice->client && $notice->paymentIntent?->payment)
                <p class="mb-0"><a href="{{route('admin.clients.show',$notice->client)}}">{{trim($notice->client->first_name.' '.$notice->client->last_name)}}</a> paid <a href="{{route('admin.payments.show',$notice->paymentIntent->payment)}}">{{\App\Support\Money::format($notice->paymentIntent->amount)}}</a> by {{str($notice->paymentIntent->provider)->title()}} on {{$notice->paymentIntent->payment->received_date->format('M j, Y')}}. Payment posted successfully.</p>
            @elseif($notice->type === 'draft_contract_setup' && $notice->client && $notice->paymentPlan)
                @php($noticeClientName = $notice->client->organization_name ?: trim($notice->client->first_name.' '.$notice->client->last_name))
                <p class="mb-0"><a href="{{route('admin.clients.show',$notice->client)}}">{{$noticeClientName}}</a> has draft plan
                    <a href="{{route('admin.plans.show',$notice->paymentPlan)}}">{{$notice->paymentPlan->plan_number}}</a> awaiting activation.
                </p>
                <p class="mb-0">{{$notice->message}}</p>
            @else
                <p class="mb-0">{{$notice->message}}</p>
            @endif
            @if($notice->paymentIntent?->overpayment_disposition)<p class="mb-0 mt-1"><strong>Client overpayment instruction:</strong> {{$notice->paymentIntent->overpayment_disposition === 'next_invoice_credit' ? 'Keep extra as account credit.' : 'Apply extra to principal.'}}</p>@endif
        </div><div class="d-flex align-items-start gap-2 flex-shrink-0">
            @if($notice->changeRequest)<a class="btn btn-sm btn-brand" href="{{route('admin.client-change-requests.show',$notice->changeRequest)}}">Review</a>
            @elseif($notice->type === 'square_payment_anomaly' && $notice->paymentIntent?->payment)<a class="btn btn-sm btn-brand" href="{{route('admin.payments.show',$notice->paymentIntent->payment)}}">Review payment</a>
            @elseif($notice->paymentIntent?->status === 'announced')<a class="btn btn-sm btn-brand" href="{{route('admin.payment-intents.receive',$notice->paymentIntent)}}">Receive payment</a>
            @elseif($notice->secureMessageThread)<a class="btn btn-sm btn-outline-brand" href="{{route('admin.messages.show',$notice->secureMessageThread)}}">Open message</a>
            @elseif($notice->invoice)<a class="btn btn-sm btn-outline-brand" href="{{route('admin.invoices.show',$notice->invoice)}}">Open invoice</a>
            @elseif($notice->paymentPlan)<a class="btn btn-sm btn-outline-brand" href="{{route('admin.plans.show',$notice->paymentPlan)}}">Open plan</a>
            @elseif($notice->client)<a class="btn btn-sm btn-outline-brand" href="{{route('admin.clients.show',$notice->client)}}">Open client</a>@endif
            <form method="post" action="{{route('admin.notices.dismiss',$notice)}}">@csrf<button class="btn btn-sm btn-outline-brand">Dismiss</button></form>
        </div></div></div>
    @endforeach
</div>
