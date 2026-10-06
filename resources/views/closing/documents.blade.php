@php
$documentLabels = ['vesting'=>'Vesting reference PDF','signing'=>'Signing form','recorded'=>'Recorded document'];
$documentsEditable = $documentKind !== 'signing' || (!$closing->submitted_on && $closing->status !== 'completed');
@endphp
@if($documentsEditable)
<form method="post" enctype="multipart/form-data" action="{{ route('admin.closing.documents.upload',$plan) }}" class="mb-3">
    @csrf <input type="hidden" name="version" value="{{ $closing->version }}"><input type="hidden" name="kind" value="{{ $documentKind }}">
    <label>{{ $documentLabels[$documentKind] }} <span class="text-muted">({{ $documentKind === 'vesting' ? 'PDF' : 'PDF, Word, JPG or PNG' }}; up to 10 MB)</span>
        <input class="form-control" type="file" name="document" accept="{{ $documentKind === 'vesting' ? '.pdf' : '.pdf,.docx,.jpg,.jpeg,.png' }}" required>
    </label>
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
