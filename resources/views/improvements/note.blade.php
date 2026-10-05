<div class="py-2 border-bottom d-flex flex-column flex-lg-row align-items-lg-start gap-2 gap-lg-3">
    <div class="flex-grow-1" style="min-width:0">
    <div class="d-flex flex-wrap align-items-baseline gap-2"><strong class="fs-6">{{$note->sender_type==='admin'?'Admin':($admin?$improvement->client->first_name:'You')}}</strong><small class="text-muted">{{$note->created_at->format('M j, Y g:i A')}}</small></div>
    <p class="fs-6 mb-0" style="white-space:pre-wrap;overflow-wrap:anywhere">{!! \App\Support\FormattedText::linkedPlainText($note->body) !!}</p>


    @if($note->attachments->isNotEmpty())
    <div class="d-flex flex-wrap gap-2 mt-2">
    @foreach($note->attachments as $attachment)
        @php($fileRoute=route($admin?'admin.messages.files.download':'portal.messages.files.download',[$note->thread,$note,$attachment]))
        <button class="secure-message-thumbnail" style="width:100px;height:80px;padding:0;overflow:hidden" type="button" data-bs-toggle="modal" data-bs-target="#secureMessageImageModal" data-message-image="{{$fileRoute}}?inline=1" data-message-name="{{$attachment->name}}" aria-label="Preview {{$attachment->name}}">
            <img src="{{$fileRoute}}?inline=1" alt="{{$attachment->name}}" loading="lazy" style="width:100%;height:100%;object-fit:cover">
        </button>
    @endforeach
    </div>
    @endif
    </div>
    @if($admin)
    <div class="d-flex flex-wrap align-items-center gap-3 mt-1 mt-lg-0 flex-lg-shrink-0">
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
