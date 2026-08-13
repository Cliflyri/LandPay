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

    @if(!$portal && $isAdminMessage)
        <small class="secure-message-tracking">Client viewed: {{$message->client_viewed_at?->format('M j, Y g:i A') ?? 'Not yet'}}@if($message->attachment_path) &middot; Downloaded: {{$message->attachment_downloaded_at?->format('M j, Y g:i A') ?? 'Not yet'}}@endif</small>
    @endif
</article>
