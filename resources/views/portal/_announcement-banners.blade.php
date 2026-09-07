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
                ->where(fn($q) => $q
                    ->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now())
                )
                ->where(fn($q) => $q
                    ->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now())
                )
            )
            ->with('announcement')
            ->get()
    )

    @foreach($announcementRecipients as $recipient)
        @php($a = $recipient->announcement)
        @php(
            $alert = match($a->severity) {
                'urgent' => 'danger',
                'important' => 'warning',
                default => 'info'
            }
        )

        <div class="container site-container">
            <div class="alert alert-{{$alert}} mt-3 mb-0" role="status">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <strong>{{$a->title}}</strong>
                        <div class="mt-1">
                            {{\Illuminate\Support\Str::limit($a->body,220)}}
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <a
                            class="btn btn-sm btn-outline-brand"
                            href="{{route('portal.announcements.view',$a)}}"
                        >
                            View
                        </a>

                        <form
                            method="post"
                            action="{{route('portal.announcements.respond',$a)}}"
                            onsubmit="return confirm('This announcement will no longer appear as a banner, but will remain available under Messages > Announcements.')"
                        >
                            @csrf
                            <button class="btn btn-sm btn-brand">
                                {{$a->severity==='information'?'Dismiss':'Acknowledge'}}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endauth