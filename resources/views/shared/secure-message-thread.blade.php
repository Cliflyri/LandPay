@php
    $olderMessages = $thread->messages->slice(0, -2);
    $recentMessages = $thread->messages->slice(-2);
@endphp
<div class="secure-message-thread">
    @if($olderMessages->isNotEmpty())
        <button class="btn btn-outline-secondary secure-message-history-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#olderMessages" data-show-label="Show {{$olderMessages->count()}} {{$olderMessages->count() === 1 ? 'older message' : 'older messages'}}" aria-expanded="false" aria-controls="olderMessages">
            <span class="history-chevron"></span>
            <span class="history-label">Show {{$olderMessages->count()}} {{$olderMessages->count() === 1 ? 'older message' : 'older messages'}}</span>
        </button>
        <div class="collapse" id="olderMessages">
            @foreach($olderMessages as $message)
                @include('shared.secure-message', ['portal' => $portal])
            @endforeach
        </div>
    @endif
    @foreach($recentMessages as $message)
        @include('shared.secure-message', ['portal' => $portal])
    @endforeach
</div>
