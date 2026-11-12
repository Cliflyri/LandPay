@php
$note = $closing->events()->where('action', 'save_closing_note')->with('user')->first();
$noteBody = $note ? ($note->context['body'] ?? '') : $closing->events()->where('action', 'add_closing_note')->reorder()->oldest('id')->get()->map(fn ($entry) => $entry->context['body'] ?? '')->filter()->implode("\n\n");
$editingNote = !$noteBody || old('action') === 'save_closing_note';
@endphp
<section id="closing-internal-notes" class="closing-internal-notes" aria-labelledby="closing-notes-heading">
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <h4 id="closing-notes-heading" class="h6 mb-0">Internal closing notes</h4>
        <span class="badge text-bg-light">Admin only</span>
    </div>
    @if($noteBody)
    <div class="closing-internal-note">
        @if($note)<small class="text-muted">Updated by {{ $note->user?->name ?? 'Former admin' }} &middot; {{ $note->created_at->format('M j, Y g:i A') }}</small>@endif
        <p>{{ $noteBody }}</p>
    </div>
    @endif
    <details class="closing-note-editor" @if($editingNote) open @endif>
        <summary class="small text-decoration-underline">{{ $noteBody ? 'Edit' : 'Add closing note' }}</summary>
        <form class="mt-2" method="post" action="{{ route('admin.closing.update', $plan) }}">
            @csrf
            <input type="hidden" name="action" value="save_closing_note">
            <input type="hidden" name="version" value="{{ $closing->version }}">
            <label class="visually-hidden" for="closing-note">Internal closing note</label>
            <textarea id="closing-note" name="closing_note" class="form-control form-control-sm" rows="4" maxlength="10000" required aria-describedby="closing-note-help">{{ old('closing_note', $noteBody) }}</textarea>
            <p id="closing-note-help" class="small text-muted my-2">Recording issues and resolutions, final document checks or delivery, outstanding follow-up, or unusual closing decisions.</p>
            @error('closing_note')<p class="text-danger small">{{ $message }}</p>@enderror
            <button class="btn btn-sm btn-outline-brand">Save closing note</button>
        </form>
    </details>

</section>
