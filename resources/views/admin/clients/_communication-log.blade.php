<section class="admin-next-card mt-4">
    <h2>Communication log</h2>
    <p class="text-muted">Existing records for this client across all plans, newest first. Times shown in {{ config('app.timezone') }}. Sent does not confirm delivery. Secure-message notification emails do not have a complete historical delivery log.</p>
    <div class="table-responsive">
        <table class="table table-sm table-striped align-middle">
            <thead><tr><th>Date/time</th><th>Plan</th><th>Channel / type</th><th>Recipient</th><th>Status</th><th>Details</th></tr></thead>
            <tbody>
            @forelse($communications as $entry)
                <tr>
                    <td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($entry->created_at)->format('M j, Y g:i:s A') }}</td>
                    <td>@if($entry->plan)<a href="{{ route('admin.plans.show', $entry->plan) }}">{{ $entry->plan->plan_number }}</a>@else Client-level @endif</td>
                    <td>{{ $entry->channel }}<small class="d-block text-muted">{{ str($entry->message_type)->replace(['-', '_'], ' ')->title() }}</small></td>
                    <td class="text-break">{{ $entry->recipient ?: 'See details' }}</td>
                    <td>{{ ucfirst($entry->status) }}</td>
                    <td>
                        <details>
                            <summary style="cursor:pointer">View details</summary>
                            <div class="py-2" style="min-width:220px; max-width:650px; overflow-wrap:anywhere">
                                @if($entry->subject)<strong class="d-block mb-2">{{ $entry->subject }}</strong>@endif
                                @if($entry->body)<div style="white-space:pre-wrap">{{ $entry->body }}</div>@endif
                                @if($entry->sent_at)<div class="small mt-2">Sent: {{ \Illuminate\Support\Carbon::parse($entry->sent_at)->format('M j, Y g:i:s A') }}</div>@endif
                                @if($entry->failed_at)<div class="small">Failed: {{ \Illuminate\Support\Carbon::parse($entry->failed_at)->format('M j, Y g:i:s A') }}</div>@endif
                                @if($entry->failure_message)<p class="text-danger">{{ $entry->failure_message }}</p>@endif
                                @if($entry->invoice)<a class="d-block mt-2" href="{{ route('admin.invoices.show', $entry->invoice) }}">Invoice: {{ $entry->invoice->invoice_number }}</a>@endif
                                @if($entry->thread_uuid)<a class="d-block mt-2" href="{{ route('admin.messages.show', $entry->thread_uuid) }}">Open secure-message thread</a>@endif
                                @if($entry->source === 'closing_events' && $entry->plan)<a class="d-block mt-2" href="{{ route('admin.plans.show', $entry->plan) }}#closing">Open closing</a>@endif
                            </div>
                        </details>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-muted">No recorded communications for this client.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($communications->hasPages()){{ $communications->links() }}@endif
</section>
