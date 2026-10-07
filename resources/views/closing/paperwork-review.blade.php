@if(!$closing->paperworkReviewed() && $closing->status === 'active' && !empty($closing->details['combined_submission']) && !empty($closing->details['confirmed']) && $closing->details_status !== 'draft' && filled($closing->extras_choice))
<form method="post" action="{{ route('admin.closing.update',$plan) }}">
    @csrf <input type="hidden" name="action" value="review_paperwork"><input type="hidden" name="version" value="{{ $closing->version }}">
    <button class="btn btn-outline-brand">Approve paperwork details and requests</button>
</form>
@endif
<details id="closing-reopen-paperwork" class="mt-3">
    <summary>Request changes to details and requests</summary>
    <form method="post" action="{{ route('admin.closing.update',$plan) }}" class="mt-2">
        @csrf <input type="hidden" name="action" value="reopen_step"><input type="hidden" name="section" value="paperwork">
        <input type="hidden" name="version" value="{{ $closing->version }}">
        <label>Instructions for the client
            <textarea class="form-control" name="reopen_instruction" maxlength="3000" required>Please review and correct your paperwork details and requests, then submit your paperwork details and requests again.</textarea>
        </label>
        <p class="small">Previously released signing forms will be on hold until you approve the changes and release the packet again. Later recording milestones are cleared; history is retained.</p>
        <button class="btn btn-outline-brand">Reopen details and requests</button>
    </form>
</details>
