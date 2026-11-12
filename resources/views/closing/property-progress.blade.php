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
                    <path d="M3 29h42M24 3v13M12 6l6 12M4 15l12 7M36 6l-6 12M44 15l-12 7M7 8l10 12M41 8L31 20" />
                    <path d="M16 29a8 8 0 0 1 16 0" fill="currentColor" stroke="none" />
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
