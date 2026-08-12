<div class="modal fade" id="secureMessageImageModal" tabindex="-1" aria-labelledby="secureMessageImageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="secureMessageImageModalLabel">Image attachment</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img class="secure-message-preview" src="" alt="">
            </div>
            <div class="modal-footer">
                <a class="btn btn-outline-brand" data-message-download href="">Download</a>
                <button type="button" class="btn btn-brand" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
document.getElementById('secureMessageImageModal')?.addEventListener('show.bs.modal', event => {
    const trigger = event.relatedTarget;
    const modal = event.currentTarget;
    const image = modal.querySelector('.secure-message-preview');
    const name = trigger.dataset.messageName;
    image.src = trigger.dataset.messageImage;
    image.alt = name;
    modal.querySelector('.modal-title').textContent = name;
    modal.querySelector('[data-message-download]').href = trigger.dataset.messageImage.replace('?inline=1', '');
});
</script>
@endpush
