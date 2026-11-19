<div class="modal fade" id="close-plan-{{ $closePlan->id }}" tabindex="-1" aria-labelledby="close-plan-title-{{ $closePlan->id }}" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <form method="post" action="{{ route('admin.plans.close', $closePlan) }}">
            @csrf
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="close-plan-title-{{ $closePlan->id }}">Mark plan closed · {{ $closePlan->plan_number }}</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Mark this plan closed? It will be removed from the active dashboard list. All history and documents will remain available under Closed plans.</p>
                <label class="form-label" for="close-plan-notes-{{ $closePlan->id }}">Closure notes (optional)</label>
                <textarea class="form-control" id="close-plan-notes-{{ $closePlan->id }}" name="closure_notes" rows="2" maxlength="2000">{{ $closePlan->closure_notes }}</textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-brand" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-plan-close">Mark closed</button>
            </div>
        </form>
    </div></div>
</div>
