@if($closingFollowUps->isNotEmpty())
<section class="admin-next-card h-auto p-3 mb-4" aria-label="Closing follow-up">
    <div class="d-flex align-items-center gap-3 mb-2"><h2 class="h5 my-0">Closing follow-up</h2><span class="dashboard-status status-due">{{ $closingFollowUps->count() }} open</span></div>
    @foreach($closingFollowUps as $followUp)
    <div class="amendment-entry d-flex flex-wrap justify-content-between align-items-center gap-2 py-1">
        <div class="d-flex flex-wrap align-items-center gap-2">
        @php
            $followUpClient = $followUp->paymentPlan->memberships->whereNull('effective_to')->firstWhere('role', 'primary')?->client;
            $followUpSteps = [
                ['Paperwork accepted', $followUp->paperwork_accepted_at],
                ['Ready for recording', $followUp->ready_on],
                ['Submitted to county', $followUp->submitted_on],
                ['Recording confirmed', $followUp->recorded_on],
            ];
            $pendingStep = collect($followUpSteps)->search(fn ($step) => !$step[1]);
        @endphp
        @if($followUpClient)
        <a class="fw-bold" href="{{ route('admin.clients.show', $followUpClient) }}">{{ $followUpClient->organization_name ?: trim($followUpClient->first_name.' '.$followUpClient->last_name) }}</a>
        <span aria-hidden="true">&middot;</span>
        @endif
        <a class="fw-bold" href="{{ route('admin.plans.show', $followUp->paymentPlan) }}#closing">Plan {{ $followUp->paymentPlan->plan_number }}</a>
        @foreach($followUpSteps as $stepIndex => [$stepLabel, $stepDate])
        <span class="badge rounded-pill {{ $stepDate ? 'text-bg-success' : '' }}"
            @if(!$stepDate) style="{{ $stepIndex === $pendingStep ? 'color:#624b08;background:#fff5d9;border:1px solid #b58715' : 'color:#59636b;background:#edf0f2;border:1px solid #cbd1d6' }}" @endif
            title="{{ $stepLabel }} · {{ $stepDate ? 'Done' : ($stepIndex === $pendingStep ? 'Pending' : 'Upcoming') }}"
            @if(!$stepDate && $stepIndex === $pendingStep) aria-current="step" @endif>
            <span aria-hidden="true">{{ $stepDate ? '✓' : ($stepIndex === $pendingStep ? '●' : '○') }}</span>
            {{ $stepLabel }}<span class="visually-hidden"> · {{ $stepDate ? 'Done' : ($stepIndex === $pendingStep ? 'Pending' : 'Upcoming') }}</span>
        </span>
        @endforeach
        </div>
        <div class="d-flex align-items-center gap-2 flex-shrink-0 small">
        <a class="text-nowrap" href="{{ route('admin.plans.show', $followUp->paymentPlan) }}#closing">Open closing</a>
        @if($followUp->status === 'completed' && $followUp->recorded_on)
        <form method="post" action="{{ route('admin.closing.dismiss', $followUp->paymentPlan) }}" class="m-0">
            @csrf
            <input type="hidden" name="recording_follow_up" value="1">
            <button class="btn btn-link p-0 border-0 align-baseline" style="font-size:inherit;line-height:inherit;" aria-label="Dismiss recording notice for plan {{ $followUp->paymentPlan->plan_number }}">Dismiss</button>
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
