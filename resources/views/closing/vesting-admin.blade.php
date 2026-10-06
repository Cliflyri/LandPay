@php
$defaultGuide = app(\App\Services\VestingGuideService::class)->current();
$assignedGuide = $closing->documents->firstWhere('kind','vesting');
$usesSharedGuide = $assignedGuide && str_starts_with($assignedGuide->path,\App\Services\VestingGuideService::PREFIX);
$latestGuide = $assignedGuide && $defaultGuide && $assignedGuide->path === $defaultGuide['path'];
@endphp
<div class="border rounded p-3 mb-3">
    <h3>Vesting guide</h3>
    <p>
        @if($usesSharedGuide) Using default vesting guide{{ $latestGuide ? '' : ' (previous version)' }}: <strong>{{ $assignedGuide->name }}</strong>.
        @elseif($assignedGuide) Using a different PDF for this plan: <strong>{{ $assignedGuide->name }}</strong>.
        @elseif($closing->status === 'review' && $defaultGuide) The current default will be assigned when closing starts.
        @else No vesting guide assigned. @endif
    </p>
    <a href="{{ route('admin.settings.index',['section'=>'closing']) }}">Manage shared default</a>
    @if($defaultGuide && !$latestGuide)
    <form method="post" action="{{ route('admin.closing.update',$plan) }}" class="mt-2">
        @csrf <input type="hidden" name="action" value="use_default_vesting"><input type="hidden" name="version" value="{{ $closing->version }}">
        <button class="btn btn-sm btn-outline-brand">{{ $usesSharedGuide ? 'Update to latest guide' : 'Use default vesting guide' }}</button>
    </form>
    @endif
    <p class="small text-muted mt-2 mb-0">To replace only this plan's guide, upload a Vesting reference PDF below.</p>
</div>
