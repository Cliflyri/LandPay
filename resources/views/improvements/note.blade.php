<div class="py-2 border-bottom">
    <small class="text-muted"><strong>{{$note->sender_type==='admin'?'Admin':($admin?$improvement->client->first_name:'You')}}</strong> &middot; {{$note->created_at->format('M j, Y g:i A')}}</small>
    <p class="small mb-0" style="white-space:pre-wrap;overflow-wrap:anywhere">{!! \App\Support\FormattedText::linkedPlainText($note->body) !!}</p>

    @if($admin)
    <div class="d-flex flex-wrap align-items-center gap-3 mt-1">
        @if($note->hidden_from_client)<span class="badge text-bg-secondary">Hidden from client</span>@endif
        <form method="post" action="{{route('admin.improvements.notes.visibility',[$improvement,$note])}}">@csrf @method('PATCH')
            <input type="hidden" name="hidden_from_client" value="{{$note->hidden_from_client?0:1}}">
            <button class="btn btn-link btn-sm p-0">{{$note->hidden_from_client?'Show to client':'Hide from client'}}</button>
        </form>
        <form method="post" action="{{route('admin.improvements.notes.destroy',[$improvement,$note])}}" onsubmit="return confirm('Permanently delete this note? This cannot be undone.');">@csrf @method('DELETE')
            <button class="btn btn-link btn-sm text-danger p-0">Delete</button>
        </form>
    </div>
    @endif
</div>
