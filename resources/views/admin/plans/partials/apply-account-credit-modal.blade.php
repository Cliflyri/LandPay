@if($creditApplicationPreview['applied'] > 0)
<div class="modal fade" id="apply-account-credit" tabindex="-1" aria-labelledby="apply-account-credit-title" aria-hidden="true">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <form method="post" action="{{ route('admin.plans.account-credit.apply', $plan) }}">
            @csrf
            <input type="hidden" name="preview_token" value="{{ $creditApplicationPreview['token'] }}">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="apply-account-credit-title">Apply account credit</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>{{ $plan->plan_number }} — {{ $plan->title }}</strong><br>
                    Available credit: <strong>{{ \App\Support\Money::format($creditApplicationPreview['available']) }}</strong></p>
                <p class="text-muted small">Credit applies to the oldest open invoices first.</p>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead><tr><th>Invoice</th><th>Due date</th><th class="text-end">Current balance</th><th class="text-end">Credit to apply</th><th class="text-end">Balance afterward</th></tr></thead>
                        <tbody>
                        @foreach($creditApplicationPreview['rows'] as $row)
                            <tr><td>{{ $row['number'] }}
                                    @foreach($row['descriptions'] as $description)
                                        <div class="small text-muted text-wrap text-break">{{ $description }}</div>
                                    @endforeach
                                </td><td class="text-nowrap">{{ $row['due_date'] ? \Illuminate\Support\Carbon::parse($row['due_date'])->format('M j, Y') : '—' }}</td>
                                <td class="money-cell">{{ \App\Support\Money::format($row['balance']) }}</td>
                                <td class="money-cell">{{ \App\Support\Money::format($row['applied']) }}</td>
                                <td class="money-cell">{{ \App\Support\Money::format($row['remaining']) }}</td></tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <dl class="payment-preview-summary mb-0">
                    <div><dt>Total credit applied</dt><dd>{{ \App\Support\Money::format($creditApplicationPreview['applied']) }}</dd></div>
                    <div><dt>Unapplied credit remaining</dt><dd>{{ \App\Support\Money::format($creditApplicationPreview['remaining']) }}</dd></div>
                </dl>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-brand">Apply {{ \App\Support\Money::format($creditApplicationPreview['applied']) }} credit</button>
            </div>
        </form>
    </div></div>
</div>
@endif