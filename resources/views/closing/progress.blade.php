@php
$milestones = [
    ['Paperwork accepted', $closing->paperwork_accepted_at],
    ['Ready for recording', $closing->ready_on],
    ['Submitted to county', $closing->submitted_on],
    ['Recording confirmed', $closing->recorded_on],
];
$currentMilestone = collect($milestones)->search(fn($item) => !$item[1]);
$adminProgress = $showUndo ?? false;
@endphp
<ol class="closing-progress {{ $adminProgress ? 'closing-progress-admin' : '' }}" aria-label="Recording progress">
@foreach($milestones as $index => [$label, $date])
    <li class="{{ $date ? 'done' : ($index === $currentMilestone ? 'current' : '') }}" @if(!$date && $index === $currentMilestone) aria-current="step" @endif>
        <div class="closing-progress-marker">
            <span aria-hidden="true">@if($date)&#10003;@elseif($index === $currentMilestone)&#9679;@else&#9675;@endif</span>
            <strong @if($date) class="badge rounded-pill text-bg-success" @endif>{{ $label }}</strong>
            <small>{{ $date ? $date->format('M j, Y') : ($index === $currentMilestone ? 'Pending' : 'Upcoming') }}</small>
        </div>
        @if($adminProgress)
        <div class="closing-progress-controls">
            @if($date)
            <form method="post" action="{{ route('admin.closing.update',$plan) }}"
                onsubmit="return confirm('Undo this milestone and clear later recording milestones? Undoing paperwork acceptance also reopens client step 2. The history will be retained.');">
                @csrf <input type="hidden" name="action" value="undo_progress"><input type="hidden" name="version" value="{{ $closing->version }}">
                <input type="hidden" name="milestone" value="{{ ['paperwork','ready','submitted','recorded'][$index] }}">
                <button class="btn btn-link btn-sm closing-progress-undo" aria-label="Undo {{ $label }}">Undo</button>
            </form>
            @elseif($index === 0)
                <small>Accept the required paperwork in step 2 above.</small>
            @elseif($closing->status === 'active')
                @php
                $milestone = [1=>'ready',2=>'submit',3=>'complete'][$index];
                $canMark = $index === $currentMilestone && $closing->paperworkReviewed() && in_array($closing->forms_status,['complete','not_required']);
                @endphp
                <form method="post" action="{{ route('admin.closing.update',$plan) }}">
                    @csrf <input type="hidden" name="version" value="{{ $closing->version }}"><input type="hidden" name="action" value="{{ $milestone }}">
                    <fieldset @disabled(!$canMark)>
                        <label>{{ ['ready'=>'Ready for recording date','submit'=>'County submission date','complete'=>'Confirmed recording date'][$milestone] }}
                            <input class="form-control form-control-sm mt-1" type="date" name="milestone_date" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" required>
                        </label>
                        @if($milestone === 'complete')
                        <label>Recording reference <span class="text-muted">(optional)</span>
                            <input class="form-control form-control-sm mt-1" name="recording_reference" maxlength="255" value="{{ $closing->recording_reference }}">
                        </label>
                        @endif
                        @if($canMark && $hasClosingBalance)
                        <div class="alert alert-warning p-2 small">
                            <div>Contract: {{ \App\Support\Money::format($closingBalances['contract']) }}<br>Invoices: {{ \App\Support\Money::format($closingBalances['outstanding']) }}</div>
                            <label class="mt-2"><input type="checkbox" name="acknowledge_balance" value="1" required> I reviewed the balances and authorize this milestone.</label>
                            <label>Reason for proceeding<textarea name="balance_reason" class="form-control form-control-sm" required maxlength="3000"></textarea></label>
                        </div>
                        @endif
                        <button class="btn btn-sm btn-brand w-100">{{ ['ready'=>'Mark ready','submit'=>'Mark submitted','complete'=>'Mark recorded / complete'][$milestone] }}</button>
                    </fieldset>
                    @if(!$canMark)<small class="text-muted">Complete the preceding steps first.</small>@endif
                </form>
            @endif
        </div>
        @endif
    </li>
@endforeach
</ol>
@if($adminProgress && $closing->status === 'active')<p class="small text-muted">Balances are checked again when saved. Recording updates do not change billing.</p>@endif
@if($closing->recording_reference && !($compact ?? false))<p><strong>Recording reference:</strong> {{ $closing->recording_reference }}</p>@endif
