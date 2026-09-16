@php
    $history = \App\Models\ClientAnnouncementRecipient::query()
        ->where('client_id', auth('client')->user()->client_id)
        ->whereHas('announcement', fn ($query) => $query
            ->whereNotNull('published_at')
            ->whereNull('removed_from_clients_at')
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now())))
        ->with('announcement')
        ->latest()
        ->get();
@endphp

<div class="admin-next-card h-auto mt-4">
    <div class="list-group list-group-flush">
        @forelse($history as $recipient)
            @php($announcement = $recipient->announcement)
            <article class="list-group-item px-0 py-3" @if(request('announcement') === $announcement->uuid) id="selected-announcement" @endif>
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <strong>{{ $announcement->title }}</strong>
                    <span class="small text-muted">{{ ($announcement->starts_at ?? $announcement->published_at)->format('M j, Y') }}</span>
                </div>
                <div class="mt-2 formatted-text">{!! \App\Support\FormattedText::admin($announcement->body) !!}</div>
                <div class="small text-muted mt-2">
                    @if($recipient->acknowledged_at)
                        Acknowledged {{ $recipient->acknowledged_at->format('M j, Y g:i A') }}
                    @elseif($recipient->dismissed_at)
                        Dismissed {{ $recipient->dismissed_at->format('M j, Y g:i A') }}
                    @elseif($announcement->isVisibleNow())
                        Active
                    @endif
                </div>
                @if(!$recipient->acknowledged_at && !$recipient->dismissed_at && $announcement->isVisibleNow())
                    <form class="mt-2" method="post" action="{{ route('portal.announcements.respond', $announcement) }}" onsubmit="return confirm('This announcement will remain available here under Messages > Announcements.')">
                        @csrf
                        <button class="btn btn-sm btn-brand">{{ $announcement->severity === 'information' ? 'Dismiss' : 'Acknowledge' }}</button>
                    </form>
                @endif
            </article>
        @empty
            <p class="mb-0">No announcements yet.</p>
        @endforelse
    </div>
</div>
