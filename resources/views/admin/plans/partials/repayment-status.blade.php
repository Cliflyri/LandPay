@auth('web')
@inject('repaymentSnapshot', 'App\Integrations\LandInvest\RepaymentSnapshot')
@php
$repaymentStatus = $repaymentSnapshot->status($repaymentPlan->plan_number);
[$repaymentColor, $repaymentLabel] = match ($repaymentStatus) {
    'paid' => ['#198754', 'Investor principal and agreed profit fully repaid.'],
    'partial' => ['#e0a800', 'Investor repayment partially complete.'],
    'unpaid' => ['#dc3545', 'No investor repayment recorded.'],
    'not_funded' => ['#6c757d', 'No investor funding recorded.'],
    default => ['#6c757d', 'No repayment status available from LandInvest.'],
};
$repaymentGeneratedAt = $repaymentSnapshot->current()['generated_at'] ?? null;
$repaymentTooltip = $repaymentLabel.($repaymentGeneratedAt ? ' Export generated: '.$repaymentGeneratedAt : '');
@endphp
<span class="d-inline-flex align-items-center ms-1" title="{{ $repaymentTooltip }}" role="img" aria-label="{{ $repaymentTooltip }}">
<svg width="12" height="12" viewBox="0 0 16 16" aria-hidden="true" focusable="false" style="flex-shrink:0">
<circle cx="8" cy="8" r="6" fill="{{ $repaymentStatus === null ? 'none' : $repaymentColor }}" stroke="{{ $repaymentColor }}" stroke-width="1.8"/>
@if($repaymentStatus === null)<path d="M4 12L12 4" stroke="{{ $repaymentColor }}" stroke-width="1.8"/>@endif
</svg>
</span>
@endauth
