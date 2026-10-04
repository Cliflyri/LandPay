<div data-improvement-photo-picker class="mt-2">
    <label class="visually-hidden" for="{{$pickerId}}">Choose photos</label>
    <input class="form-control form-control-sm" id="{{$pickerId}}" data-photo-files type="file" name="{{$photoField}}[]" accept="image/jpeg,image/png" multiple aria-describedby="{{$pickerId}}-help">
    <div data-photo-controls class="d-none flex-wrap gap-2">
        <button type="button" class="btn btn-sm btn-outline-brand" data-photo-choose>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8" cy="8" r="1.5"/><path d="m3 17 5-5 4 4 4-6 5 7"/></svg>
            Choose photos
        </button>
        <button type="button" class="btn btn-sm btn-outline-brand" data-photo-take>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M8 5 9.5 3h5L16 5h4a1 1 0 0 1 1 1v13H3V6a1 1 0 0 1 1-1Z"/><circle cx="12" cy="12" r="4"/></svg>
            Take photo
        </button>
        <div role="textbox" tabindex="0" contenteditable="true" spellcheck="false" class="btn btn-sm btn-outline-brand d-inline-flex align-items-center justify-content-center" style="width:36px;height:36px;padding:0;overflow:hidden;caret-color:transparent" data-photo-paste aria-label="Paste photo from clipboard" aria-describedby="{{$pickerId}}-paste-help" title="Paste photo: click, or focus and press Ctrl+V / Command+V">
            <svg contenteditable="false" style="pointer-events:none" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="5" y="5" width="14" height="16" rx="2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M8 17l3-3 2 2 3-4"/></svg>
        </div>
    </div>
    <small class="text-muted d-none mt-1" data-photo-paste-help id="{{$pickerId}}-paste-help" role="status"></small>
    <input data-photo-camera type="file" accept="image/*" capture="environment" hidden aria-label="Take photo">
    <small class="text-muted d-block mt-1" id="{{$pickerId}}-help">Up to 5 JPG or PNG photos, 10 MB each.</small>
    <p class="small text-danger mb-0 mt-1" data-photo-error role="alert"></p>
    <div class="d-flex flex-wrap gap-2 mt-2" data-photo-previews aria-live="polite"></div>
</div>
@once
@push('scripts')
<script src="{{asset('assets/js/improvement-photo-picker.js')}}?v={{filemtime(public_path('assets/js/improvement-photo-picker.js'))}}" defer></script>
@endpush
@endonce
