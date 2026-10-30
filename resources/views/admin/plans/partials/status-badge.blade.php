                            @if ($badgePlan->status === 'paused')
                                <a
                                    href="{{ route('admin.plans.show', $badgePlan) }}#pause-plan-controls"
                                    class="text-decoration-none"
                                    title="Click to resume this payment plan"
                                >
                                    <span class="dashboard-status status-current" @if($badgePlan->paused_on_hold) style="background:#e5d8c4;color:#514536" @endif>
                                        {{ $badgePlan->paused_on_hold ? 'On hold' : '❚❚ Paused' }}
                                    </span>
                                </a>
                            @else
@if($badgePlan->status === 'terminated')
    @include('admin.plans.partials.termination-badge', ['plan' => $badgePlan])
@else
    <span class="dashboard-status {{ 'status-' . str($badgeStatus)->slug() }}">
        {{ $badgeStatus }}
    </span>
@endif
                            @endif

