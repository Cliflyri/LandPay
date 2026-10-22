@if($propertyProgressPlans->isNotEmpty())
<div class="admin-next-card mt-4">
    <h2>Your Property</h2>
    @foreach($propertyProgressPlans as $summary)
    <div id="closing-{{$summary['plan']->id}}">
        <strong>{{$summary['plan']->plan_number}}</strong> &middot; {{$summary['plan']->title}}
        @include('closing.progress',['closing'=>$summary['plan']->closing,'compact'=>true,'showUndo'=>false])
    </div>
    @endforeach
</div>
@endif
