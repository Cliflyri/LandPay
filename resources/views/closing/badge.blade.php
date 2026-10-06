@php($badgeClosing = $badgePlan->closing)
@if($badgeClosing && $badgeClosing->status !== 'review' || $closingEligible || $badgeClosing?->eligible_at)
<div class="mt-1">
    <a href="{{ route('admin.plans.show', $badgePlan) }}#closing" class="text-decoration-none" title="Review closing workflow">
        <span class="dashboard-status {{ $badgeClosing?->status === 'completed' ? 'status-current' : 'status-ready-to-close' }}">
            &#10003; {{ $badgeClosing?->label() ?? 'Ready to close' }}
        </span>
    </a>
</div>
@endif
