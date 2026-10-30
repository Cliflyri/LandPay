@php
$documentLabels = ['vesting'=>'Vesting reference PDF','signing'=>'Signing form','recorded'=>'Recorded document'];
$documentsEditable = $documentKind !== 'signing' || (!$closing->submitted_on && $closing->status !== 'completed');
@endphp
@if($documentsEditable)
<form method="post" enctype="multipart/form-data" action="{{ route('admin.closing.documents.upload',$plan) }}" class="mb-3">
    @csrf <input type="hidden" name="version" value="{{ $closing->version }}"><input type="hidden" name="kind" value="{{ $documentKind }}">
    @if($documentKind === 'signing')
    <div class="closing-upload-zone mb-2" data-signing-upload tabindex="0" role="group" aria-labelledby="signing-upload-label" aria-describedby="signing-upload-help">
        <strong id="signing-upload-label">Drop your signing packet here, paste a file, or browse</strong>
        <p id="signing-upload-help" class="small text-muted mb-2">One file: PDF, DOCX, JPG or PNG, up to 10 MB. Click this area and press Ctrl+V (or Command+V) to paste.</p>
        <label class="w-100">Choose signing packet
            <input class="form-control" type="file" name="document" accept=".pdf,.docx,.jpg,.jpeg,.png" required>
        </label>
        <div class="small mt-2" data-upload-selection aria-live="polite"></div>
        <p class="small text-danger mt-2 mb-0" data-upload-error role="alert" hidden></p>
        <button class="btn btn-sm btn-outline-secondary mt-2" type="button" data-upload-remove hidden>Remove selected file</button>
    </div>
    @else
    <label>{{ $documentLabels[$documentKind] }} <span class="text-muted">({{ $documentKind === 'vesting' ? 'PDF' : 'PDF, Word, JPG or PNG' }}; up to 10 MB)</span>
        <input class="form-control" type="file" name="document" accept="{{ $documentKind === 'vesting' ? '.pdf' : '.pdf,.docx,.jpg,.jpeg,.png' }}" required>
    </label>
    @endif
    @if($documentKind === 'signing')<p class="small">Uploading a new signing form replaces all previous signing forms. Upload the complete signing packet as one file.</p>@endif
    <button class="btn btn-outline-brand">Upload {{ strtolower($documentLabels[$documentKind]) }}</button>
</form>
@endif
<h3>{{ $documentKind === 'signing' ? 'Review uploaded signing forms' : 'Uploaded documents' }}</h3>
@forelse($closing->documents->where('kind',$documentKind) as $document)
<div class="d-flex flex-wrap gap-2 align-items-center mb-2">
    <a href="{{ route('admin.closing.documents.download',[$plan,$document]) }}">{{ $document->name }}</a>
    @if($documentsEditable)
    <form method="post" action="{{ route('admin.closing.documents.remove',[$plan,$document]) }}">
        @csrf @method('DELETE') <input type="hidden" name="version" value="{{ $closing->version }}">
        <button class="btn btn-sm btn-outline-danger">Remove</button>
    </form>
    @endif
</div>
@empty <p class="text-muted">No {{ strtolower($documentLabels[$documentKind]) }} uploaded yet.</p>
@endforelse
@if($documentKind === 'signing')
<p class="small">Adding or removing a signing form withdraws the packet and requires release again.</p>
@elseif($documentKind === 'recorded')
<p class="small">Recorded documents appear to clients after closing is complete.</p>
@endif
