<div class="admin-next-card mt-4">
    <h2 class="mb-1">Communication log</h2>
    <p class="text-muted mb-0">Recorded emails and text messages for this plan, newest first. Times shown in {{ config('app.timezone') }}. Sent does not confirm delivery.</p>
    <div class="table-responsive mt-4">
        <table class="table table-sm table-striped align-middle mb-0">
            <thead><tr><th>Date/time</th><th>Invoice</th><th>Channel</th><th>Type</th><th>Recipient</th><th>Status</th><th>Details</th></tr></thead>
            <tbody>
            @forelse($communications as $entry)
                @php($invoice = $plan->invoices->firstWhere('id', $entry->invoice_id))
                @php($detailsId = 'communication-details-'.$entry->source.'-'.$entry->id)
                @php($hasDetails = $entry->details || $entry->body || $entry->failure_message || $entry->sent_at || $entry->failed_at)
                <tr>
                    <td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($entry->created_at)->format('M j, Y g:i:s A') }}</td>
                    <td>@if($invoice)<a href="{{ route('admin.invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a>@else &mdash; @endif</td>
                    <td>{{ $entry->channel }}</td>
                    <td>{{ str($entry->message_type)->replace(['-', '_'], ' ')->title() }}</td>
                    <td class="text-break">{{ $entry->recipient }}</td>
                    <td><span class="badge {{ in_array($entry->status, ['failed', 'undelivered']) ? 'text-bg-danger' : (in_array($entry->status, ['sent', 'delivered']) ? 'text-bg-success' : 'text-bg-secondary') }}">{{ ucfirst($entry->status) }}</span></td>
                    <td>
                        @if($hasDetails)
                            <button type="button" class="btn btn-link btn-sm p-0 text-nowrap" aria-expanded="false" aria-controls="{{ $detailsId }}" onclick="const row = document.getElementById(this.getAttribute('aria-controls')); row.hidden = !row.hidden; this.setAttribute('aria-expanded', String(!row.hidden)); this.textContent = row.hidden ? 'View details' : 'Hide details';">View details</button>
                        @else &mdash; @endif
                    </td>
                </tr>
                @if($hasDetails)
                <tr id="{{ $detailsId }}" hidden>
                    <td colspan="7" class="p-3" style="overflow-wrap: anywhere; white-space: normal;">
                        @if($entry->details)<div class="small mb-2" style="white-space: pre-wrap">{{ $entry->details }}</div>@endif
                        @if($entry->body)<div class="small mb-2" style="white-space: pre-wrap">{{ html_entity_decode(strip_tags(str_replace(['</p>', '<br>', '<br/>', '<br />', '</div>', '</tr>'], ["\n\n", "\n", "\n", "\n", "\n", "\n"], $entry->body))) }}</div>@endif
                        @if($entry->sent_at)<div class="small mt-1">Sent: {{ \Illuminate\Support\Carbon::parse($entry->sent_at)->format('M j, Y g:i:s A') }}</div>@endif
                        @if($entry->failed_at)<div class="small mt-1">Failed: {{ \Illuminate\Support\Carbon::parse($entry->failed_at)->format('M j, Y g:i:s A') }}</div>@endif
                        @if($entry->failure_message)<div class="small text-danger mt-1">{{ $entry->failure_message }}</div>@endif
                    </td>
                </tr>
                @endif
            @empty
                <tr><td colspan="7" class="text-muted">No recorded communications for this plan.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($communications->hasPages())<div class="mt-3">{{ $communications->links() }}</div>@endif
</div>
