<div
    class="alert py-2 px-3 mb-3"
    @if (($monthlyServiceFeeSummary['remaining'] ?? 0) > 0)
        style="background:#fff8d7;border:1px solid #f0d36b;color:#664d03;"
    @else
        style="background:#f8f9fa;border:1px solid #dee2e6;"
    @endif
>
    <div class="d-flex flex-wrap justify-content-between gap-2">
        <strong>
            Service fee for {{ $monthlyServiceFeeSummary['monthLabel'] }}
        </strong>

        <span
            @if($monthlyServiceFeeSummary['remaining'] > 0)
                class="fw-semibold fs-5 text-danger"
                style="color:#7a1f1f !important;"
            @else
                class="fw-semibold"
            @endif
        >
            Assessed: {{ \App\Support\Money::format($monthlyServiceFeeSummary['assessed']) }} &middot; Applied: {{ \App\Support\Money::format($monthlyServiceFeeSummary['total']) }} &middot; Remaining: {{ \App\Support\Money::format($monthlyServiceFeeSummary['remaining']) }}
        </span>

    </div>

    @if (($monthlyServiceFeeSummary['count'] ?? 0) > 0)
        <div class="small text-muted mt-1">
            Payment allocations applied to
            @foreach ($monthlyServiceFeeSummary['entries'] as $entry)
                <a href="{{ route('admin.invoices.show', $entry->invoice_id) }}">
                    {{ $entry->invoice_number }}
                </a>@if (! $loop->last), @endif
            @endforeach
        </div>
    @elseif (($monthlyServiceFeeSummary['total'] ?? 0) > 0)
        <div class="small text-muted mt-1">
            The fee was satisfied without a direct payment allocation, such as by account credit or adjustment.
        </div>
    @else
        <div class="small text-muted mt-1">
            No amount has been applied to this billing month's service fee.
        </div>

        @unless(request()->routeIs('admin.plans.invoices.manual.*'))
        <div class="small mt-1">
            To collect service fees first
            <a href="{{ route('admin.plans.invoices.manual.create', $plan) }}">
                create invoice
            </a>.
        </div>
        @endunless

        @if(request()->routeIs('admin.plans.invoices.manual.*'))
            <div class="small mt-1">
                Add service fees for <strong>{{ $monthlyServiceFeeSummary['monthLabel'] }}</strong> below if desired.
            </div>
        @endif

    @endif
</div>