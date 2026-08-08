<div
    class="alert py-2 px-3 mb-3"
    @if (($monthlyServiceFeeSummary['total'] ?? 0) == 0)
        style="background:#fff8d7;border:1px solid #f0d36b;color:#664d03;"
    @else
        style="background:#f8f9fa;border:1px solid #dee2e6;"
    @endif
>
    <div class="d-flex flex-wrap justify-content-between gap-2">
        <strong>
            Service fees collected in {{ $monthlyServiceFeeSummary['monthLabel'] }}
        </strong>

        <span
            @if($monthlyServiceFeeSummary['total'] == 0)
                class="fw-semibold fs-5 text-danger"
                style="color:#7a1f1f !important;"
            @else
                class="fw-semibold"
            @endif
        >
            {{ \App\Support\Money::format($monthlyServiceFeeSummary['total']) }}
        </span>

    </div>

    @if (($monthlyServiceFeeSummary['count'] ?? 0) > 0)
        <div class="small text-muted mt-1">
            Collected from
            @foreach ($monthlyServiceFeeSummary['entries'] as $entry)
                <a href="{{ route('admin.invoices.show', $entry->invoice_id) }}">
                    {{ $entry->invoice_number }}
                </a>@if (! $loop->last), @endif
            @endforeach
        </div>
    @else
        <div class="small text-muted mt-1">
            No service-fee invoice payments were collected for this plan during this month.
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