@if(!in_array($closing->{$section.'_status'},['complete','not_required']) && $closing->status !== 'completed')
<form method="post" action="{{ route('admin.closing.update',$plan) }}" class="d-flex flex-wrap align-items-end gap-2">
    @csrf <input type="hidden" name="action" value="review_{{ $section }}"><input type="hidden" name="version" value="{{ $closing->version }}">
    <label class="mb-0">Admin review
        <select name="section_status" class="form-select">
            <option value="complete">{{ $section === 'forms' ? 'Originals received and accepted' : 'Reviewed and complete' }}</option>
            @if($section !== 'details')<option value="not_required">Not required</option>@endif
        </select>
    </label>
    <button class="btn btn-outline-brand">Save review</button>
</form>
@endif
<details id="closing-reopen-{{ $section }}" class="mt-3">
    <summary>Reopen step / request changes</summary>
    <form method="post" action="{{ route('admin.closing.update',$plan) }}" class="mt-2">
        @csrf <input type="hidden" name="action" value="reopen_step"><input type="hidden" name="section" value="{{ $section }}">
        <input type="hidden" name="version" value="{{ $closing->version }}">
        <label>Instructions for the client
            <textarea class="form-control" name="reopen_instruction" maxlength="3000" required>{{ $section === 'forms' ? 'Please review the signing instructions and complete step 3 again. Contact us with any questions.' : 'Please review and correct section '.($section === 'details' ? '1' : '2').', then submit sections 1 and 2 together again.' }}</textarea>
        </label>
        <p class="small">Reopening restores the client controls and yellow instruction banner, and clears later recording milestones. History is retained.</p>
        <button class="btn btn-outline-brand">Reopen step and show client instructions</button>
    </form>
</details>
