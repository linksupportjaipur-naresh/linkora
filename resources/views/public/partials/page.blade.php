{{-- The profile page body. Used on the public page and in the dashboard preview.
     Expects: $profile, $user, $links (collection), $design (ProfileDesign::resolve), $linkHref (bool) --}}
@php
    $avatar = $profile->avatarUrl();
    $verified = $user->isAdmin() || $user->isPro();
    $iconColors = config('linkora_design.icon_colors');
    $socialColors = config('linkora_design.social_colors');
    // Card look by position: filled, plain, filled (cool), plain, filled (warm), plain…
    $cardCycle = ['fill-1', 'plain', 'fill-2', 'plain', 'fill-3', 'plain'];
    $publicUrl = $user->publicLink();
@endphp
<div class="lp lp-btn-{{ $design['button_style'] }} {{ !empty($design['light_button']) ? 'lp-light-btn' : '' }} {{ !empty($design['bg_image']) ? 'lp-has-photo lp-tint-' . ($design['bg_overlay'] ?? 'theme') : '' }}" style="{{ \App\Support\ProfileDesign::cssVars($design) }}@if (! empty($design['bg_image'])) --lp-photo: url('{{ $design['bg_image'] }}');@endif" data-lp>
    <div class="lp-bg" aria-hidden="true">
        <span class="lp-blob lp-blob-a"></span><span class="lp-blob lp-blob-b"></span>
        <span class="lp-photo"></span>
        <span class="lp-tint"></span>
    </div>

    <div class="lp-inner">
        {{-- Header --}}
        <div class="lp-avatar-wrap">
            @if ($avatar)
                <img class="lp-avatar" src="{{ $avatar }}" alt="{{ $profile->name() }}" width="150" height="150" referrerpolicy="no-referrer" data-lp-avatar>
            @else
                <div class="lp-avatar" aria-hidden="true" data-lp-avatar>{{ $profile->initials() }}</div>
            @endif
            @if ($verified)
                <span class="lp-verified" title="Verified" aria-label="Verified"><i class="fa-solid fa-check"></i></span>
            @endif
        </div>

        <h1 class="lp-name" data-lp-name>{{ $profile->name() }}</h1>
        <p class="lp-headline">{{ $profile->headline ?: '@' . $user->username }}</p>
        <div class="lp-divider" aria-hidden="true"><span></span><b></b><span></span></div>

        @if ($profile->bio)
            <p class="lp-bio" data-lp-bio>{{ $profile->bio }}</p>
        @endif

        @if ($profile->location || $profile->website || $profile->contact_email || $profile->phone)
            <div class="lp-meta">
                {{-- Address on its own centred line (wraps neatly under itself) --}}
                @if ($profile->location)
                    <p class="lp-meta-loc"><i class="fa-solid fa-location-dot"></i>{{ $profile->location }}</p>
                @endif
                {{-- Website / Email / Call as small pills in one row --}}
                @if ($profile->website || $profile->contact_email || $profile->phone)
                    <div class="lp-meta-pills">
                        @if ($profile->website)<a class="lp-pill" href="{{ $profile->website }}" target="_blank" rel="noopener nofollow"><i class="fa-solid fa-globe"></i><span>{{ preg_replace('#^https?://(www\.)?#', '', rtrim($profile->website, '/')) }}</span></a>@endif
                        @if ($profile->contact_email)<a class="lp-pill" href="mailto:{{ $profile->contact_email }}"><i class="fa-regular fa-envelope"></i><span>Email</span></a>@endif
                        @if ($profile->phone)<a class="lp-pill" href="tel:{{ preg_replace('/[^0-9+]/', '', $profile->phone) }}"><i class="fa-solid fa-phone"></i><span>Call</span></a>@endif
                    </div>
                @endif
            </div>
        @endif

        @if (count($socials = $profile->socialLinks()))
            <nav class="lp-socials" aria-label="Social profiles">
                @foreach ($socials as $s)
                    <a href="{{ $s['url'] }}" target="_blank" rel="noopener nofollow" aria-label="{{ $s['label'] }}" title="{{ $s['label'] }}"
                       style="background: {{ $socialColors[$s['key']] ?? 'var(--lp-btn)' }}"><i class="{{ $s['icon'] }}"></i></a>
                @endforeach
            </nav>
        @endif

        {{-- Links --}}
        @if ($links->isNotEmpty())
            <ul class="lp-links">
                @foreach ($links as $link)
                    @php
                        $variant = $cardCycle[$loop->index % count($cardCycle)];
                        $iconKey = $link->iconKey();
                        $iconClass = $link->iconClass() ?: 'fa-solid fa-link';
                    @endphp
                    <li>
                        <a class="lp-link lp-card-{{ $variant }}" href="{{ ($linkHref ?? true) ? route('link.go', $link) : '#' }}" target="_blank" rel="noopener nofollow">
                            <span class="lp-ico" @if ($variant === 'plain') style="background: {{ $iconColors[$iconKey] ?? 'var(--lp-accent)' }}" @endif aria-hidden="true"><i class="{{ $iconClass }}"></i></span>
                            <span class="lp-txt">
                                <span class="lp-title">{{ $link->title }}</span>
                                @if ($link->subtitle)<span class="lp-sub">{{ $link->subtitle }}</span>@endif
                            </span>
                            <span class="lp-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="lp-empty">No links yet.</p>
        @endif

        {{-- Share card (hidden in the Appearance page preview via $showShare = false) --}}
        @if ($showShare ?? true)
        <section class="lp-sharecard" aria-labelledby="lp-share-title">
            <div class="lp-share-main">
                <div class="lp-share-head">
                    <button type="button" class="lp-share-ico" data-lp-share aria-label="More ways to share"><i class="fa-solid fa-share-nodes"></i></button>
                    <div>
                        <h2 id="lp-share-title">Share My Profile</h2>
                        <p>Share this link with your friends</p>
                    </div>
                </div>
                <div class="lp-copy-row">
                    <span class="lp-url"><i class="fa-solid fa-link"></i><span>{{ preg_replace('#^https?://#', '', $publicUrl) }}</span></span>
                    <button type="button" class="lp-copy" data-lp-copy="{{ $publicUrl }}"><i class="fa-regular fa-copy"></i><span>Copy</span></button>
                </div>
            </div>
            <div class="lp-share-qr">
                <div class="lp-qr" data-lp-qr="{{ $publicUrl }}" role="img" aria-label="QR code for {{ preg_replace('#^https?://#', '', $publicUrl) }}"></div>
                <span>Scan QR Code</span>
            </div>
        </section>
        @endif

        <div class="lp-foot">
            <div class="lp-thanks"><span></span><i class="fa-solid fa-heart"></i>Thanks for visiting!<span></span></div>
            <a class="lp-badge" href="{{ route('home') }}" target="_blank" rel="noopener"><img src="{{ asset('assets/img/logo-icon.png') }}" alt="">Made with {{ config('app.name') }}</a>
        </div>
    </div>
</div>

{{-- QR + copy behaviour (no jQuery needed; safe to include more than once) --}}
<script>
(function () {
    function drawQr() {
        if (!window.QRCode) return setTimeout(drawQr, 120);
        document.querySelectorAll('[data-lp-qr]:not([data-ready])').forEach(function (el) {
            el.setAttribute('data-ready', '1');
            new QRCode(el, { text: el.getAttribute('data-lp-qr'), width: 220, height: 220, colorDark: '#1B1233', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.M });
            el.removeAttribute('title');
        });
    }
    if (!window.QRCode && !document.querySelector('script[data-qrlib]')) {
        var s = document.createElement('script');
        s.src = 'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js';
        s.setAttribute('data-qrlib', '1');
        document.head.appendChild(s);
    }
    drawQr();

    document.querySelectorAll('[data-lp-copy]').forEach(function (btn) {
        if (btn.dataset.bound) return;
        btn.dataset.bound = '1';
        btn.addEventListener('click', function () {
            var url = btn.getAttribute('data-lp-copy'), label = btn.querySelector('span');
            var done = function () { label.textContent = 'Copied!'; btn.classList.add('copied'); setTimeout(function () { label.textContent = 'Copy'; btn.classList.remove('copied'); }, 1600); };
            if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(url).then(done); }
            else { var t = document.createElement('textarea'); t.value = url; document.body.appendChild(t); t.select(); document.execCommand('copy'); t.remove(); done(); }
        });
    });
})();
</script>
