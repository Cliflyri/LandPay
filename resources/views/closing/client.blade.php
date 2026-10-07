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
$requestsSubmitted = !empty($closing->details['combined_submission']);
$requestsEditable = $editable && !$requestsSubmitted;
$packetPreviouslyReleased = $closing->packetPreviouslyReleased();
@endphp
<article class="closing-card" id="closing-{{ $plan->id }}">
    <div class="d-flex flex-wrap justify-content-between gap-2"><h2>Your Closing Process</h2><strong>Plan {{ $plan->plan_number }}</strong></div>
    @if($closing->details_status === 'draft' || $closing->extras_status === 'draft')
    <div class="alert alert-warning border-warning mt-3" role="status">
        <strong>Not yet submitted.</strong> Complete your paperwork details and requests, then submit below.
    </div>
    @endif
    @if($closing->nextAction())
    <div class="alert alert-warning border-warning mt-3" role="status"><strong>{{ $closing->nextAction() }}</strong></div>
    @endif
    <p><strong>Please review and complete the steps below so we can prepare your paperwork.</strong></p>
    @if($closing->admin_notes)<div class="alert alert-info" style="white-space:pre-wrap">{{ $closing->admin_notes }}</div>@endif
    @if($closing->status === 'completed')<div class="alert alert-success">Your recording has been confirmed and your closing is complete.</div>@endif

    @if($closing->paperworkReviewed())
    <p class="my-3">To make changes to your <u>prior submitted requests</u>, please contact us by <a href="#closing-messages-{{ $plan->id }}">secure message</a> or other means.</p>
    @else
    <form method="post" action="{{ route('portal.closing.update',$plan) }}" data-closing-owners data-combined-closing>
        @csrf <input type="hidden" name="version" value="{{ $closing->version }}"><input type="hidden" name="closing_plan" value="{{ $plan->id }}">
        @if($requestsSubmitted)
        <p class="small">Your paperwork details and requests have been submitted and are awaiting admin review.</p>
        @else
        <p class="small">Complete your paperwork details and requests, then submit below.</p>
        @endif
    <details class="closing-step" @if(!$requestsSubmitted) open @endif>
        <summary>1. Confirm Your Paperwork Details <span class="closing-status {{ $closing->paperworkDisplayStatus() }}">{{ $statusLabels[$closing->paperworkDisplayStatus()] }}</span></summary>
        <div class="closing-step-content">
            <p>We need a few details in order to draft your paperwork.</p>
            <h3>a) How would you like the property titled?</h3>
            @include('closing.vesting-preview')
                <fieldset @disabled(!$requestsEditable)>
                <label>Requested titling <span class="text-muted">(required)</span>
                    <textarea name="titling" class="form-control" required maxlength="3000">{{ $details['titling'] ?? '' }}</textarea>
                </label>
                <hr class="my-3">
                <h3>b) Proposed owner information</h3>
                <p class="small">Provide the details below for each proposed owner. Mailing address is optional.</p>
                <div data-owner-list>
                    @foreach($details['owners'] ?? [] as $ownerIndex => $owner) @include('closing.owner') @endforeach
                </div>
                <template>@include('closing.owner',['ownerIndex'=>'__INDEX__','owner'=>[]])</template>
                <button type="button" class="btn btn-sm btn-outline-brand mb-3" data-add-owner>Add another proposed owner</button>
                </fieldset>
            <hr class="my-3">
            <h3>c) Would you like a beneficiary deed or other special paperwork prepared for an additional fee?</h3>
            <p class="mb-1">Examples include:</p>
            <ul>
                <li>Naming someone to receive your property should you pass away.</li>
                <li>Addressing paperwork for a spouse who is not listed.</li>
                <li>Other special circumstances or questions.</li>
            </ul>
            <p>We'll discuss any requirements and fees with you before preparing additional paperwork. Please contact us with questions before signing.</p>
                <fieldset @disabled(!$requestsEditable)>
                @php($choice = $ownOld ? old('extras_choice') : ((!empty($details['beneficiary']) && $details['beneficiary'] === 'yes' && empty($details['special_paperwork_question'])) ? 'request' : $closing->extras_choice))
                <p class="small">Please select Yes or No (required).</p>
                <label><input type="radio" name="extras_choice" value="none" required @checked($choice==='none')> No additional paperwork requested</label>
                <label><input type="radio" name="extras_choice" value="request" required @checked($choice==='request')> Yes, I'd like to discuss additional paperwork</label>
                <label>Comments <span class="text-muted">(required for Yes; optional for No)</span><textarea name="extras_comments" class="form-control" maxlength="5000">{{ $ownOld ? old('extras_comments',$closing->extras_comments) : $closing->extras_comments }}</textarea></label>
                </fieldset>

        @if($requestsEditable)
        <h3>d) Review and submit</h3>
        <div class="border rounded p-3 mb-3">
            
            <label><input type="checkbox" name="confirmed" value="1" required> I have reviewed my paperwork details and additional-paperwork choices and confirm they are correct.</label>
            <button class="btn btn-outline-brand" name="action" value="draft" formnovalidate>Save draft</button>
            <button class="btn btn-brand" name="action" value="details">Submit paperwork details and requests</button>
            <p class="small text-muted mt-2 mb-0">Saving a draft does not submit your information. Changes after submission require review again before signing.</p>
        </div>
        @endif
        </div>
    </details>
    </form>

    @endif
    @if(($closing->paperworkReviewed() || $packetPreviouslyReleased) && !in_array($closing->forms_status,['complete','not_required']))
    <details class="closing-step" open>
        <summary>2. Complete and Sign Required Forms <span class="closing-status {{ $closing->forms_status }}">{{ $closing->formsLabel() }}</span></summary>
        <div class="closing-step-content">
            @if($closing->forms_status === 'not_required')<p>No signed forms are required for this closing.</p>
            @elseif(!$closing->released_at && $packetPreviouslyReleased)
                <div class="alert alert-warning">Your paperwork details are being reviewed. Please wait for an updated signing packet before signing or mailing forms.</div>
            @elseif(!$closing->released_at)<p>Forms being prepared. We will make the final signing packet available here after reviewing your details and any additional paperwork requests.</p>
            @else
                <div class="closing-instructions mb-3" style="white-space:pre-wrap">{{ $closing->instructionsText() }}</div>
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
