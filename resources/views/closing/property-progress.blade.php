@if($propertyProgressPlans->isNotEmpty())
<div class="admin-next-card mt-4">
    <h2>Your Property</h2>
    @foreach($propertyProgressPlans as $summary)
    <div id="closing-{{$summary['plan']->id}}">
        <strong>{{$summary['plan']->plan_number}}</strong> &middot; {{$summary['plan']->title}}
        @include('closing.progress',['closing'=>$summary['plan']->closing,'compact'=>true,'showUndo'=>false])
        @if($summary['plan']->closing->status === 'completed' && $summary['plan']->closing->recorded_on)
        <div class="rounded p-3 mt-3 mb-2" style="background:#f5f8f6">
            <strong>Congratulations on your purchase!</strong>
            @if($summary['plan']->closing->events()->where('action', 'purchase_interest')->where('client_id', auth('client')->user()->client_id)->exists())
                <p class="mb-0 mt-1" role="status">Thanks! We've let our team know you're interested.</p>
            @else
                <p class="small mt-1 mb-2">The only thing better than completing a purchase is finding your next opportunity. Let us know you're interested, and we'll contact you with our current best deals.</p>
                <form method="post" action="{{ route('portal.closing.purchase-interest', $summary['plan']) }}">
                    @csrf
                    <button class="btn btn-sm btn-outline-brand">I'm interested in another property</button>
                </form>
            @endif
        </div>
        @endif
    </div>
    @endforeach
</div>
@endif
