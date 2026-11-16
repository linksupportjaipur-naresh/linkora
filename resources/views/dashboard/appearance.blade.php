@extends('layouts.dashboard')

@section('title', 'Appearance')
@section('page_title', 'Appearance')

@php
    $fonts = config('linkora_design.fonts');
    $firstHex = function ($value) { preg_match('/#[0-9a-fA-F]{6}/', $value, $m); return $m[0] ?? '#FFFFFF'; };
    $colorFields = [
        'bg_color'          => ['Background', 'bg'],
        'text_color'        => ['Text', 'text'],
        'button_color'      => ['Button', 'button'],
        'button_text_color' => ['Button text', 'button_text'],
    ];
@endphp

@push('styles')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?{{ collect($fonts)->map(fn ($f) => 'family=' . $f[2])->implode('&') }}&display=swap">
<link rel="stylesheet" href="{{ asset('assets/css/profile-page.css') }}?v={{ filemtime(public_path('assets/css/profile-page.css')) }}">
@endpush

@section('content')
    <form method="POST" action="{{ route('dashboard.appearance.update') }}" id="design-form" enctype="multipart/form-data" novalidate>
        @csrf
        <div class="row g-4">
            <div class="col-xl-7">
                @if ($errors->any())
                    <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation me-2"></i>{{ $errors->first() }}</div>
                @endif

                <div class="p-card mb-3">
                    <h3 class="section-title">Themes</h3>
                    <p class="section-sub">Pick a ready-made look. You can fine-tune it below.</p>
                    <div class="theme-grid-pick" role="radiogroup" aria-label="Theme">
                        @foreach ($themes as $key => $t)
                            @php($locked = ! empty($t['is_pro']) && ! $isPro && $design['theme'] !== $key)
                            <label class="theme-card {{ $locked ? 'locked' : '' }}" @if ($locked) title="Available on paid plans" @endif>
                                <input type="radio" name="theme" value="{{ $key }}" {{ old('theme', $design['theme']) === $key ? 'checked' : '' }} {{ $locked ? 'disabled' : '' }}>
                                <span class="swatch" style="background: {{ $t['bg'] }}">
                                    <span class="av" style="background: {{ $t['button'] }}"></span>
                                    @foreach (range(1, 3) as $i)
                                        <span class="bt" style="background: {{ $t['button_style'] === 'outline' ? 'transparent' : $t['button'] }}; border: 2px solid {{ $t['button'] }}; border-radius: {{ ['pill' => '999px', 'rounded' => '6px', 'square' => '2px', 'outline' => '6px', 'shadow' => '4px'][$t['button_style']] }}"></span>
                                    @endforeach
                                </span>
                                <span class="name">{{ $t['name'] }}@if (! empty($t['is_pro'])) <i class="fa-solid {{ $locked ? 'fa-lock' : 'fa-crown' }} text-warning small" aria-label="Pro theme"></i>@endif</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="p-card mb-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h3 class="section-title">Colours</h3>
                            <p class="section-sub">Change the background, text and button colours.</p>
                        </div>
                        <button type="button" class="btn btn-sm btn-ghost" id="reset-colors"><i class="fa-solid fa-rotate-left me-1"></i>Reset</button>
                    </div>
                    <div class="row g-2">
                        @foreach ($colorFields as $field => [$label, $key])
                            <div class="col-sm-6">
                                <div class="color-row">
                                    <input type="color" id="picker-{{ $field }}" value="{{ $firstHex($design[$key]) }}" data-color-field="{{ $field }}" aria-label="{{ $label }} colour">
                                    <input type="hidden" name="{{ $field }}" id="{{ $field }}" value="{{ old($field, $custom[$field] ?? '') }}">
                                    <div>
                                        <div class="lbl">{{ $label }}</div>
                                        <div class="hex" id="hex-{{ $field }}">{{ ($custom[$field] ?? null) ?: 'Theme' }}</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="p-card">
                    <h3 class="section-title">Font</h3>
                    <p class="section-sub mb-2">{{ count($fonts) }} fonts. The Hindi group also supports Devanagari (हिन्दी).</p>
                    {{-- Shows the first two groups; the rest scroll inside the box (height set by JS below) --}}
                    <div class="font-picker-wrap mb-4">
                    <div class="font-picker" id="font-picker" role="radiogroup" aria-label="Font" tabindex="-1">
                        @foreach (config('linkora_design.font_groups') as $group => $groupLabel)
                            @php($inGroup = collect($fonts)->filter(fn ($f) => ($f[3] ?? 'Modern') === $group))
                            @continue($inGroup->isEmpty())
                            <div class="font-group-label">{{ $groupLabel }}</div>
                            <div class="font-grid">
                                @foreach ($inGroup as $key => [$label, $family])
                                    <div class="font-opt">
                                        <input type="radio" name="font" value="{{ $key }}" id="font-{{ $key }}" {{ old('font', $design['font']) === $key ? 'checked' : '' }}>
                                        <label for="font-{{ $key }}" style="font-family: {{ $family }}">
                                            <span class="font-sample">{{ $group === 'Hindi' ? 'Aa अ' : 'Aa' }}</span>
                                            <span class="font-name">{{ $label }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                    <button type="button" class="font-more" id="font-more" aria-controls="font-picker">
                        <span>More fonts</span><i class="fa-solid fa-chevron-down"></i>
                    </button>
                    </div>

                    <h3 class="section-title">Button style</h3>
                    <div class="choice-row mt-3" role="radiogroup" aria-label="Button style">
                        @foreach (config('linkora_design.button_styles') as $key => $label)
                            <div class="opt">
                                <input type="radio" name="button_style" value="{{ $key }}" id="bs-{{ $key }}" {{ old('button_style', $design['button_style']) === $key ? 'checked' : '' }}>
                                <label for="bs-{{ $key }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="save-bar">
                    <span class="hint" id="unsaved-hint" hidden><i class="fa-solid fa-circle text-warning me-1" style="font-size:.6rem"></i>Unsaved changes</span>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check me-1"></i>Save design</button>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="sticky-preview">
                    <div class="d-flex justify-content-between align-items-center mb-3 px-1">
                        <strong>Mobile preview</strong>
                        <a href="{{ $user->publicLink() }}" target="_blank" rel="noopener" class="small fw-semibold"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Open</a>
                    </div>
                    <div class="phone-preview" aria-label="Live preview of your page">
                        <div class="phone-screen" id="live-preview">
                            @include('public.partials.page', ['linkHref' => false, 'showShare' => false])
                        </div>
                    </div>

                    {{-- Background image --}}
                    @php($bgUrl = $profile->bgImageUrl())
                    <div class="bg-picker {{ $bgUrl ? 'has-image' : '' }}" id="bg-picker">
                        <div class="bg-thumb" id="bg-thumb" @if ($bgUrl) style="background-image: url('{{ $bgUrl }}')" @endif>
                            <i class="fa-regular fa-image"></i>
                        </div>
                        <div class="bg-body">
                            <div class="fw-semibold">Background image</div>
                            <div class="small text-muted" id="bg-hint">{{ $bgUrl ? 'Shown behind your page.' : 'JPG, PNG or WebP · up to 5 MB' }}</div>
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                <label class="btn btn-sm btn-outline-primary mb-0" for="bg_image"><i class="fa-solid fa-upload me-1"></i><span id="bg-upload-label">{{ $bgUrl ? 'Change' : 'Upload' }}</span></label>
                                <input type="file" class="d-none" id="bg_image" name="bg_image" accept="image/jpeg,image/png,image/webp">
                                <button type="button" class="btn btn-sm btn-ghost" id="bg-remove" @unless ($bgUrl) hidden @endunless><i class="fa-regular fa-trash-can me-1"></i>Remove</button>
                                <input type="hidden" name="remove_bg_image" id="remove_bg_image" value="0">
                            </div>
                            @error('bg_image')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="bg-overlay-row" id="bg-overlay-row" @unless ($bgUrl) hidden @endunless>
                            <span class="small fw-semibold me-1">Tint</span>
                            <div class="choice-row" role="radiogroup" aria-label="Background tint">
                                @foreach (['theme' => 'Theme', 'light' => 'Light', 'dark' => 'Dark', 'none' => 'None'] as $k => $label)
                                    <div class="opt">
                                        <input type="radio" name="bg_overlay" value="{{ $k }}" id="tint-{{ $k }}" {{ old('bg_overlay', $design['bg_overlay']) === $k ? 'checked' : '' }}>
                                        <label for="tint-{{ $k }}">{{ $label }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
(function () {
    var themes = @json($themes), fonts = @json(collect($fonts)->map(fn ($f) => $f[1]));
    var $lp = $('#live-preview .lp'), $form = $('#design-form');
    var fieldToKey = { bg_color: 'bg', text_color: 'text', button_color: 'button', button_text_color: 'button_text' };
    var varFor = { bg_color: '--lp-bg', text_color: '--lp-text', button_color: '--lp-btn', button_text_color: '--lp-btn-text' };

    function firstHex(v) { var m = /#[0-9a-fA-F]{6}/.exec(v); return m ? m[0] : '#FFFFFF'; }
    function luminance(hex) {
        var m = /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.exec(hex || ''); if (!m) return 0.5;
        var h = m[1].length === 3 ? m[1].replace(/./g, '$&$&') : m[1];
        var c = [0, 2, 4].map(function (i) { var v = parseInt(h.substr(i, 2), 16) / 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); });
        return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
    }
    function theme() { return themes[$form.find('[name=theme]:checked').val()]; }

    function apply() {
        var t = theme();
        $.each(fieldToKey, function (field, key) {
            var custom = $('#' + field).val();
            $lp[0].style.setProperty(varFor[field], custom || t[key]);
            $('#hex-' + field).text(custom || 'Theme');
            if (!custom) $('#picker-' + field).val(firstHex(t[key]));
        });
        // Accent colours: same rules as ProfileDesign on the server (light buttons use the button-text colour)
        var btn = $('#button_color').val() || firstHex(t.button), btnText = $('#button_text_color').val() || firstHex(t.button_text), txt = $('#text_color').val() || firstHex(t.text);
        var lightBtn = luminance(btn) > 0.8, accent = lightBtn ? btnText : btn;
        var mark = lightBtn && luminance(txt) > 0.6 && luminance(accent) < 0.2 ? txt : accent;
        $lp[0].style.setProperty('--lp-accent', accent);
        $lp[0].style.setProperty('--lp-mark', mark);
        $lp.toggleClass('lp-light-btn', lightBtn);
        $lp[0].style.setProperty('--lp-font', fonts[$form.find('[name=font]:checked').val()]);
        $lp.removeClass(function (i, c) { return (c.match(/lp-btn-\S+/g) || []).join(' '); })
           .addClass('lp-btn-' + $form.find('[name=button_style]:checked').val());
    }

    function dirty() { $('#unsaved-hint').prop('hidden', false); }

    // Choosing a theme resets fonts, button style and colours to that theme's defaults.
    $form.on('change', '[name=theme]', function () {
        var t = theme();
        $.each(fieldToKey, function (field) { $('#' + field).val(''); });
        $form.find('[name=font][value="' + t.font + '"]').prop('checked', true);
        $form.find('[name=button_style][value="' + t.button_style + '"]').prop('checked', true);
        apply(); dirty();
    });
    $form.on('change', '[name=font], [name=button_style]', function () { apply(); dirty(); });
    $form.on('input', '[data-color-field]', function () {
        $('#' + $(this).data('color-field')).val(this.value.toUpperCase());
        apply(); dirty();
    });
    $('#reset-colors').on('click', function () {
        $.each(fieldToKey, function (field) { $('#' + field).val(''); });
        apply(); dirty();
    });

    // ---- Background image: live preview before saving ----
    var savedPhoto = @json($profile->bgImageUrl());
    var $picker = $('#bg-picker'), $file = $('#bg_image');

    function setPhoto(url) {
        if (url) {
            $lp[0].style.setProperty('--lp-photo', 'url("' + url + '")');
            $('#bg-thumb').css('background-image', 'url("' + url + '")');
        } else {
            $lp[0].style.removeProperty('--lp-photo');
            $('#bg-thumb').css('background-image', '');
        }
        $lp.toggleClass('lp-has-photo', !!url);
        $picker.toggleClass('has-image', !!url);
        $('#bg-remove, #bg-overlay-row').prop('hidden', !url);
        $('#bg-upload-label').text(url ? 'Change' : 'Upload');
        applyTint();
    }
    function applyTint() {
        var t = $form.find('[name=bg_overlay]:checked').val() || 'theme';
        $lp.removeClass('lp-tint-theme lp-tint-light lp-tint-dark lp-tint-none').addClass('lp-tint-' + t);
    }
    function pick(file) {
        if (!file) return;
        if (!/^image\/(jpeg|png|webp)$/.test(file.type)) { lkToast('Please choose a JPG, PNG or WebP image.', 'error'); $file.val(''); return; }
        if (file.size > 5 * 1024 * 1024) { lkToast('Please choose an image smaller than 5 MB.', 'error'); $file.val(''); return; }
        $('#remove_bg_image').val('0');
        setPhoto(URL.createObjectURL(file));
        $('#bg-hint').text(file.name + ' · save to apply');
        dirty();
    }
    $file.on('change', function () { pick(this.files && this.files[0]); });
    $('#bg-remove').on('click', function () {
        $file.val(''); $('#remove_bg_image').val('1');
        setPhoto(null);
        $('#bg-hint').text('Background will be removed when you save.');
        dirty();
    });
    $form.on('change', '[name=bg_overlay]', function () { applyTint(); dirty(); });

    // Drag & drop an image onto the picker or the phone
    $('#bg-picker, .phone-preview').on('dragover dragenter', function (e) { e.preventDefault(); $picker.addClass('drag'); })
        .on('dragleave drop', function () { $picker.removeClass('drag'); })
        .on('drop', function (e) {
            e.preventDefault();
            var f = e.originalEvent.dataTransfer.files[0];
            if (!f) return;
            try { var dt = new DataTransfer(); dt.items.add(f); $file[0].files = dt.files; } catch (err) {}
            pick(f);
        });

    if (savedPhoto) applyTint();

    var saving = false;
    $form.on('submit', function () { saving = true; $(this).find('[type=submit]').prop('disabled', true); });

    // Unsaved changes: ask with our own dialog (not the browser's "Reload site?" popup)
    // when the user clicks a link or submits another form (e.g. Log out) on this page.
    function hasUnsaved() { return !saving && !$('#unsaved-hint').prop('hidden'); }
    function askBeforeLeaving(go, nextUrl) {
        lkConfirm({
            title: 'Save your design changes?',
            message: 'You changed your page design but haven’t saved it yet.',
            confirmText: 'Save changes',
            altText: 'Leave without saving',
            cancelText: 'Stay',
            tone: 'warning'
        }).then(function (choice) {
            if (choice === 'confirm') {
                if (nextUrl) $('<input type="hidden" name="next">').val(nextUrl).appendTo($form);
                $form[0].requestSubmit();
            } else if (choice === 'alt') {
                saving = true; // don't ask again
                go();
            }
        });
    }
    $(document).on('click', 'a[href]', function (e) {
        if (!hasUnsaved() || e.isDefaultPrevented() || e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) return;
        var a = this, href = a.getAttribute('href');
        if (!href || href.charAt(0) === '#' || a.target === '_blank' || a.hasAttribute('download') || /^(mailto|tel|javascript):/i.test(href)) return;
        e.preventDefault();
        var url = new URL(a.href, location.href);
        askBeforeLeaving(function () { location.href = a.href; }, url.origin === location.origin ? url.pathname + url.search : null);
    });
    $(document).on('submit', 'form', function (e) {
        if (this === $form[0] || !hasUnsaved() || this.dataset.lkLeaveOk === '1') return;
        e.preventDefault();
        var other = this;
        askBeforeLeaving(function () { other.dataset.lkLeaveOk = '1'; other.requestSubmit ? other.requestSubmit() : other.submit(); }, null);
    });

    apply();
})();

/* Font box: tall enough for "Clean & modern" + "Soft & friendly" only; other groups scroll into view. */
(function () {
    var picker = document.getElementById('font-picker'), more = document.getElementById('font-more');
    if (!picker) return;
    var grids = picker.querySelectorAll('.font-grid');

    function size() {
        if (grids.length < 3) { picker.style.maxHeight = 'none'; more.hidden = true; return; }
        var second = grids[1];
        // bottom of the 2nd group, measured inside the box (+ room for the selected outline)
        var h = second.getBoundingClientRect().bottom - picker.getBoundingClientRect().top + picker.scrollTop + 6;
        picker.style.maxHeight = Math.ceil(h) + 'px';
        edges();
    }
    function edges() {
        var atEnd = picker.scrollTop + picker.clientHeight >= picker.scrollHeight - 4;
        picker.classList.toggle('at-end', atEnd);
        picker.classList.toggle('at-start', picker.scrollTop < 4);
        more.classList.toggle('up', atEnd);
        more.querySelector('span').textContent = atEnd ? 'Back to top' : 'More fonts';
    }

    more.addEventListener('click', function () {
        var atEnd = picker.classList.contains('at-end');
        picker.scrollTo({ top: atEnd ? 0 : picker.scrollTop + picker.clientHeight - 40, behavior: 'smooth' });
    });
    picker.addEventListener('scroll', edges, { passive: true });
    window.addEventListener('resize', size);
    // Web fonts change the label sizes slightly once they load
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(size);
    size();
})();
</script>
@endpush
