<div class="closing-message">
    <small><strong>{{ $message->sender_type === 'admin' ? 'Admin' : trim(($message->senderClient?->first_name ?? '').' '.($message->senderClient?->last_name ?? '')) }}</strong>
        @if($admin && $message->sender_type === 'admin') &rarr; {{ $threads->firstWhere('id', $message->secure_message_thread_id)?->client?->email }} @endif
        &middot; {{ $message->created_at->format('M j, Y g:i A') }}</small>
    <p>{{ $message->body }}</p>
</div>
