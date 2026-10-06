@include('closing.assets')
@if(session('success'))<div class="alert alert-success mt-3" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger mt-3" role="alert"><strong>Please review:</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@foreach($planSummaries as $summary)
@php
$plan = $summary['plan'];
$closing = $plan->closing;
$eligible = in_array($plan->status, ['active','paused'], true) && app(\App\Services\FinancialBalanceService::class)->contractBalance($plan) <= 0;
@endphp
@if($closing?->status === 'hold')
    @if($closing->show_hold_notice)
    <div class="alert alert-warning mt-4" id="closing-{{ $plan->id }}" role="status">
        <strong>Plan {{ $plan->plan_number }}</strong>
        <p class="mb-1">Your closing process is currently on hold. We will let you know when the next steps are available.</p>
        @if($closing->client_note)<div style="white-space:pre-wrap">{{ $closing->client_note }}</div>@endif
    </div>
    @endif
@elseif($closing?->compactProgress())
    {{-- After paperwork acceptance, progress is displayed beneath this plan in Your Plans. --}}
@elseif($closing?->visible())
@php
$details = $closing->details ?? app(\App\Services\ClosingWorkflowService::class)->prefill($plan);
$ownOld = (string) old('closing_plan') === (string) $plan->id;
if ($ownOld && in_array(old('action'), ['details','draft'], true)) $details = array_replace($details, old());
$statusLabels = ['needed'=>'Action needed','draft'=>'Draft saved','submitted'=>'Awaiting review','complete'=>'Complete','not_required'=>'Not required'];
$editable = $closing->status === 'active' && !$closing->submitted_on;
@endphp
<article class="closing-card" id="closing-{{ $plan->id }}">
    <div class="d-flex flex-wrap justify-content-between gap-2"><h2>Your Closing Process</h2><strong>Plan {{ $plan->plan_number }}</strong></div>
    @if($closing->details_status === 'draft' || $closing->extras_status === 'draft')
    <div class="alert alert-warning border-warning mt-3" role="status">
        <strong>Not yet submitted.</strong> Complete sections 1 and 2, then submit them together below.
    </div>
    @endif
    @if($closing->nextAction())
    <div class="alert alert-warning border-warning mt-3" role="status"><strong>{{ $closing->nextAction() }}</strong></div>
    @endif
    <p>Please review and complete the steps below so we can prepare your paperwork.</p>
    @if($closing->admin_notes)<div class="alert alert-info" style="white-space:pre-wrap">{{ $closing->admin_notes }}</div>@endif
    @if($closing->status === 'completed')<div class="alert alert-success">Your recording has been confirmed and your closing is complete.</div>@endif

    @if($closing->paperworkReviewed())
    <p class="my-3">To make changes to your <u>prior submitted requests</u>, please contact us by <a href="#closing-messages-{{ $plan->id }}">secure message</a> or other means.</p>
    @else
    <form method="post" action="{{ route('portal.closing.update',$plan) }}" data-closing-owners data-combined-closing>
        @csrf <input type="hidden" name="version" value="{{ $closing->version }}"><input type="hidden" name="closing_plan" value="{{ $plan->id }}">
        <p class="small">Complete sections 1 and 2, then submit them together below.</p>
    <details class="closing-step" @if($closing->details_status !== 'complete') open @endif>
        <summary>1. Confirm Your Paperwork Details <span class="closing-status {{ $closing->details_status }}">{{ $statusLabels[$closing->details_status] }}</span></summary>
        <div class="closing-step-content">
            <p>We need a few details in order to draft your paperwork.</p>
            @include('closing.vesting-preview')
                <fieldset @disabled(!$editable)>
                <label>How would you like the property titled?
                    <textarea name="titling" class="form-control" required maxlength="3000">{{ $details['titling'] ?? '' }}</textarea>
                </label>
                <div data-owner-list>
                    @foreach($details['owners'] ?? [] as $ownerIndex => $owner) @include('closing.owner') @endforeach
                </div>
                <template>@include('closing.owner',['ownerIndex'=>'__INDEX__','owner'=>[]])</template>
                <button type="button" class="btn btn-sm btn-outline-brand mb-3" data-add-owner>Add another proposed owner</button>
                <label>Would you also like an additional beneficiary deed prepared for an additional fee?
                    <select name="beneficiary" class="form-select" required>
                        <option value="">Please choose</option>
                        <option value="yes" @selected(($details['beneficiary'] ?? '') === 'yes')>Yes</option>
                        <option value="no" @selected(($details['beneficiary'] ?? '') === 'no')>No</option>
                    </select>
                </label>
                <p class="small">A beneficiary deed names someone to receive the land should you pass. We will discuss your request and the additional fee in step 2 before preparing paperwork.</p>
                </fieldset>
        </div>
    </details>

    <details class="closing-step" @if(!in_array($closing->extras_status,['complete','not_required'])) open @endif>
        <summary>2. Request Additional Paperwork <span class="closing-status {{ $closing->extras_status }}">{{ $statusLabels[$closing->extras_status] }}</span></summary>
        <div class="closing-step-content">
            <p>Are you married with a spouse not listed on this paperwork? Would you like to assign a beneficiary who receives the land should you pass? Do you have any other special cases or questions?</p>
            <p>We can prepare additional paperwork <strong>for an additional fee</strong>. Please discuss your request with us before signing.</p>
            @if(($details['beneficiary'] ?? '') === 'yes')<div class="alert alert-info">You requested a beneficiary deed in step 1. Please discuss the details below.</div>@endif
                <fieldset @disabled(!$editable)>
                @php($choice = $ownOld ? old('extras_choice',$closing->extras_choice) : $closing->extras_choice)
                <label><input type="radio" name="extras_choice" value="none" required @checked($choice==='none')> No additional paperwork needed</label>
                <label><input type="radio" name="extras_choice" value="request" required @checked($choice==='request')> I'd like to discuss a special request</label>
                <label>Comments <span class="text-muted">(required for a special request)</span><textarea name="extras_comments" class="form-control" maxlength="5000">{{ $ownOld ? old('extras_comments',$closing->extras_comments) : $closing->extras_comments }}</textarea></label>
                </fieldset>
        </div>
    </details>

        @if($editable)
        <div class="border rounded p-3 mb-3">
            <label><input type="checkbox" name="confirmed" value="1" required> I have reviewed my paperwork details and additional-paperwork choices in sections 1 and 2 and confirm they are correct.</label>
            <button class="btn btn-outline-brand" name="action" value="draft" formnovalidate>Save draft</button>
            <button class="btn btn-brand" name="action" value="details">Submit paperwork details and requests</button>
            <p class="small text-muted mt-2 mb-0">Saving a draft does not submit your information. Changes after submission require review of both sections again before signing.</p>
        </div>
        @endif
    </form>

    @endif
    @if($closing->paperworkReviewed() && !in_array($closing->forms_status,['complete','not_required']))
    <details class="closing-step" @if($closing->details_status === 'complete' && in_array($closing->extras_status,['complete','not_required']) && !in_array($closing->forms_status,['complete','not_required'])) open @endif>
        <summary>3. Complete and Sign Required Forms <span class="closing-status {{ $closing->forms_status }}">{{ $closing->formsLabel() }}</span></summary>
        <div class="closing-step-content">
            @if($closing->forms_status === 'not_required')<p>No signed forms are required for this closing.</p>
            @elseif(!$closing->released_at)<p>Forms being prepared. We will make the final signing packet available here after reviewing your details and any additional paperwork requests.</p>
            @else
                <ol type="a" class="closing-instructions">
                    @foreach($closing->instructions() as $instruction)<li>{{ $instruction }}</li>@endforeach
                </ol>
                @foreach($closing->documents->where('kind','signing') as $document)
                    <p><a class="btn btn-outline-brand" href="{{ route('portal.closing.documents.download',[$plan,$document]) }}">Download {{ $document->name }}</a></p>
                @endforeach
                @if($closing->forms_status === 'submitted')<p>Your mailing update was received. We will confirm when the originals have arrived and been accepted.</p>
                @elseif($closing->forms_status === 'complete')<p>Your signed paperwork has been accepted.</p>
                @elseif($editable)
                <form method="post" action="{{ route('portal.closing.update',$plan) }}">@csrf
                    <input type="hidden" name="action" value="mailed"><input type="hidden" name="version" value="{{ $closing->version }}">
                    <button class="btn btn-brand">I have mailed the original paperwork</button>
                </form>
                @endif
            @endif
        </div>
    </details>

    @endif
    @include('closing.messages',['admin'=>false])
</article>
@elseif($eligible)
<div class="alert alert-warning mt-4" role="status">Your plan {{ $plan->plan_number }} may be fulfilled. Please watch for information which will guide you through the next steps.</div>
@endif
@endforeach
