@php($context=$notice->billingContext())
<p class="mb-0">
@if($context['client'])
<a href="{{route('admin.clients.show',$context['client'])}}">{{$context['client']->organization_name ?: trim($context['client']->first_name.' '.$context['client']->last_name)}}</a> &middot;
@endif
@if($context['plan'])
Plan <a href="{{route('admin.plans.show',$context['plan'])}}">{{$context['plan']->plan_number}}</a>@if($context['invoice']), invoice <a href="{{route('admin.invoices.show',$context['invoice'])}}">{{$context['invoice']->invoice_number}}</a>@endif:
{{preg_replace('/^Plan .+?(?:, invoice [^:]+)?: /','',$notice->message,1)}}
@else
{{$notice->message}}
@endif
</p>
