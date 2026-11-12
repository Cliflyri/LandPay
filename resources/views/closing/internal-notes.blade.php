<section id="closing-internal-notes" class="closing-internal-notes" aria-labelledby="closing-notes-heading">
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <h4 id="closing-notes-heading" class="h6 mb-0">Internal closing notes</h4>
        <span class="badge text-bg-light">Admin only</span>
    </div>
    <p class="small text-muted mt-1 mb-2">Recording confirmed &mdash; final closing notes and follow-up. Not visible to clients or included in notifications.</p>
    <form method="post" action="{{ route('admin.closing.update', $plan) }}">
        @csrf
        <input type="hidden" name="action" value="add_closing_note">
        <input type="hidden" name="version" value="{{ $closing->version }}">
        <label class="visually-hidden" for="closing-note">New internal closing note</label>
        <textarea id="closing-note" name="closing_note" class="form-control form-control-sm" rows="3" maxlength="10000" required aria-describedby="closing-note-help">{{ old('closing_note') }}</textarea>
        <p id="closing-note-help" class="small text-muted my-2">Recording issues and resolutions, final document checks or delivery, outstanding follow-up, or unusual closing decisions.</p>
        @error('closing_note')<p class="text-danger small">{{ $message }}</p>@enderror
        <button class="btn btn-sm btn-outline-brand">Add closing note</button>
    </form>
    @foreach($closing->events()->where('action', 'add_closing_note')->with('user')->get() as $note)
    <div class="closing-internal-note">
        <small class="text-muted">{{ $note->user?->name ?? 'Former admin' }} &middot; {{ $note->created_at->format('M j, Y g:i A') }}</small>
        <p>{{ $note->context['body'] ?? '' }}</p>
    </div>
    @endforeach
</section>
