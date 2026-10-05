<div class="mt-3">
    <label class="form-label fw-semibold" for="photos">Making your land your own?</label>
    <p class="text-muted mb-2" id="photo-help">Add photos of your improvement to document your progress &mdash; from your first steps to the finished project. Photos are optional, and you can add them anytime.</p>
    @include('improvements.photo-picker',['pickerId'=>'photos','photoField'=>'photos'])
</div>
