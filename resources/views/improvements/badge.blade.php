@if($update)
<span class="badge rounded-pill" style="background:{{$update->received_at?'#d1e7dd':'#fff3cd'}};color:{{$update->received_at?'#0f5132':'#664d03'}};white-space:normal">
{{$update->received_at?'Admin received':'Admin notified'}} &middot; {{($update->received_at??$update->created_at)->format('M j, Y')}}
</span>
@endif
