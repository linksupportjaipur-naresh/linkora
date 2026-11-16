@extends('layouts.dashboard')

@section('title', 'My Profile')
@section('page_title', 'My Profile')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/profile-page.css') }}?v={{ filemtime(public_path('assets/css/profile-page.css')) }}">
<style>
    /* Field errors: one message slot per field (filled by the server or by the live checks below) */
    #profile-form .invalid-feedback[data-error-for] { font-size: .84rem; margin-top: .35rem; }
    #profile-form .invalid-feedback[data-error-for]:empty { display: none !important; }
    #profile-form .invalid-feedback[data-error-for]::before { content: "\f06a"; font: var(--fa-font-solid); margin-right: .35rem; }
    #profile-form .emoji-field:has(.is-invalid) { --emoji-ring: #dc3545; }
    #profile-form .char-count.over { color: #dc3545; font-weight: 600; }

</style>
@endpush

@section('content')
    <div class="page-head">
        <div>
            <h2>Your public profile</h2>
            <p>This is what people see at <a href="{{ $user->publicLink() }}" target="_blank" rel="noopener" class="fw-semibold">{{ $user->publicUrl() }}</a></p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="status-pill {{ $profile->is_published ? 'live' : '' }}"><span class="dot"></span>{{ $profile->is_published ? 'Published' : 'Not published' }}</span>
            @if ($profile->is_published)
                <form method="POST" action="{{ route('dashboard.profile.unpublish') }}">@csrf
                    <button type="submit" class="btn btn-sm btn-ghost"><i class="fa-solid fa-eye-slash me-1"></i>Unpublish</button>
                </form>
            @else
                <form method="POST" action="{{ route('dashboard.profile.publish') }}">@csrf
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fa-solid fa-globe me-1"></i>Publish</button>
                </form>
            @endif
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation me-2"></i>Please fix the highlighted fields below.</div>
    @endif

    <div class="row g-4">
        <div class="col-xl-7">
            <form method="POST" action="{{ route('dashboard.profile.update') }}" enctype="multipart/form-data" id="profile-form" novalidate>
                @csrf

                <div class="p-card mb-3">
                    <h3 class="section-title">Basics</h3>
                    <p class="section-sub">Your photo, name and a short line about you.</p>

                    <div class="avatar-edit mb-4">
                        @php($avatar = $profile->avatarUrl())
                        <div id="avatar-preview-wrap">
                            @if ($avatar)
                                <img src="{{ $avatar }}" class="ph" alt="Your profile photo" id="avatar-preview" referrerpolicy="no-referrer">
                            @else
                                <div class="ph" id="avatar-preview">{{ $profile->initials() }}</div>
                            @endif
                        </div>
                        <div>
                            <div class="d-flex flex-wrap gap-2">
                                <label class="btn btn-sm btn-outline-primary mb-0" for="avatar"><i class="fa-solid fa-camera me-1"></i>{{ $avatar ? 'Change photo' : 'Upload photo' }}</label>
                                {{-- Re-open the adjust dialog: for a photo picked just now, or for a saved uploaded photo (not Google ones, which live on another site) --}}
                                <button type="button" class="btn btn-sm btn-ghost" id="avatar-adjust" data-saved="{{ $profile->avatar_path ? $avatar : '' }}" @unless ($profile->avatar_path) hidden @endunless>
                                    <i class="fa-solid fa-crop-simple me-1"></i>Adjust photo
                                </button>
                            </div>
                            <input type="file" class="d-none" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp">
                            @if ($profile->avatar_path)
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="remove_avatar" value="1" id="remove_avatar">
                                    <label class="form-check-label small" for="remove_avatar">Remove photo</label>
                                </div>
                            @endif
                            <div class="form-text">JPG, PNG or WebP, up to 10 MB. You can zoom, move and rotate it to fit the circle.</div>
                            <div class="invalid-feedback d-block" data-error-for="avatar">@error('avatar'){{ $message }}@enderror</div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="display_name">Display name</label>
                            <input type="text" class="form-control @error('display_name') is-invalid @enderror" id="display_name" name="display_name" data-emoji value="{{ old('display_name', $profile->display_name ?: $user->name) }}" maxlength="60" required data-rule="name">
                            <div class="invalid-feedback d-block" data-error-for="display_name">@error('display_name'){{ $message }}@enderror</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="username">Username</label>
                            <div class="input-group has-validation">
                                <span class="input-group-text">{{ preg_replace('#/[^/]*$#', '', $user->publicUrl()) }}/</span>
                                <input type="text" class="form-control @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username', $user->username) }}"
                                       maxlength="30" required autocomplete="off" autocapitalize="none" spellcheck="false" data-username-input
                                       data-check-url="{{ route('dashboard.profile.username') }}" aria-describedby="username-status" data-rule="username">
                            </div>
                            <div class="invalid-feedback d-block" data-error-for="username">@error('username'){{ $message }}@enderror</div>
                            <div class="username-status" id="username-status" aria-live="polite"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="headline">Headline <span class="text-muted fw-normal small">(shown under your name)</span></label>
                            <input type="text" class="form-control @error('headline') is-invalid @enderror" id="headline" name="headline" data-emoji value="{{ old('headline', $profile->headline) }}" maxlength="80" placeholder="Developer | Creator | Blogger" data-rule="headline">
                            <div class="invalid-feedback d-block" data-error-for="headline">@error('headline'){{ $message }}@enderror</div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <label class="form-label" for="bio">Bio</label>
                                <span class="char-count" id="bio-count">0 / 300</span>
                            </div>
                            <textarea class="form-control @error('bio') is-invalid @enderror" id="bio" name="bio" data-emoji rows="3" maxlength="300" data-counter="#bio-count" data-rule="bio" placeholder="Designer & creator from Jaipur. I share tutorials every week.">{{ old('bio', $profile->bio) }}</textarea>
                            <div class="invalid-feedback d-block" data-error-for="bio">@error('bio'){{ $message }}@enderror</div>
                        </div>
                    </div>
                </div>

                <div class="p-card mb-3">
                    <h3 class="section-title">Contact details <span class="text-muted fw-normal small">(optional)</span></h3>
                    <p class="section-sub">Shown under your bio so people can reach you.</p>
                    <div class="row g-3">
                        @foreach ([
                            ['contact_email', 'Public email', 'email', 'hello@yourname.com', 'fa-regular fa-envelope'],
                            ['phone', 'Phone', 'tel', '+91 98765 43210', 'fa-solid fa-phone'],
                            ['website', 'Website', 'text', 'yourwebsite.com', 'fa-solid fa-globe'],
                            ['location', 'Location', 'text', 'Jaipur, India', 'fa-solid fa-location-dot'],
                        ] as [$field, $label, $type, $ph, $icon])
                            <div class="col-md-6">
                                <label class="form-label" for="{{ $field }}">{{ $label }}</label>
                                <div class="input-group has-validation">
                                    <span class="input-group-text"><i class="{{ $icon }}"></i></span>
                                    <input type="{{ $type }}" class="form-control @error($field) is-invalid @enderror" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $profile->$field) }}" placeholder="{{ $ph }}" maxlength="{{ $field === 'website' ? 255 : ($field === 'location' ? 80 : ($field === 'phone' ? 20 : 150)) }}"
                                           data-rule="{{ $field }}" @if ($type === 'tel') inputmode="tel" autocomplete="tel" @elseif ($type === 'email') autocomplete="email" @endif>
                                </div>
                                <div class="invalid-feedback d-block" data-error-for="{{ $field }}">@error($field){{ $message }}@enderror</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="p-card">
                    <h3 class="section-title">Social icons <span class="text-muted fw-normal small">(optional)</span></h3>
                    <p class="section-sub">Add your profiles and they appear as icons on your page.</p>
                    <div class="row g-3">
                        @foreach (config('linkora_design.socials') as $key => [$icon, $label, $ph])
                            <div class="col-md-6">
                                <label class="form-label visually-hidden" for="social-{{ $key }}">{{ $label }}</label>
                                <div class="input-group has-validation">
                                    <span class="input-group-text" style="width:46px;justify-content:center" title="{{ $label }}"><i class="{{ $icon }}"></i></span>
                                    <input type="text" class="form-control @error('socials.' . $key) is-invalid @enderror" id="social-{{ $key }}" name="socials[{{ $key }}]"
                                           value="{{ old('socials.' . $key, $profile->socials[$key] ?? '') }}" placeholder="{{ $ph }}" maxlength="255"
                                           data-rule="social" data-social="{{ $key }}" data-label="{{ $label }}" autocomplete="off" autocapitalize="none" spellcheck="false">
                                </div>
                                <div class="invalid-feedback d-block" data-error-for="socials.{{ $key }}">@error('socials.' . $key){{ $message }}@enderror</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="save-bar">
                    <div class="hint">
                        @if ($profile->is_published)
                            <span class="dot live"></span><span><strong>Live</strong> · updates when you save</span>
                        @else
                            <span class="dot"></span><span><strong>Draft</strong> · visitors can’t see it yet</span>
                        @endif
                    </div>
                    <div class="save-actions">
                        @if ($profile->is_published)
                            <button type="submit" name="action" value="save" class="btn btn-primary"><i class="fa-solid fa-check me-1"></i>Save changes</button>
                        @else
                            <button type="submit" name="action" value="save" class="btn btn-ghost">Save draft</button>
                            <button type="submit" name="action" value="publish" class="btn btn-primary"><i class="fa-solid fa-globe me-1"></i>Save &amp; publish</button>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <div class="col-xl-5 d-none d-xl-block">
            @include('dashboard.partials.preview')
        </div>
    </div>
    @include('dashboard.partials.avatar-crop-modal')
@endsection

@push('scripts')
<script>
/* Social box helpers — same rules as ProfileController::normalizeSocial() */
var LK_SOCIAL_DOMAINS = @json(config('linkora_design.social_domains'));
var LK_SOCIAL_HANDLES = @json(config('linkora_design.social_handle_urls'));
function lkNormalizeSocial(key, v) {
    v = (v || '').trim();
    if (!v) return '';
    var m = v.match(/^@([A-Za-z0-9_.\-]{1,60})$/);
    if (m && LK_SOCIAL_HANDLES[key]) return LK_SOCIAL_HANDLES[key] + m[1];
    if (key === 'whatsapp' && /^\+?[0-9\s\-()]{8,20}$/.test(v)) return 'https://wa.me/' + v.replace(/\D/g, '');
    return /^[a-z][a-z0-9+.-]*:/i.test(v) ? v : 'https://' + v.replace(/^\/+/, '');
}

/* ------------------------------------------------------------------
 * Live preview: mirror the form into the phone preview while typing.
 * The preview is our own public page in a same-origin iframe, so we
 * update its DOM directly (nothing is saved until you press Save).
 * ------------------------------------------------------------------ */
(function () {
    var SOCIALS = @json(collect(config('linkora_design.socials'))->map(fn ($s) => ['icon' => $s[0], 'label' => $s[1]]));
    var SOCIAL_COLORS = @json(config('linkora_design.social_colors'));
    var savedAvatar = @json($profile->avatarUrl());
    var avatarUrl = savedAvatar;           // what the preview should show (null = initials)
    var frame = document.getElementById('page-preview');
    var timer = null;

    function previewDoc() {
        try { var d = frame && frame.contentDocument; return d && d.querySelector('.lp') ? d : null; } catch (e) { return null; }
    }
    function val(id) { var el = document.getElementById(id); return el ? el.value.trim() : ''; }
    // Same rule as the server: add https:// if missing, and only ever link http(s) addresses.
    function withScheme(u) {
        if (!u) return '';
        var full = /^[a-z][a-z0-9+.-]*:/i.test(u) ? u : 'https://' + u.replace(/^\/+/, '');
        return /^https?:\/\//i.test(full) ? full : '';
    }
    function initials(name) {
        var parts = name.trim().split(/\s+/).filter(Boolean).slice(0, 2);
        return parts.map(function (p) { return Array.from(p)[0].toUpperCase(); }).join('') || 'L';
    }
    function el(d, tag, cls, text) { var e = d.createElement(tag); if (cls) e.className = cls; if (text != null) e.textContent = text; return e; }
    function icon(d, cls) { var i = d.createElement('i'); i.className = cls; return i; }

    function render() {
        var d = previewDoc();
        if (!d) return;
        var name = val('display_name') || @json($user->name), username = val('username') || @json($user->username);

        // Name + headline
        var n = d.querySelector('.lp-name'); if (n) n.textContent = name;
        var h = d.querySelector('.lp-headline'); if (h) h.textContent = val('headline') || '@' + username;

        // Avatar: chosen file / saved photo / initials
        var wrap = d.querySelector('.lp-avatar-wrap'), old = wrap && wrap.querySelector('.lp-avatar');
        if (wrap && old) {
            var next;
            if (avatarUrl) { next = d.createElement('img'); next.src = avatarUrl; next.alt = name; next.setAttribute('referrerpolicy', 'no-referrer'); }
            else { next = el(d, 'div', null, initials(name)); next.setAttribute('aria-hidden', 'true'); }
            next.className = 'lp-avatar';
            if (old.tagName !== next.tagName || old.getAttribute('src') !== next.getAttribute('src') || old.textContent !== next.textContent) old.replaceWith(next);
        }

        // Bio, contact details and social icons sit (in this order) right after the divider
        ['.lp-bio', '.lp-meta', '.lp-socials'].forEach(function (s) { var x = d.querySelector('.lp-inner > ' + s); if (x) x.remove(); });
        var anchor = d.querySelector('.lp-divider');
        if (!anchor) return;
        function add(node) { anchor.after(node); anchor = node; }

        var bio = val('bio');
        if (bio) add(el(d, 'p', 'lp-bio', bio));

        var loc = val('location'), web = withScheme(val('website')), mail = val('contact_email'), phone = val('phone');
        if (loc || web || mail || phone) {
            var meta = el(d, 'div', 'lp-meta');
            if (loc) { var p = el(d, 'p', 'lp-meta-loc'); p.append(icon(d, 'fa-solid fa-location-dot'), loc); meta.append(p); }
            if (web || mail || phone) {
                var pills = el(d, 'div', 'lp-meta-pills');
                function pill(href, ic, label, blank) {
                    var a = el(d, 'a', 'lp-pill'); a.href = href; if (blank) { a.target = '_blank'; a.rel = 'noopener nofollow'; }
                    a.append(icon(d, ic), el(d, 'span', null, label)); pills.append(a);
                }
                if (web) pill(web, 'fa-solid fa-globe', web.replace(/^https?:\/\/(www\.)?/i, '').replace(/\/$/, ''), true);
                if (mail) pill('mailto:' + mail, 'fa-regular fa-envelope', 'Email');
                if (phone) pill('tel:' + phone.replace(/[^0-9+]/g, ''), 'fa-solid fa-phone', 'Call');
                meta.append(pills);
            }
            add(meta);
        }

        var nav = el(d, 'nav', 'lp-socials'); nav.setAttribute('aria-label', 'Social profiles');
        Object.keys(SOCIALS).forEach(function (key) {
            var url = withScheme(lkNormalizeSocial(key, val('social-' + key)));
            if (!url) return;
            var a = el(d, 'a'); a.href = url; a.target = '_blank'; a.rel = 'noopener nofollow';
            a.title = SOCIALS[key].label; a.setAttribute('aria-label', SOCIALS[key].label);
            a.style.background = SOCIAL_COLORS[key] || 'var(--lp-btn)';
            a.append(icon(d, SOCIALS[key].icon)); nav.append(a);
        });
        if (nav.children.length) add(nav);
    }

    function schedule() { clearTimeout(timer); timer = setTimeout(render, 60); }

    $('#profile-form').on('input change', 'input, textarea', schedule);
    // Fired by the photo check below once the chosen file passed validation
    $(document).on('lk:avatar', function (e, url) { avatarUrl = url; schedule(); });
    $('#remove_avatar').on('change', function () { avatarUrl = this.checked ? null : savedAvatar; schedule(); });
    // The preview iframe (re)loads lazily and after saving links elsewhere: re-apply unsaved edits each time.
    if (frame) frame.addEventListener('load', render);
    render();
})();

    $('#bio').trigger('input');

/* ------------------------------------------------------------------
 * Validation — mirrors ProfileController::update() so mistakes show
 * while typing; the server still checks everything again on save.
 * ------------------------------------------------------------------ */
var lkProfileValidation = (function () {
    var form = document.getElementById('profile-form');
    var ORIGINAL_USERNAME = @json($user->username);
    var touched = {};
    var usernameServerError = '';            // last "taken/reserved" answer from the availability check

    function len(v) { return Array.from(v).length; }   // count emoji as 1 character, like the server
    function hostOf(u) { try { var x = new URL(u); return /^https?:$/.test(x.protocol) ? x : null; } catch (e) { return null; } }
    function keyOf(input) { return input.name.replace(/\[(\w[\w-]*)\]/g, '.$1'); }   // socials[instagram] -> socials.instagram

    var RULES = {
        name: function (v) {
            if (!v.trim()) return 'Please enter your name — it’s shown at the top of your page.';
            if (len(v.trim()) < 2) return 'Your name must be at least 2 characters.';
            if (len(v) > 60) return 'Your name can be at most 60 characters.';
            if (/[<>]/.test(v)) return 'Your name can’t contain < or >.';
            return '';
        },
        username: function (v) {
            v = v.trim().toLowerCase();
            if (!v) return 'Please choose a username.';
            if (v.length < 3) return 'Your username must be at least 3 characters.';
            if (v.length > 30) return 'Your username can be at most 30 characters.';
            if (!/^[a-z0-9](?:[a-z0-9_.]*[a-z0-9])?$/.test(v)) return 'Use lowercase letters, numbers, dots or underscores, starting and ending with a letter or number.';
            if (/[_.]{2}/.test(v)) return 'Dots and underscores can’t be next to each other.';
            return v === ORIGINAL_USERNAME ? '' : usernameServerError;
        },
        headline: function (v) {
            if (len(v) > 80) return 'Your headline can be at most 80 characters.';
            if (/[<>]/.test(v)) return 'Your headline can’t contain < or >.';
            return '';
        },
        bio: function (v) {
            if (len(v) > 300) return 'Your bio can be at most 300 characters.';
            if (/[<>]/.test(v)) return 'Your bio can’t contain < or >.';
            return '';
        },
        contact_email: function (v) {
            v = v.trim();
            if (!v) return '';
            if (v.length > 150) return 'This email address is too long.';
            return /^[A-Za-z0-9._%+\-']+@[A-Za-z0-9](?:[A-Za-z0-9\-]*[A-Za-z0-9])?(?:\.[A-Za-z0-9](?:[A-Za-z0-9\-]*[A-Za-z0-9])?)*\.[A-Za-z]{2,}$/.test(v)
                ? '' : 'Please enter a valid email address, like hello@yourname.com.';
        },
        phone: function (v) {
            v = v.trim();
            if (!v) return '';
            if (!/^\+?[0-9\s\-()]+$/.test(v)) return 'Use only numbers, spaces, brackets, dashes and an optional + at the start.';
            var d = v.replace(/\D/g, '').length;
            return d < 7 || d > 15 ? 'A phone number should have 7 to 15 digits.' : '';
        },
        website: function (v) {
            v = v.trim();
            if (!v) return '';
            var full = /^[a-z][a-z0-9+.-]*:/i.test(v) ? v : 'https://' + v.replace(/^\/+/, '');
            var u = hostOf(full);
            if (!u || !/^([a-z0-9\-]+\.)+[a-z]{2,}$/i.test(u.hostname)) return 'Please enter a valid website, like yourwebsite.com.';
            return full.length > 255 ? 'This link is too long (max 255 characters).' : '';
        },
        // Location is free text — no checks (the box itself stops at 80 characters)
        social: function (v, input) {
            var key = input.getAttribute('data-social'), label = input.getAttribute('data-label');
            var domains = LK_SOCIAL_DOMAINS[key] || [];
            var full = lkNormalizeSocial(key, v);
            if (!full) return '';
            var u = hostOf(full);
            if (!u || !u.hostname.includes('.')) return 'Please enter a full ' + label + ' link, for example ' + input.placeholder + '.';
            var host = u.hostname.toLowerCase();
            var ok = !domains.length || domains.some(function (d) { return host === d || host.endsWith('.' + d); });
            if (!ok) return 'This doesn’t look like ' + (/^[aeiou]/i.test(label) ? 'an ' : 'a ') + label + ' link. It should start with https://' + domains[0] + '/';
            if (u.pathname.replace(/\//g, '') === '' && !u.search) return 'Please add your ' + label + ' username or page to the link, not just the home page.';
            return full.length > 255 ? 'This link is too long (max 255 characters).' : '';
        }
    };

    function feedback(input) { return form.querySelector('[data-error-for="' + keyOf(input) + '"]'); }

    function show(input, msg) {
        input.classList.toggle('is-invalid', !!msg);
        input.setAttribute('aria-invalid', msg ? 'true' : 'false');
        var fb = feedback(input);
        if (fb) {
            fb.textContent = msg || '';
            if (!fb.id) fb.id = 'err-' + keyOf(input).replace(/\W/g, '-');
            var ids = (input.getAttribute('aria-describedby') || '').split(' ').filter(function (x) { return x && x !== fb.id; });
            if (msg) ids.push(fb.id);
            input.setAttribute('aria-describedby', ids.join(' '));
        }
    }

    function check(input) {
        var rule = RULES[input.getAttribute('data-rule')];
        if (!rule) return true;
        var msg = rule(input.value, input);
        show(input, msg);
        return !msg;
    }

    function fields() { return Array.prototype.slice.call(form.querySelectorAll('[data-rule]')); }

    // Check a field when you leave it; after that, re-check as you type so the message clears the moment it's fixed.
    form.addEventListener('focusout', function (e) {
        var t = e.target;
        if (!t.matches || !t.matches('[data-rule]')) return;
        touched[t.name] = true;
        if (check(t) && t.getAttribute('data-rule') === 'social' && t.value.trim()) {
            // Show the full link we'll save (e.g. "@name" -> https://instagram.com/name)
            var full = lkNormalizeSocial(t.getAttribute('data-social'), t.value);
            if (full !== t.value) { t.value = full; t.dispatchEvent(new Event('input', { bubbles: true })); }
        }
    });
    form.addEventListener('input', function (e) {
        var t = e.target;
        if (t.matches && t.matches('[data-rule]') && (touched[t.name] || t.classList.contains('is-invalid'))) check(t);
    });

    // Character counter turns red when over the limit
    $('#bio').on('input', function () { $('#bio-count').toggleClass('over', len(this.value) > 300); });

    return {
        check: check,
        show: show,
        setUsernameServerError: function (msg) { usernameServerError = msg || ''; check(document.getElementById('username')); },
        validateAll: function () {
            var bad = fields().filter(function (f) { touched[f.name] = true; return !check(f); });
            var avatarErr = feedback(document.getElementById('avatar'));
            return { ok: !bad.length && !(avatarErr && avatarErr.textContent.trim()), first: bad[0] };
        }
    };
})();

/* Profile photo: pick → "Adjust your photo" dialog → the adjusted square replaces the file (public/assets/js/avatar-cropper.js) */
(function () {
    var input = document.getElementById('avatar');
    LkAvatarCropper({
        input: input,
        adjustBtn: document.getElementById('avatar-adjust'),
        savedUrl: document.getElementById('avatar-adjust').getAttribute('data-saved') || null,
        onError: function (msg) { lkProfileValidation.show(input, msg); },
        onApplied: function (url) {
            $('#avatar-preview-wrap').html($('<img class="ph" alt="New profile photo">').attr('src', url));
            $('#remove_avatar').prop('checked', false);
            $(document).trigger('lk:avatar', [url]);
            lkToast('Photo adjusted. Press Save to update your profile.');
        }
    });
})();

    // Live username availability
    (function () {
        var $u = $('#username'), $s = $('#username-status'), t = null, xhr = null;
        $u.on('input', function () {
            clearTimeout(t);
            if (xhr) xhr.abort();
            lkProfileValidation.setUsernameServerError('');
            $s.removeClass('ok bad').text('');
            t = setTimeout(function () {
                var v = $u.val().trim().toLowerCase();
                // Don't ask the server about something the format check already rejects
                if (!v || !lkProfileValidation.check($u[0])) return;
                $s.html('<i class="fa-solid fa-spinner fa-spin me-1"></i>Checking…');
                xhr = $.get($u.data('check-url'), { username: v }).done(function (r) {
                    if (r.available) {
                        $s.addClass('ok').html('<i class="fa-solid fa-circle-check me-1"></i>').append(document.createTextNode(r.message));
                    } else {
                        $s.text('');
                        lkProfileValidation.setUsernameServerError(r.message);
                    }
                }).fail(function (x) { if (x.statusText !== 'abort') $s.text(''); });
            }, 350);
        });
    })();

    // Validate, then prevent double submits
    // (disabled buttons aren't submitted, so copy the clicked button's action into a hidden field)
    $('#profile-form').on('submit', function (e) {
        var result = lkProfileValidation.validateAll();
        if (!result.ok) {
            e.preventDefault();
            var target = result.first || document.getElementById('avatar');
            var focusEl = result.first || document.querySelector('label[for="avatar"]');
            target.closest('.p-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
            setTimeout(function () { focusEl && focusEl.focus({ preventScroll: true }); }, 350);
            var n = $(this).find('.is-invalid').length || 1;
            lkToast(n === 1 ? 'Please fix the highlighted field before saving.' : 'Please fix the ' + n + ' highlighted fields before saving.', 'error');
            return;
        }
        var submitter = e.originalEvent && e.originalEvent.submitter;
        $('<input type="hidden" name="action">').val(submitter && submitter.value ? submitter.value : 'save').appendTo(this);
        $(this).find('.save-bar button').prop('disabled', true);
    });
</script>
@endpush
