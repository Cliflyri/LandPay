@if($closingFollowUps->isNotEmpty())
<section class="admin-next-card h-auto p-3 mb-4" aria-label="Closing follow-up">
    <div class="d-flex align-items-center gap-3 mb-2"><h2 class="h5 my-0">Closing follow-up</h2><span class="dashboard-status status-due">{{ $closingFollowUps->count() }} open</span></div>
    @foreach($closingFollowUps as $followUp)
    <div class="amendment-entry d-flex flex-wrap justify-content-between align-items-center gap-2 py-2">
        <div class="d-flex flex-wrap align-items-center gap-2">
        <a class="fw-bold" href="{{ route('admin.plans.show', $followUp->paymentPlan) }}#closing">Plan {{ $followUp->paymentPlan->plan_number }}</a>
        <span class="badge rounded-pill {{ $followUp->recorded_on || $followUp->ready_on ? 'text-bg-success' : '' }}" @if(!$followUp->ready_on && !$followUp->recorded_on) style="color:#624b08;background:#fff5d9;border:1px solid #b58715" @endif>
            {{ $followUp->recorded_on ? 'Recording confirmed' : ($followUp->submitted_on ? 'Submitted to county' : ($followUp->ready_on ? 'Ready for recording' : 'Ready for recording · Pending')) }}
        </span>
        </div>
        <div class="d-flex gap-2 flex-shrink-0">
        <a class="btn btn-sm btn-brand" href="{{ route('admin.plans.show', $followUp->paymentPlan) }}#closing">Open closing</a>
        @if($followUp->status === 'completed' && $followUp->recorded_on)
        <form method="post" action="{{ route('admin.closing.dismiss', $followUp->paymentPlan) }}" class="m-0">
            @csrf
            <input type="hidden" name="recording_follow_up" value="1">
            <button class="btn btn-sm btn-outline-brand" aria-label="Dismiss recording notice for plan {{ $followUp->paymentPlan->plan_number }}">Dismiss</button>
        </form>
        @endif
        </div>
    </div>
    @endforeach
</section>
@endif
@foreach($closingNotices as $reviewClosing)
@continue($reviewClosing->compactProgress())
<div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2" role="status">
    <div>
        @include('closing.notice-heading', ['noticePlan'=>$reviewClosing->paymentPlan, 'noticeClient'=>$reviewClosing->paymentPlan->memberships->whereNull('effective_to')->firstWhere('role','primary')?->client])
        <p class="mb-0"><strong>Ready to close:</strong> This plan may be fulfilled and is ready for closing review.</p>
        <a href="{{ route('admin.plans.show', $reviewClosing->paymentPlan) }}#closing">Review closing</a>
    </div>
    <form method="post" action="{{ route('admin.closing.dismiss', $reviewClosing->paymentPlan) }}">@csrf
        <button class="btn btn-sm btn-outline-dark" aria-label="Dismiss closing review for plan {{ $reviewClosing->paymentPlan->plan_number }}">Dismiss</button>
    </form>
</div>
@endforeach
