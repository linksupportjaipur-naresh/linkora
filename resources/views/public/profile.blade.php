<!DOCTYPE html>
<html lang="en" @if ($isPreview) class="lp-embed" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    @php($title = $profile->name() . ' (@' . $user->username . ')')
    @php($desc = $profile->bio ? \Illuminate\Support\Str::limit($profile->bio, 155) : 'Find all of ' . $profile->name() . '’s links in one place.')
    <title>{{ $title }} · {{ config('app.name') }}</title>
    <meta name="description" content="{{ $desc }}">
    <meta property="og:type" content="profile">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $desc }}">
    <meta property="og:url" content="{{ $user->publicLink() }}">
    @if ($profile->avatarUrl())<meta property="og:image" content="{{ $profile->avatarUrl() }}">@endif
    <meta name="twitter:card" content="summary">
    @if (! $profile->is_published || $isPreview)<meta name="robots" content="noindex">@endif
    <link rel="canonical" href="{{ $user->publicLink() }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/img/favicon-32.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('assets/img/icon-192.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/img/apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="{{ $design['font_url'] }}">
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/profile-page.css') }}?v={{ filemtime(public_path('assets/css/profile-page.css')) }}">
    <style>
        html, body { margin: 0; }
        body { min-height: 100vh; display: flex; flex-direction: column; background: {{ \App\Support\ProfileDesign::color($design['bg']) ?? '#fff' }}; }
        body > .lp, body > .lp-stage { flex: 1; }
        .me-1 { margin-right: .25rem; }
        @media (prefers-reduced-motion: reduce) { .lp * { transition: none !important; } }
    </style>
</head>
<body>
    @if ($isOwner && ! $isPreview)
        <div class="lp-banner">
            @if ($profile->is_published)
                <i class="fa-solid fa-eye me-1"></i>This is your live page. Visits from you aren’t counted. <a href="{{ route('dashboard') }}">Back to dashboard</a>
            @else
                <i class="fa-solid fa-eye-slash me-1"></i>Only you can see this page. <a href="{{ route('dashboard.profile') }}">Publish it</a> to share it.
            @endif
        </div>
    @endif

    {{-- On tablets/desktops the page sits in a centred frame over a soft backdrop made from the theme
         (or a blurred copy of the background photo). Phones and the dashboard preview stay full-bleed. --}}
    <div class="lp-stage {{ $isPreview ? 'is-embed' : '' }}"
         style="{{ \App\Support\ProfileDesign::cssVars($design) }}@if (! empty($design['bg_image'])) --lp-photo: url('{{ $design['bg_image'] }}');@endif">
        <div class="lp-stage-bg {{ ! empty($design['bg_image']) ? 'has-photo' : '' }}" aria-hidden="true"></div>
        @include('public.partials.page', ['linkHref' => ! $isPreview, 'showShare' => ! $isPreview])
    </div>

    {{-- Share dialog (Linktree-style): link + copy, the profile's own platforms, then a scrollable row of share targets --}}
    <?php // share dialog data (plain PHP tags on purpose: Blade 8 mis-pairs block and inline php directives in one file)
        $shareLink = $user->publicLink();
        $u = urlencode($shareLink);
        $t = urlencode('Check out ' . $profile->name() . ' on ' . config('app.name'));
        $tl = urlencode('Check out ' . $profile->name() . ' on ' . config('app.name') . ': ' . $shareLink);
        $socialColors = config('linkora_design.social_colors');
        $targets = [
            ['qr', 'QR code', 'fa-solid fa-qrcode', '#EDEAF5', '#1B1233', null],
            ['whatsapp', 'WhatsApp', 'fa-brands fa-whatsapp', '#25D366', '#fff', 'https://wa.me/?text=' . $tl],
            ['facebook', 'Facebook', 'fa-brands fa-facebook-f', '#1877F2', '#fff', 'https://www.facebook.com/sharer/sharer.php?u=' . $u],
            ['x', 'X', 'fa-brands fa-x-twitter', '#000000', '#fff', 'https://x.com/intent/post?url=' . $u . '&text=' . $t],
            ['linkedin', 'LinkedIn', 'fa-brands fa-linkedin-in', '#0A66C2', '#fff', 'https://www.linkedin.com/sharing/share-offsite/?url=' . $u],
            ['telegram', 'Telegram', 'fa-brands fa-telegram', '#229ED9', '#fff', 'https://t.me/share/url?url=' . $u . '&text=' . $t],
            ['threads', 'Threads', 'fa-brands fa-threads', '#000000', '#fff', 'https://www.threads.net/intent/post?text=' . $tl],
            ['reddit', 'Reddit', 'fa-brands fa-reddit-alien', '#FF4500', '#fff', 'https://www.reddit.com/submit?url=' . $u . '&title=' . $t],
            ['pinterest', 'Pinterest', 'fa-brands fa-pinterest-p', '#E60023', '#fff', 'https://pinterest.com/pin/create/button/?url=' . $u . '&description=' . $t],
            ['sms', 'Messages', 'fa-solid fa-comment-sms', '#34C759', '#fff', 'sms:?&body=' . $tl],
            ['email', 'Email', 'fa-regular fa-envelope', '#EDEAF5', '#1B1233', 'mailto:?subject=' . $t . '&body=' . $u],
            ['more', 'More', 'fa-solid fa-ellipsis', '#EDEAF5', '#1B1233', null],
        ];
        $platforms = $profile->socialLinks();
    ?>
    <div class="ls-backdrop" id="share-sheet" hidden>
        <div class="ls-sheet" role="dialog" aria-modal="true" aria-labelledby="share-title" tabindex="-1">
            {{-- Main view --}}
            <div class="ls-view" data-view="main">
                <div class="ls-head">
                    <h2 id="share-title">Share {{ $isOwner ? 'your' : $profile->name() . '’s' }} {{ config('app.name') }}</h2>
                    <button type="button" class="ls-x" data-close-sheet aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="ls-link">
                    <img src="{{ asset('assets/img/logo-icon.png') }}" alt="" width="22" height="22">
                    <span class="ls-url" title="{{ $shareLink }}">{{ preg_replace('#^https?://#', '', $shareLink) }}</span>
                    <button type="button" class="ls-copy" id="share-copy" data-url="{{ $shareLink }}">Copy</button>
                </div>

                @if (count($platforms))
                    <div class="ls-section">
                        <h3>{{ $isOwner ? 'My platforms' : 'Find ' . $user->firstName() . ' on' }}</h3>
                        <div class="ls-row ls-platforms">
                            @foreach ($platforms as $s)
                                <a class="ls-item" href="{{ $s['url'] }}" target="_blank" rel="noopener nofollow">
                                    <span class="ls-ico" style="background: {{ $socialColors[$s['key']] ?? '#7955E8' }}; color: #fff"><i class="{{ $s['icon'] }}"></i></span>
                                    <span class="ls-label">{{ preg_replace('/\s*\(.*\)$/', '', $s['label']) }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="ls-targets-wrap">
                    <button type="button" class="ls-nav prev" aria-label="Scroll left" hidden><i class="fa-solid fa-chevron-left"></i></button>
                    <div class="ls-row ls-targets" id="share-targets">
                        @foreach ($targets as [$key, $label, $icon, $bg, $fg, $href])
                            @if ($href)
                                <a class="ls-item" href="{{ $href }}" @unless (\Illuminate\Support\Str::startsWith($href, ['sms:', 'mailto:'])) target="_blank" rel="noopener" @endunless data-target="{{ $key }}">
                                    <span class="ls-ico" style="background: {{ $bg }}; color: {{ $fg }}"><i class="{{ $icon }}"></i></span>
                                    <span class="ls-label">{{ $label }}</span>
                                </a>
                            @else
                                <button type="button" class="ls-item" data-target="{{ $key }}" @if ($key === 'more') hidden @endif>
                                    <span class="ls-ico" style="background: {{ $bg }}; color: {{ $fg }}"><i class="{{ $icon }}"></i></span>
                                    <span class="ls-label">{{ $label }}</span>
                                </button>
                            @endif
                        @endforeach
                    </div>
                    <button type="button" class="ls-nav next" aria-label="Scroll right"><i class="fa-solid fa-chevron-right"></i></button>
                </div>
            </div>

            {{-- QR view --}}
            <div class="ls-view" data-view="qr" hidden>
                <div class="ls-head">
                    <button type="button" class="ls-x" data-back aria-label="Back"><i class="fa-solid fa-arrow-left"></i></button>
                    <h2>QR code</h2>
                    <button type="button" class="ls-x" data-close-sheet aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="ls-qr-card">
                    <div class="ls-qr" id="share-qr" role="img" aria-label="QR code for {{ preg_replace('#^https?://#', '', $shareLink) }}"></div>
                    <div class="ls-qr-name"><img src="{{ asset('assets/img/logo-icon.png') }}" alt="" width="18" height="18">{{ preg_replace('#^https?://#', '', $shareLink) }}</div>
                </div>
                <p class="ls-qr-hint">Point a phone camera at the code to open this page.</p>
                <button type="button" class="ls-download" id="share-qr-dl"><i class="fa-solid fa-download"></i>Download PNG</button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var back = document.getElementById('share-sheet');
            var sheet = back.querySelector('.ls-sheet');
            var copyBtn = document.getElementById('share-copy');
            var url = copyBtn.getAttribute('data-url');
            var row = document.getElementById('share-targets');
            var prev = back.querySelector('.ls-nav.prev'), next = back.querySelector('.ls-nav.next');
            var lastFocus = null;

            function view(name) {
                back.querySelectorAll('.ls-view').forEach(function (v) { v.hidden = v.getAttribute('data-view') !== name; });
                if (name === 'qr') drawQr();
                sheet.focus();
            }
            function open() {
                lastFocus = document.activeElement;
                view('main');
                back.hidden = false;
                row.scrollLeft = 0;
                arrows();
                requestAnimationFrame(function () { back.classList.add('open'); arrows(); });
                document.documentElement.style.overflow = 'hidden';
            }
            function close() {
                back.classList.remove('open');
                document.documentElement.style.overflow = '';
                setTimeout(function () { back.hidden = true; if (lastFocus) lastFocus.focus(); }, 220);
            }

            document.querySelectorAll('[data-lp-share]').forEach(function (b) { b.addEventListener('click', open); });
            back.addEventListener('click', function (e) {
                if (e.target === back || e.target.closest('[data-close-sheet]')) close();
                if (e.target.closest('[data-back]')) view('main');
            });
            document.addEventListener('keydown', function (e) {
                if (back.hidden) return;
                if (e.key === 'Escape') close();
                if (e.key === 'Tab') {   // keep focus inside the dialog
                    var f = Array.prototype.filter.call(sheet.querySelectorAll('a[href], button:not([hidden])'), function (el) { return el.offsetParent !== null; });
                    if (!f.length) return;
                    if (e.shiftKey && document.activeElement === f[0]) { e.preventDefault(); f[f.length - 1].focus(); }
                    else if (!e.shiftKey && document.activeElement === f[f.length - 1]) { e.preventDefault(); f[0].focus(); }
                }
            });

            // Copy
            copyBtn.addEventListener('click', function () {
                var done = function () { copyBtn.textContent = 'Copied!'; copyBtn.classList.add('done'); setTimeout(function () { copyBtn.textContent = 'Copy'; copyBtn.classList.remove('done'); }, 1600); };
                if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(url).then(done);
                else { var t = document.createElement('textarea'); t.value = url; document.body.appendChild(t); t.select(); document.execCommand('copy'); t.remove(); done(); }
            });

            // Native share sheet as the last option, where the browser supports it
            var more = row.querySelector('[data-target="more"]');
            if (navigator.share) {
                more.hidden = false;
                more.addEventListener('click', function () { navigator.share({ title: document.title, url: url }).catch(function () {}); });
            }
            row.querySelector('[data-target="qr"]').addEventListener('click', function () { view('qr'); });

            // Scroll arrows for the share row
            function arrows() {
                prev.hidden = row.scrollLeft < 4;
                next.hidden = row.scrollLeft + row.clientWidth >= row.scrollWidth - 4;
            }
            row.addEventListener('scroll', arrows, { passive: true });
            prev.addEventListener('click', function () { row.scrollBy({ left: -row.clientWidth * .8, behavior: 'smooth' }); });
            next.addEventListener('click', function () { row.scrollBy({ left: row.clientWidth * .8, behavior: 'smooth' }); });
            window.addEventListener('resize', arrows);

            // QR (qrcodejs is loaded by the page partial)
            var qrBox = document.getElementById('share-qr');
            function drawQr() {
                if (qrBox.dataset.ready) return;
                if (!window.QRCode) return setTimeout(drawQr, 150);
                qrBox.dataset.ready = '1';
                new QRCode(qrBox, { text: url, width: 480, height: 480, colorDark: '#1B1233', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.M });
                qrBox.removeAttribute('title');
            }
            document.getElementById('share-qr-dl').addEventListener('click', function () {
                var c = qrBox.querySelector('canvas');
                if (!c) return;
                // white margin around the code so it scans well when printed
                var pad = 40, out = document.createElement('canvas');
                out.width = c.width + pad * 2; out.height = c.height + pad * 2;
                var g = out.getContext('2d'); g.fillStyle = '#fff'; g.fillRect(0, 0, out.width, out.height); g.drawImage(c, pad, pad);
                var a = document.createElement('a');
                a.download = @json($user->username . '-qr.png');
                a.href = out.toDataURL('image/png');
                a.click();
            });
        })();
    </script>
</body>
</html>
