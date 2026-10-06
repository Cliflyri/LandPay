@include('closing.assets')
@php
$closingService = app(\App\Services\ClosingWorkflowService::class);
$closing = $plan->closing ?? new \App\Models\PlanClosing(['payment_plan_id'=>$plan->id,'status'=>'review','version'=>0,'details_status'=>'needed','extras_status'=>'needed','forms_status'=>'needed','show_hold_notice'=>true]);
$closingBalances = $closingService->balances($plan);
$hasClosingBalance = $closingBalances['contract'] > 0 || $closingBalances['outstanding'] > 0;
$labels = ['needed'=>'Action needed','draft'=>'Draft saved','submitted'=>'Awaiting review','complete'=>'Complete','not_required'=>'Not required'];
@endphp
<details class="closing-card" id="closing" @if($closing->status !== 'review' || $closing->eligible_at || $closingService->eligible($plan)) open @endif>
    <summary class="h4">Closing <span class="badge text-bg-light">{{ $closing->exists ? $closing->label() : 'Prepare closing' }}</span></summary>
    @if($closing->nextAction(true))
    <div class="alert alert-warning border-warning mt-3" role="status"><strong>{{ $closing->nextAction(true) }}</strong></div>
    @endif
    <p class="text-muted mt-3">Prepare closing at any time. Client steps appear only when you start closing. Billing remains separate.</p>
    <div class="alert {{ $hasClosingBalance ? 'alert-warning closing-balance' : 'alert-success' }}">
        <strong>{{ $hasClosingBalance ? 'Outstanding balances require attention before recording or final completion.' : 'No outstanding contract or invoice balance.' }}</strong>
        <div>Contract balance: <strong>{{ \App\Support\Money::format($closingBalances['contract']) }}</strong></div>
        <div>Outstanding invoices: <strong>{{ \App\Support\Money::format($closingBalances['outstanding']) }}</strong></div>
        @foreach($closingBalances['invoices'] as $item)
        <div><a href="{{ route('admin.invoices.show',$item['invoice']) }}">{{ $item['invoice']->invoice_number }}</a> &mdash; {{ \App\Support\Money::format($item['balance']) }}</div>
        @endforeach
        <small>These figures can overlap; they are shown separately and should not be added together.</small>
    </div>

    @if(in_array($closing->status,['review','hold']))
    <form method="post" action="{{ route('admin.closing.update',$plan) }}" class="mb-3">
        @csrf <input type="hidden" name="version" value="{{ $closing->version }}">
        <input type="hidden" name="action" value="{{ $closing->status === 'hold' ? 'resume' : 'start' }}">
        <label><input type="checkbox" name="notify_client" value="1"> Notify client by email and eligible SMS</label>
        <button class="btn btn-brand">{{ $closing->status === 'hold' ? 'Resume closing' : 'Start closing' }}</button>
    </form>
    @elseif($closing->status === 'active')
    <details class="mb-3"><summary>Disable closing</summary>
        <form method="post" action="{{ route('admin.closing.update',$plan) }}" class="mt-2">
            @csrf <input type="hidden" name="version" value="{{ $closing->version }}"><input type="hidden" name="action" value="disable">
            <label><input type="radio" name="show_hold_notice" value="1" checked> Show an on-hold notice</label>
            <label><input type="radio" name="show_hold_notice" value="0"> Hide the closing area entirely</label>
            <label>Optional client note<textarea class="form-control" name="client_note" maxlength="3000">{{ $closing->client_note }}</textarea></label>
            <button class="btn btn-outline-danger">Disable closing and preserve progress</button>
        </form>
    </details>
    @endif

    <details class="closing-step"><summary>Client notes</summary><div class="closing-step-content">
        <form method="post" action="{{ route('admin.closing.update',$plan) }}">
            @csrf <input type="hidden" name="version" value="{{ $closing->version }}"><input type="hidden" name="action" value="save">
            <label>Client-facing closing notes<textarea name="admin_notes" class="form-control" maxlength="10000">{{ old('admin_notes',$closing->admin_notes) }}</textarea></label>
            <button class="btn btn-outline-brand">Save client notes</button>
        </form>
    </div></details>

    <details class="closing-step" @if($closing->details_status !== 'complete') open @endif>
        <summary>1. Confirm Your Paperwork Details <span class="closing-status {{ $closing->details_status }}">{{ $labels[$closing->details_status] }}</span></summary>
        <div class="closing-step-content">
            @include('closing.vesting-admin')
            @if($closing->details)
            <p><strong>Requested titling:</strong> {{ $closing->details['titling'] ?? '' }}</p>
            @foreach($closing->details['owners'] ?? [] as $owner)
            <div class="closing-owner">
                <strong>{{ $owner['name'] ?? '' }}</strong>
                <div style="white-space:pre-wrap">{{ $owner['address'] ?? '' }}</div>
                @if(!empty($owner['mailing_address']))<p>Mailing address: <span style="white-space:pre-wrap">{{ $owner['mailing_address'] }}</span></p>@endif
                <p>Married: {{ ucfirst($owner['married'] ?? 'Not answered') }}</p>
            </div>
            @endforeach
            <p>Beneficiary deed requested: {{ ucfirst($closing->details['beneficiary'] ?? 'Not answered') }}</p>
            <p>Client confirmation: {{ !empty($closing->details['confirmed']) ? 'Submitted and confirmed' : 'Draft only' }}</p>
            @else <p>No details submitted yet.</p> @endif
        </div>
    </details>
    <details class="closing-step" @if(!in_array($closing->extras_status,['complete','not_required'])) open @endif>
        <summary>2. Request Additional Paperwork <span class="closing-status {{ $closing->extras_status }}">{{ $labels[$closing->extras_status] }}</span></summary>
        <div class="closing-step-content">
            <p>{{ $closing->extras_choice === 'request' ? 'Client requests discussion of additional paperwork for an additional fee.' : ($closing->extras_choice === 'none' ? 'Client selected no additional paperwork.' : 'No request submitted yet.') }}</p>
            <p style="white-space:pre-wrap">{{ $closing->extras_comments }}</p>
            <p class="small">Resolve special requirements and any fees before marking this step complete.</p>
            @include('closing.paperwork-review')
        </div>
    </details>
    <details class="closing-step" @if(!in_array($closing->forms_status,['complete','not_required'])) open @endif>
        <summary>3. Complete and Sign Required Forms <span class="closing-status {{ $closing->forms_status }}">{{ $closing->formsLabel(true) }}</span> @if(in_array($closing->forms_status,['complete','not_required']))<button type="button" class="btn btn-sm btn-outline-secondary" data-closing-reopen="closing-reopen-forms">Reopen</button>@endif</summary>
        <div class="closing-step-content">
            <p>{{ $closing->released_at ? 'Signing packet released '.$closing->released_at->format('M j, Y g:i A').'.' : 'Signing forms remain private until you release the final packet.' }}</p>
            @if($closing->forms_status === 'submitted')<div class="alert alert-info">Client reports mailing originals. Confirm receipt and acceptance before completing this step.</div>@endif
            <h3>Upload signing forms</h3>
            @include('closing.documents',['documentKind'=>'signing'])
            <form method="post" action="{{ route('admin.closing.update',$plan) }}" class="mb-3">
                @csrf <input type="hidden" name="version" value="{{ $closing->version }}"><input type="hidden" name="action" value="save">
            <h3>Review signing and mailing instructions</h3>
            <textarea class="form-control form-control-sm mb-2" name="instructions_text" rows="4" required maxlength="10000" aria-label="Signing and mailing instructions">{{ old('instructions_text',$closing->instructionsText()) }}</textarea>
                <button class="btn btn-outline-brand">Save signing instructions</button>
            </form>
            @if(!$closing->released_at && $closing->status !== 'completed')
            @php
            $releaseRequirements = [];
            if ($closing->status !== 'active') $releaseRequirements[] = 'Start or resume closing.';
            if (!$closing->paperworkReviewed()) $releaseRequirements[] = 'Approve paperwork details and requests in steps 1 and 2.';
            if ($closing->documents->where('kind','signing')->isEmpty()) $releaseRequirements[] = 'Upload at least one signing form above.';
            @endphp
            @if($releaseRequirements)
            <div class="alert alert-warning" id="closing-release-requirements">
                <strong>Before releasing the packet:</strong>
                <ul class="mb-0">@foreach($releaseRequirements as $requirement)<li>{{ $requirement }}</li>@endforeach</ul>
            </div>
            @endif
            <form method="post" action="{{ route('admin.closing.update',$plan) }}" class="mb-3">
                @csrf <input type="hidden" name="action" value="release"><input type="hidden" name="version" value="{{ $closing->version }}">
                <label><input type="checkbox" name="notify_client" value="1" @disabled(count($releaseRequirements) > 0)> Notify client by email and eligible SMS that step 3 is ready</label>
                <button class="btn btn-brand" @disabled(count($releaseRequirements) > 0) @if($releaseRequirements) aria-describedby="closing-release-requirements" @endif>Release final signing packet</button>
            </form>
            @endif
            @include('closing.review',['section'=>'forms'])
        </div>
    </details>

    <details class="closing-step" @if($closing->paperwork_accepted_at) open @endif>
        <summary>4. Recording and Your Deed</summary><div class="closing-step-content">
        @include('closing.progress',['showUndo'=>true])
        <h3>Recorded documents</h3>
        @include('closing.documents',['documentKind'=>'recorded'])
    </div></details>
    @include('closing.messages',['admin'=>true])
    <details class="mt-4"><summary>Closing history</summary><div class="closing-history">
        @foreach($closing->events()->with(['user','client'])->get() as $event)
        <div class="border-bottom py-2">
            <strong>{{ ucfirst(str_replace('_',' ',$event->action)) }}</strong> &middot; {{ $event->created_at->format('M j, Y g:i A') }}
            &middot; {{ $event->user?->name ?: ($event->client ? trim($event->client->first_name.' '.$event->client->last_name) : 'System') }}
            @if($event->context)<details><summary>Details</summary><pre>{{ json_encode($event->context,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre></details>@endif
        </div>
        @endforeach
    </div></details>
</details>
