{{-- Shared "Adjust your photo" dialog (My Profile + Settings). Wired up by public/assets/js/avatar-cropper.js. --}}
@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
@endpush
<div class="modal fade" id="avatarCropModal" tabindex="-1" aria-labelledby="avatarCropTitle" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content crop-modal">
            <div class="modal-header">
                <h2 class="modal-title" id="avatarCropTitle">Adjust your photo</h2>
                <button type="button" class="btn-close" data-crop-cancel aria-label="Cancel"></button>
            </div>
            <div class="modal-body">
                <p class="crop-hint"><i class="fa-solid fa-hand me-1"></i>Drag to move · scroll, pinch or use the slider to zoom · arrow keys nudge it.</p>
                <div class="crop-stage" id="cropStage">
                    <img id="avatarCropImg" alt="Photo being adjusted">
                    <div class="crop-loading" id="cropLoading"><i class="fa-solid fa-spinner fa-spin fa-lg"></i></div>
                </div>
                <div class="crop-tools">
                    <label class="crop-zoom" for="cropZoom">
                        <i class="fa-solid fa-magnifying-glass-minus" aria-hidden="true"></i>
                        <input type="range" id="cropZoom" min="0" max="100" step="1" value="0" aria-label="Zoom">
                        <i class="fa-solid fa-magnifying-glass-plus" aria-hidden="true"></i>
                    </label>
                    <div class="crop-btns">
                        <button type="button" data-crop="rotate-left" title="Rotate left" aria-label="Rotate left"><i class="fa-solid fa-rotate-left"></i></button>
                        <button type="button" data-crop="rotate-right" title="Rotate right" aria-label="Rotate right"><i class="fa-solid fa-rotate-right"></i></button>
                        <button type="button" data-crop="flip" title="Flip" aria-label="Flip horizontally"><i class="fa-solid fa-left-right"></i></button>
                        <button type="button" data-crop="reset" title="Reset" aria-label="Reset"><i class="fa-solid fa-arrows-rotate"></i></button>
                    </div>
                </div>
                <div class="crop-preview-row">
                    <span class="crop-prev lg"></span><span class="crop-prev sm"></span>
                    <span>This is how it will look on your page.</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-crop-cancel>Cancel</button>
                <button type="button" class="btn btn-primary" id="cropApply"><i class="fa-solid fa-check me-1"></i>Use this photo</button>
            </div>
        </div>
    </div>
</div>
@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
    <script src="{{ asset('assets/js/avatar-cropper.js') }}?v={{ filemtime(public_path('assets/js/avatar-cropper.js')) }}"></script>
@endpush
