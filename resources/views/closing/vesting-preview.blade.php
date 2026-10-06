@php($vestingDocuments = $closing->documents->where('kind','vesting'))
<p>Please review the vesting form and contact us with any questions before submitting.</p>
@if($closing->vesting_notes)
<details class="mb-3"><summary>Vesting information</summary><p style="white-space:pre-wrap">{{ $closing->vesting_notes }}</p></details>
@endif
@forelse($vestingDocuments as $document)
    @php($guideUrl = route('portal.closing.documents.download',[$plan,$document]))
    @if(strtolower(pathinfo($document->name, PATHINFO_EXTENSION)) === 'pdf')
    <button type="button" class="btn btn-outline-brand mb-3" data-bs-toggle="modal" data-bs-target="#vesting-guide-{{ $document->id }}">View vesting guide</button>
    <a class="btn btn-outline-brand mb-3" href="{{ $guideUrl }}">Download PDF</a>
    <div class="modal fade" id="vesting-guide-{{ $document->id }}" tabindex="-1" aria-labelledby="vesting-title-{{ $document->id }}" aria-hidden="true" data-vesting-modal>
        <div class="modal-dialog modal-xl modal-dialog-centered modal-fullscreen-sm-down"><div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="vesting-title-{{ $document->id }}">Vesting guide &mdash; {{ $document->name }}</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <iframe data-pdf-src="{{ $guideUrl }}?inline=1" title="Vesting guide PDF" style="display:block;width:100%;height:65vh;border:0"></iframe>
            </div>
            <div class="modal-footer">
                <span class="small text-muted me-auto">If the preview is unavailable, open or download the PDF.</span>
                <a class="btn btn-outline-brand" href="{{ $guideUrl }}?inline=1" target="_blank" rel="noopener">Open in new tab</a>
                <a class="btn btn-brand" href="{{ $guideUrl }}">Download PDF</a>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div></div>
    </div>
    @else
    <p><a href="{{ $guideUrl }}">Download {{ $document->name }}</a></p>
    @endif
@empty
<p>Contact us for guidance on how you would like the property titled.</p>
@endforelse
