@auth('client')
    @php(
        $announcementRecipients = \App\Models\ClientAnnouncementRecipient::query()
            ->where('client_id', auth('client')->user()->client_id)
            ->whereNull('dismissed_at')
            ->whereNull('acknowledged_at')
            ->whereHas('announcement', fn($q) => $q
                ->whereNotNull('published_at')
                ->whereNull('deactivated_at')
                ->whereNull('removed_from_clients_at')
                ->where(fn($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            )
            ->with('announcement')
            ->get()
    )

    @foreach($announcementRecipients as $recipient)
        @php($a = $recipient->announcement)
        @php($alert = match($a->severity) { 'urgent' => 'danger', 'important' => 'warning', default => 'info' })
        @php($isTruncated = \Illuminate\Support\Str::length($a->body) > 220)
        <div class="container site-container">
            <div class="alert alert-{{$alert}} mt-3 mb-0" role="status">
                <strong>{{$a->title}}</strong>
                @if($isTruncated)
                    <div id="announcement-preview-{{$a->id}}" class="mt-1">
                        {{\Illuminate\Support\Str::limit(html_entity_decode(strip_tags(\App\Support\FormattedText::admin($a->body))),220)}}
                    </div>
                    <div class="collapse" id="announcement-body-{{$a->id}}" data-announcement-view-url="{{route('portal.announcements.view',$a)}}">
                        <div class="mt-1 formatted-text">{!!\App\Support\FormattedText::admin($a->body)!!}</div>
                    </div>
                @else
                    <div class="mt-1 formatted-text">{!!\App\Support\FormattedText::admin($a->body)!!}</div>
                @endif
                <div class="d-flex gap-2 mt-3">
                    @if($isTruncated)
                        <button type="button" class="btn btn-sm btn-outline-brand" data-bs-toggle="collapse" data-bs-target="#announcement-body-{{$a->id}}" aria-expanded="false" aria-controls="announcement-body-{{$a->id}}">View</button>
                    @endif
                    <form method="post" action="{{route('portal.announcements.respond',$a)}}" onsubmit="return confirm('This announcement will no longer appear as a banner, but will remain available under Messages > Announcements.')">
                        @csrf
                        <button class="btn btn-sm btn-brand">{{$a->severity==='information'?'Dismiss':'Acknowledge'}}</button>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

    @once
        @push('scripts')
            <script>
                document.querySelectorAll('[data-announcement-view-url]').forEach(function (drawer) {
                    const button = document.querySelector('[data-bs-target="#' + drawer.id + '"]');
                    const preview = document.getElementById(drawer.id.replace('body', 'preview'));
                    drawer.addEventListener('show.bs.collapse', function () {
                        if (preview) preview.hidden = true;
                        if (button) button.textContent = 'Hide';
                        if (!drawer.dataset.viewed) {
                            drawer.dataset.viewed = '1';
                            fetch(drawer.dataset.announcementViewUrl, {headers: {'Accept': 'application/json'}});
                        }
                    });
                    drawer.addEventListener('hide.bs.collapse', function () {
                        if (preview) preview.hidden = false;
                        if (button) button.textContent = 'View';
                    });
                });
            </script>
        @endpush
    @endonce
@endauth
