<div class="admin-next-card mt-4" id="improvements">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2"><h2>{{$admin?'Improvements':'Your Improvements'}}</h2>
@if($admin)
<form method="post" action="{{route('admin.improvements.dashboard-visibility',$plan)}}" class="small">@csrf @method('PATCH')
<input type="hidden" name="show_improvements_on_dashboard" value="0">
<div class="form-check form-switch mb-0"><input class="form-check-input" type="checkbox" role="switch" id="improvements-dashboard-{{$plan->id}}" name="show_improvements_on_dashboard" value="1" @checked($plan->show_improvements_on_dashboard) onchange="this.form.requestSubmit()"><label class="form-check-label" for="improvements-dashboard-{{$plan->id}}">Show on client dashboard</label></div>
</form>
@endif
@if(!$admin)<a class="btn btn-outline-brand" href="{{route('portal.improvements.index',['notify'=>1])}}#notify">Notify admin of planned improvement</a>@endif</div>
@forelse($improvements as $improvement)
<div class="border-bottom py-3" style="overflow-wrap:anywhere"><a href="{{route($admin?'admin.improvements.show':'portal.improvements.show',$improvement)}}">{{$improvement->title}}</a>
<small class="d-block text-muted">{{$improvement->paymentPlan->plan_number}} &middot; Submitted {{$improvement->created_at->format('M j, Y')}}</small>
@include('improvements.badge',['update'=>$improvement->latestUpdate])
@if($improvement->pending_updates_count>0)<small class="d-block text-muted">{{$improvement->pending_updates_count}} awaiting receipt acknowledgment</small>@endif
</div>
@empty<p class="text-muted mb-0">{{$admin?'No improvements have been submitted for this plan.':'Planning an improvement? Notify admin here and add photos as you make your land your own.'}}</p>@endforelse
@if(!$admin && ($improvementsCount ?? 0)>1)<a class="d-inline-block mt-3" href="{{route('portal.improvements.index')}}">View all improvements ({{$improvementsCount}})</a>@endif
</div>
