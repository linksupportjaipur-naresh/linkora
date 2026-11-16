{{-- Live phone preview of the user's real public page (owner preview mode, not counted as a view). --}}
<div class="sticky-preview">
    <div class="d-flex justify-content-between align-items-center mb-3 px-1">
        <strong>Preview</strong>
        <a href="{{ $user->publicLink() }}" target="_blank" rel="noopener" class="small fw-semibold"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Open</a>
    </div>
    <div class="phone-preview">
        <div class="phone-screen">
            <iframe id="page-preview" src="{{ $user->publicLink() }}?preview=1" title="Preview of your public page" loading="lazy" tabindex="-1"></iframe>
        </div>
    </div>
</div>
@push('scripts')
<script>
    window.reloadPreview = function () {
        var f = document.getElementById('page-preview');
        if (f) f.src = f.src.split('?')[0] + '?preview=1&t=' + Date.now();
    };
</script>
@endpush
