@php($defaultGuide = app(\App\Services\VestingGuideService::class)->current())
<div class="tab-pane fade" id="closing-settings" role="tabpanel" tabindex="0">
    <div class="admin-next-card mt-4">
        <h2>Closing defaults</h2>
        <h3 class="h5">Default vesting guide</h3>
        <p>Upload one PDF for newly started closings. Each closing keeps its assigned version when you replace the default. You can update an existing closing or use a different PDF from its Closing panel.</p>
        @if($defaultGuide)
        <p><strong>Current guide:</strong> <a href="{{ route('admin.closing-defaults.download') }}">{{ $defaultGuide['name'] }}</a></p>
        @else <p class="text-muted">No default vesting guide uploaded yet.</p> @endif
        <form method="post" enctype="multipart/form-data" action="{{ route('admin.closing-defaults.update') }}">
            @csrf
            <label class="form-label" for="default-vesting-pdf">{{ $defaultGuide ? 'Replace default PDF' : 'Upload default PDF' }} (up to 10 MB)</label>
            <input class="form-control mb-3" id="default-vesting-pdf" name="document" type="file" accept="application/pdf,.pdf" required>
            <button class="btn btn-brand">Save default vesting guide</button>
        </form>
    </div>
</div>
