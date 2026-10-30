@if($paymentHistory->isNotEmpty())
<section class="admin-next-card mt-4" aria-labelledby="payments-applied-title">
    <h2 id="payments-applied-title">Payments applied</h2>
    <p class="small text-muted">Amounts below apply to this invoice only. Account credit is not a new payment received.</p>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Date</th><th>Method / source</th><th>Reference</th><th class="text-end">Applied to this invoice</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach($paymentHistory as $entry)
                <tr>
                    <td class="text-nowrap">{{ $entry['date']->format('M j, Y') }}</td>
                    <td>{{ $entry['method'] }}</td>
                    <td>{{ $entry['reference'] ?: '—' }}</td>
                    <td class="money-cell">@if($entry['reversal'])<s>{{ \App\Support\Money::format($entry['amount']) }}</s>@else{{ \App\Support\Money::format($entry['amount']) }}@endif</td>
                    <td>@if($entry['reversal'])<span class="badge text-bg-secondary">Reversed</span><small class="d-block text-muted">{{ $entry['reversal']->posted_at->format('M j, Y') }}</small>@else Applied @endif</td>
                    <td>@if($entry['payment'])<a href="{{ route('admin.payments.show', $entry['payment']) }}">View payment</a>@endif</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot><tr><th colspan="3">Net applied</th><th class="money-cell">{{ \App\Support\Money::format((int) $paymentHistory->filter(fn ($entry) => !$entry['reversal'])->sum('amount')) }}</th><td colspan="2"></td></tr></tfoot>
        </table>
    </div>
</section>
@endif