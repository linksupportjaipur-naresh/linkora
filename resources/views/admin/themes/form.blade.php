@extends('layouts.admin')

@section('title', $theme->exists ? 'Edit theme' : 'New theme')
@section('page_title', $theme->exists ? 'Edit theme' : 'New theme')

@push('styles')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?{{ collect(config('linkora_design.fonts'))->map(fn ($f) => 'family=' . $f[2])->implode('&') }}&display=swap">
<link rel="stylesheet" href="{{ asset('assets/css/profile-page.css') }}?v={{ filemtime(public_path('assets/css/profile-page.css')) }}">
@endpush

@section('content')
    <a href="{{ route('admin.themes.index') }}" class="small fw-semibold d-inline-block mb-3"><i class="fa-solid fa-arrow-left me-1"></i>All themes</a>

    <form method="POST" action="{{ $theme->exists ? route('admin.themes.update', $theme) : route('admin.themes.store') }}" id="theme-form" novalidate>
        @csrf
        @if ($theme->exists) @method('PUT') @endif
        <div class="row g-4">
            <div class="col-xl-7">
                <div class="p-card">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="name">Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $theme->name) }}" maxlength="40" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 d-flex align-items-end gap-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $theme->is_active) ? 'checked' : '' }} {{ $theme->isDefault() ? 'disabled checked' : '' }}>
                                <label class="form-check-label" for="is_active">Available</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_pro" name="is_pro" value="1" {{ old('is_pro', $theme->is_pro) ? 'checked' : '' }} {{ $theme->isDefault() ? 'disabled' : '' }}>
                                <label class="form-check-label" for="is_pro">Pro only</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="bg">Background</label>
                            <input type="text" class="form-control font-monospace @error('bg') is-invalid @enderror" id="bg" name="bg" value="{{ old('bg', $theme->bg) }}" maxlength="200" required data-live>
                            @error('bg')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">A hex colour like <code>#F3EEFF</code> or a gradient like <code>linear-gradient(180deg, #F3EEFF 0%, #E4D9FF 100%)</code>.</div>
                        </div>
                        @foreach (['text' => 'Text', 'button' => 'Button', 'button_text' => 'Button text'] as $f => $label)
                            <div class="col-md-4">
                                <label class="form-label" for="{{ $f }}">{{ $label }}</label>
                                <div class="color-row py-1">
                                    <input type="color" value="{{ old($f, $theme->$f) }}" data-pick="#{{ $f }}" aria-label="{{ $label }} colour picker">
                                    <input type="text" class="form-control form-control-sm font-monospace border-0 @error($f) is-invalid @enderror" id="{{ $f }}" name="{{ $f }}" value="{{ old($f, $theme->$f) }}" maxlength="7" required data-live>
                                </div>
                                @error($f)<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        @endforeach
                        <div class="col-md-6">
                            <label class="form-label" for="font">Font</label>
                            <select class="form-select" id="font" name="font" data-live>
                                @foreach (config('linkora_design.fonts') as $k => [$label])<option value="{{ $k }}" {{ old('font', $theme->font) === $k ? 'selected' : '' }}>{{ $label }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="button_style">Button style</label>
                            <select class="form-select" id="button_style" name="button_style" data-live>
                                @foreach (config('linkora_design.button_styles') as $k => $label)<option value="{{ $k }}" {{ old('button_style', $theme->button_style) === $k ? 'selected' : '' }}>{{ $label }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button class="btn btn-primary">{{ $theme->exists ? 'Save theme' : 'Create theme' }}</button>
                            <a href="{{ route('admin.themes.index') }}" class="btn btn-ghost">Cancel</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="sticky-preview">
                    <strong class="d-block mb-3 px-1">Preview</strong>
                    <div class="phone-preview"><div class="phone-screen">
                        <div class="lp" id="theme-preview">
                            <div class="lp-inner">
                                <div class="lp-avatar">AS</div>
                                <div class="lp-name">Aanya Sharma</div>
                                <p class="lp-headline">Designer | Creator</p>
                                <div class="lp-divider"><span></span><b></b><span></span></div>
                                <ul class="lp-links">
                                    <li><a class="lp-link lp-card-fill-1"><span class="lp-ico"><i class="fa-solid fa-globe"></i></span><span class="lp-txt"><span class="lp-title">Visit my website</span><span class="lp-sub">Explore more about me</span></span><span class="lp-arrow"><i class="fa-solid fa-arrow-right"></i></span></a></li>
                                    <li><a class="lp-link lp-card-plain"><span class="lp-ico" style="background:#F59E0B"><i class="fa-solid fa-bag-shopping"></i></span><span class="lp-txt"><span class="lp-title">Shop online</span><span class="lp-sub">Check out my products</span></span><span class="lp-arrow"><i class="fa-solid fa-arrow-right"></i></span></a></li>
                                    <li><a class="lp-link lp-card-fill-2"><span class="lp-ico"><i class="fa-regular fa-file-lines"></i></span><span class="lp-txt"><span class="lp-title">My resume</span><span class="lp-sub">Get it as a PDF</span></span><span class="lp-arrow"><i class="fa-solid fa-arrow-right"></i></span></a></li>
                                </ul>
                            </div>
                        </div>
                    </div></div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
(function () {
    var fonts = @json(collect(config('linkora_design.fonts'))->map(fn ($f) => $f[1]));
    var $p = $('#theme-preview');
    function apply() {
        var s = $p[0].style;
        s.setProperty('--lp-bg', $('#bg').val());
        s.setProperty('--lp-text', $('#text').val());
        s.setProperty('--lp-btn', $('#button').val());
        s.setProperty('--lp-btn-text', $('#button_text').val());
        s.setProperty('--lp-font', fonts[$('#font').val()]);
        $p.removeClass(function (i, c) { return (c.match(/lp-btn-\S+/g) || []).join(' '); }).addClass('lp-btn-' + $('#button_style').val());
    }
    $('[data-pick]').on('input', function () { $($(this).data('pick')).val(this.value.toUpperCase()); apply(); });
    $('[data-live]').on('input change', function () {
        var m = /^#[0-9a-fA-F]{6}$/.test(this.value);
        if (m) $('[data-pick="#' + this.id + '"]').val(this.value);
        apply();
    });
    apply();
})();
</script>
@endpush
