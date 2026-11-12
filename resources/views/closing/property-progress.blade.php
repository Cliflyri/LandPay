@if($propertyProgressPlans->isNotEmpty())
<div class="admin-next-card mt-4">
    <h2>Your Property</h2>
    @foreach($propertyProgressPlans as $summary)
    <div id="closing-{{$summary['plan']->id}}">
        <strong>{{$summary['plan']->plan_number}}</strong> &middot; {{$summary['plan']->title}}
        @include('closing.progress',['closing'=>$summary['plan']->closing,'compact'=>true,'showUndo'=>false])
        @if($summary['plan']->closing->status === 'completed' && $summary['plan']->closing->recorded_on)
        <div class="closing-celebration mt-3 mb-2">
            <svg class="closing-celebration-hills" viewBox="0 0 600 100" fill="none" aria-hidden="true" focusable="false">
                <path d="M0 100 Q100 45 230 80 T600 65 M150 100 Q310 10 440 65 T600 85 M340 100 Q490 0 600 35" />
            </svg>
            <div class="closing-celebration-content">
            <div class="closing-celebration-heading">
                <svg class="closing-celebration-sun" viewBox="0 0 48 32" fill="none" aria-hidden="true" focusable="false">
                    <path d="M38.49 24.12L46.22 22.05 M35.49 18.36L41.62 13.22 M30.34 14.41L33.72 7.15 M24.00 13.00L24.00 5.00 M17.66 14.41L14.28 7.15 M12.51 18.36L6.38 13.22 M9.51 24.12L1.78 22.05" />
                    <path d="M14 28a10 10 0 0 1 20 0Z" fill="currentColor" stroke="none" />
                    <path d="M3 29h42" />
                </svg>
                <strong>Congratulations on your purchase!</strong>
            </div>
            @if($summary['plan']->closing->events()->where('action', 'purchase_interest')->where('client_id', auth('client')->user()->client_id)->exists())
                <p class="mb-0 mt-1" role="status">Thanks! We've let our team know you're interested.</p>
            @else
                <p class="small mt-2 mb-3">The only thing better than completing a purchase is finding your next opportunity. Let us know you're interested, and we'll contact you with our current best deals.</p>
                <form method="post" action="{{ route('portal.closing.purchase-interest', $summary['plan']) }}">
                    @csrf
                    <button class="btn btn-sm btn-brand closing-celebration-button">I'm interested in another property</button>
                </form>
            @endif
            </div>
        </div>
        @endif
    </div>
    @endforeach
</div>
@endif
