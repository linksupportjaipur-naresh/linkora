@extends('layouts.dashboard')

@section('title', 'QR Code')
@section('page_title', 'QR Code')

@section('content')
    <div class="page-head">
        <div>
            <h2>Your QR code</h2>
            <p>Print it on cards, menus, posters or packaging. Scanning it opens your page.</p>
        </div>
    </div>

    @unless ($profile->is_published)
        <div class="alert alert-warning d-flex align-items-center gap-2">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div class="flex-fill">Your page isn’t published yet, so people who scan this code will see “page not available”.</div>
            <form method="POST" action="{{ route('dashboard.profile.publish') }}">@csrf<button class="btn btn-sm btn-primary">Publish now</button></form>
        </div>
    @endunless

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="p-card">
                <div class="qr-stage">
                    <div class="qr-box"><div id="qr"></div></div>
                    <div class="mt-3 fw-semibold text-center text-break">{{ $user->publicUrl() }}</div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="p-card">
                <h3 class="section-title">Customise</h3>
                <p class="section-sub">Dark colours on a light background scan best.</p>

                <label class="form-label">Colour</label>
                <div class="choice-row mb-4" role="radiogroup" aria-label="QR colour">
                    @foreach (['#1B1233' => 'Navy', '#7955E8' => 'Purple', '#000000' => 'Black', '#157347' => 'Green', '#C2185B' => 'Pink'] as $hex => $name)
                        <div class="opt">
                            <input type="radio" name="qr_color" value="{{ $hex }}" id="qc-{{ $loop->index }}" {{ $loop->first ? 'checked' : '' }}>
                            <label for="qc-{{ $loop->index }}"><span class="d-inline-block rounded-circle me-1 align-middle" style="width:14px;height:14px;background:{{ $hex }}"></span>{{ $name }}</label>
                        </div>
                    @endforeach
                </div>

                <label class="form-label">Download size</label>
                <div class="choice-row mb-4" role="radiogroup" aria-label="Download size">
                    @foreach ([512 => 'Small · 512px', 1024 => 'Medium · 1024px', 2048 => 'Print · 2048px'] as $px => $label)
                        <div class="opt">
                            <input type="radio" name="qr_size" value="{{ $px }}" id="qs-{{ $px }}" {{ $px === 1024 ? 'checked' : '' }}>
                            <label for="qs-{{ $px }}">{{ $label }}</label>
                        </div>
                    @endforeach
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-primary" id="qr-download"><i class="fa-solid fa-download me-1"></i>Download PNG</button>
                    <button type="button" class="btn btn-ghost" data-copy="{{ $user->publicLink() }}"><i class="fa-regular fa-copy me-1"></i>Copy link</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
(function () {
    var url = @json($user->publicLink()), $box = $('#qr');

    function make(el, size, color) {
        $(el).empty();
        return new QRCode(el, { text: url, width: size, height: size, colorDark: color, colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.H });
    }
    function color() { return $('[name=qr_color]:checked').val(); }
    function draw() { make($box[0], 480, color()); }

    $('[name=qr_color]').on('change', draw);

    $('#qr-download').on('click', function () {
        var size = parseInt($('[name=qr_size]:checked').val(), 10), tmp = document.createElement('div');
        make(tmp, size, color());
        var canvas = tmp.querySelector('canvas');
        // Add a white quiet-zone border so the code scans reliably when printed.
        var pad = Math.round(size * 0.08), out = document.createElement('canvas');
        out.width = out.height = size + pad * 2;
        var ctx = out.getContext('2d');
        ctx.fillStyle = '#ffffff'; ctx.fillRect(0, 0, out.width, out.height);
        ctx.drawImage(canvas, pad, pad);
        var a = document.createElement('a');
        a.download = 'linkora-qr-{{ $user->username }}.png';
        a.href = out.toDataURL('image/png');
        a.click();
        lkToast('QR code downloaded.');
    });

    draw();
})();
</script>
@endpush
