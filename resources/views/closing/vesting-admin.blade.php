@php
$defaultGuide = app(\App\Services\VestingGuideService::class)->current();
$assignedGuide = $closing->documents->firstWhere('kind','vesting');
$usesSharedGuide = $assignedGuide && str_starts_with($assignedGuide->path,\App\Services\VestingGuideService::PREFIX);
$latestGuide = $assignedGuide && $defaultGuide && $assignedGuide->path === $defaultGuide['path'];
@endphp
<div class="mb-3">
    <p class="mb-2">
        @if($usesSharedGuide) Using default vesting guide{{ $latestGuide ? '' : ' (previous version)' }}: <strong>{{ $assignedGuide->name }}</strong>.
        @elseif($assignedGuide) Using a different PDF for this plan: <strong>{{ $assignedGuide->name }}</strong>.
        @elseif($closing->status === 'review' && $defaultGuide) The current default will be assigned when closing starts.
        @else No vesting guide assigned. @endif
        @if($assignedGuide)
        <a class="ms-2" href="{{ route('admin.closing.documents.download',[$plan,$assignedGuide]) }}?inline=1" target="_blank" rel="noopener">View PDF</a>
        @endif
    </p>
    <details>
        <summary>Change vesting guide for this plan</summary>
        <div class="mt-3">
    <a href="{{ route('admin.settings.index',['section'=>'closing']) }}">Manage shared default</a>
    @if($defaultGuide && !$latestGuide)
    <form method="post" action="{{ route('admin.closing.update',$plan) }}" class="mt-2">
        @csrf <input type="hidden" name="action" value="use_default_vesting"><input type="hidden" name="version" value="{{ $closing->version }}">
        <button class="btn btn-sm btn-outline-brand">{{ $usesSharedGuide ? 'Update to latest guide' : 'Use default vesting guide' }}</button>
    </form>
    @endif
    <p class="small text-muted mt-2">Upload a replacement PDF to change only this plan's guide.</p>
    @include('closing.documents',['documentKind'=>'vesting'])
        </div>
    </details>
</div>
