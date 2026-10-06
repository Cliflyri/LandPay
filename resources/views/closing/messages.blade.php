@php
$threads = $closing->threads()->with(['client','messages.senderClient','messages.senderUser'])
    ->when(!$admin, fn($q) => $q->where('client_id', auth('client')->user()->client_id))->get();
$closingMessages = $threads->flatMap(fn($thread) => $thread->messages->filter(fn($message) => $admin || !$message->hidden_from_client))
    ->sortBy('id')->values();
$readIds = $closingMessages->filter(fn($message) => $message->sender_type === ($admin ? 'client' : 'admin'))->pluck('id')->all();
if ($readIds) \App\Models\SecureMessage::whereIn('id', $readIds)->whereNull($admin ? 'admin_viewed_at' : 'client_viewed_at')
    ->update([$admin ? 'admin_viewed_at' : 'client_viewed_at' => now()]);
@endphp
<div id="{{ $admin ? 'closing-messages' : 'closing-messages-'.$plan->id }}" class="mt-4">
    <h3>Closing messages</h3>
    @if($admin)<p class="small text-muted">Messages are private to the selected client. Paperwork details are shared with current plan members.</p>@endif
    @if($closingMessages->count() > 2)
        <details><summary>Earlier messages ({{ $closingMessages->count() - 2 }})</summary>
            @foreach($closingMessages->slice(0,-2) as $message) @include('closing.message') @endforeach
        </details>
    @endif
    @forelse($closingMessages->take(-2) as $message) @include('closing.message')
    @empty <p class="text-muted">No closing messages yet.</p> @endforelse
    @if($admin || $closing->status === 'active')
    <form method="post" action="{{ route(($admin ? 'admin' : 'portal').'.closing.messages', $plan) }}">
        @csrf
        @if($admin)
        <label>Client
            <select name="client_id" class="form-select" required>
                @foreach($plan->memberships->filter(fn($m) => !$m->effective_to && $m->effective_from->lte(today())) as $membership)
                <option value="{{ $membership->client_id }}">{{ $membership->client->organization_name ?: trim($membership->client->first_name.' '.$membership->client->last_name) }}</option>
                @endforeach
            </select>
        </label>
        @endif
        <label>Message<textarea name="body" class="form-control" required maxlength="10000"></textarea></label>
        <button class="btn btn-outline-brand">Send closing message</button>
    </form>
    @endif
</div>
