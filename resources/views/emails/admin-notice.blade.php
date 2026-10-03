@if(isset($notice) && $notice->type === 'billing_automation_failure')
@include('shared.billing-failure-notice')
@else
<p>{{$noticeMessage}}</p>
@endif
@if($adminUrl)
<p><a href="{{$adminUrl}}">View in LandPay</a></p>
@endif
