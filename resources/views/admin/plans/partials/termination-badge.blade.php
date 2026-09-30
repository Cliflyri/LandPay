@if($plan?->status === 'terminated')<span class="badge text-bg-secondary" title="{{ $plan->termination_reason ?: 'No termination reason recorded.' }}">Terminated</span>@endif
