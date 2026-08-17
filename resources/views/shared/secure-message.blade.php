@php
    $isAdminMessage = $message->sender_type === 'admin';
    $isImage = in_array($message->attachment_mime, ['image/jpeg', 'image/png'], true);
    $attachmentRoute = $portal
        ? route('portal.messages.download', [$thread, $message])
        : route('admin.messages.download', [$thread, $message]);
    $senderName = $isAdminMessage
        ? ($portal ? 'LandPay' : ($message->senderUser?->name ?? 'Administrator'))
        : ($portal ? 'You' : ($message->senderClient?->organization_name ?: trim(($message->senderClient?->first_name ?? '').' '.($message->senderClient?->last_name ?? ''))));
@endphp
<article class="secure-message-bubble {{$isAdminMessage ? 'is-admin' : 'is-client'}}">
    <div class="secure-message-meta">
        <strong>{{$senderName}}</strong>
        <span>{{$message->created_at->format('M j, Y g:i A')}}</span>
    </div>
    <div class="secure-message-body">{{$message->body}}</div>

    @if(!$portal && $isAdminMessage)
        @php($latestRevision=$message->revisions->last())
        <div class="collapse mt-2" id="edit-message-{{$message->uuid}}">
            <form method="post" action="{{route('admin.messages.update',[$thread,$message])}}">@csrf @method('PUT')
                <textarea class="form-control" name="body" rows="4" maxlength="10000">{{$message->body}}</textarea>
                <div class="form-text">Previously sent email or text notifications cannot be changed. Card information must not be entered here.</div>
                <div class="d-flex gap-2 mt-2"><button class="btn btn-sm btn-brand" type="submit">Save</button><button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#edit-message-{{$message->uuid}}">Cancel</button></div>
            </form>
        </div>
    @endif

    @if($message->attachment_path)
        @if($isImage)
            <button class="secure-message-thumbnail" type="button" data-bs-toggle="modal" data-bs-target="#secureMessageImageModal" data-message-image="{{$attachmentRoute}}?inline=1" data-message-name="{{$message->attachment_name}}" aria-label="Preview {{$message->attachment_name}}">
                <img src="{{$attachmentRoute}}?inline=1" alt="{{$message->attachment_name}}" loading="lazy">
            </button>
        @endif
        <div class="secure-message-attachment">
            <a class="btn btn-sm btn-outline-brand" href="{{$attachmentRoute}}">Download {{$message->attachment_name}}</a>
            @unless($portal)
                <div class="dropdown">
                    <button class="btn btn-sm btn-light dashboard-menu-button" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Attachment actions">&#8942;</button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{$attachmentRoute}}">Download</a></li>
                        <li><form method="post" action="{{route('admin.messages.attachments.destroy',[$thread,$message])}}" onsubmit="return confirm('Permanently delete this attachment?')">@csrf @method('DELETE')<button class="dropdown-item text-danger" type="submit">Delete attachment</button></form></li>
                    </ul>
                </div>
            @endunless
        </div>
    @endif

    @foreach($message->attachments as $attachment)
        @php($fileRoute=$portal ? route('portal.messages.files.download',[$thread,$message,$attachment]) : route('admin.messages.files.download',[$thread,$message,$attachment]))
        @if(in_array($attachment->mime,['image/jpeg','image/png'],true))
            <button class="secure-message-thumbnail" type="button" data-bs-toggle="modal" data-bs-target="#secureMessageImageModal" data-message-image="{{$fileRoute}}?inline=1" data-message-name="{{$attachment->name}}" aria-label="Preview {{$attachment->name}}"><img src="{{$fileRoute}}?inline=1" alt="{{$attachment->name}}" loading="lazy"></button>
        @endif
        <div class="secure-message-attachment">
            <a class="btn btn-sm btn-outline-brand" href="{{$fileRoute}}">Download {{$attachment->name}}</a>
            @unless($portal)<form method="post" action="{{route('admin.messages.files.destroy',[$thread,$message,$attachment])}}" onsubmit="return confirm('Permanently delete this attachment?')">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger" type="submit">Delete</button></form>@endunless
        </div>
    @endforeach

    @foreach($message->documents as $document)
        @php($documentAvailable=!$portal || ($document->visible_to_client && !$document->archived_at))
        <div class="secure-message-attachment">
            @if($documentAvailable)
                <div><strong>{{$document->name}}</strong><div class="small text-muted">{{str($document->category)->replace('_',' ')->title()}} &middot; Shared document</div></div>
                @php($canPreview=in_array($document->mime,['application/pdf','image/jpeg','image/png'],true))
                <a class="btn btn-sm btn-outline-brand" href="{{$portal ? ($canPreview ? route('portal.documents.preview',$document) : route('portal.documents.download',$document)) : ($canPreview ? route('admin.documents.preview',$document) : route('admin.documents.download',$document))}}">{{$canPreview?'View document':'Download'}}</a>
            @else
                <span class="text-muted">This document is no longer available.</span>
            @endif
        </div>
    @endforeach

    @if(!$portal && $isAdminMessage)
        <small class="secure-message-tracking">
            <button class="btn btn-link btn-sm p-0 align-baseline" type="button" data-bs-toggle="collapse" data-bs-target="#edit-message-{{$message->uuid}}" aria-expanded="false" title="{{$latestRevision ? 'Edited '.$latestRevision->created_at->format('M j, Y g:i A').' by '.($latestRevision->editor?->name ?? 'Administrator') : 'Edit message text'}}">Edit</button>
            &middot; Client viewed: {{$message->client_viewed_at?->format('M j, Y g:i A') ?? 'Not yet'}}
            @if($message->attachment_path)
                &middot; Downloaded: {{$message->attachment_downloaded_at?->format('M j, Y g:i A') ?? 'Not yet'}}
            @endif
        </small>
    @endif
</article>
