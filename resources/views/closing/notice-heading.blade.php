<strong>Closing paperwork update</strong> &mdash;
@if($noticeClient)
<a href="{{ route('admin.clients.show',$noticeClient) }}">{{ $noticeClient->organization_name ?: trim($noticeClient->first_name.' '.$noticeClient->last_name) }}</a> &middot;
@endif
@if($noticePlan)
<a href="{{ route('admin.plans.show',$noticePlan) }}#closing">{{ $noticePlan->plan_number }}</a> &middot;
@endif
