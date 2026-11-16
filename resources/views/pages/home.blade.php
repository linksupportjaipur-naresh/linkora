@extends('layouts.public')

{{--
  Linkora homepage — premium SaaS landing page.
  Styles: assets/css/home.css · Motion: assets/js/home.js (GSAP + ScrollTrigger, homepage only).
  Everything is readable without JavaScript; animations only enhance it and respect prefers-reduced-motion.
  Live data: $facts (theme/font counts), $plans (pricing), $showcase (real themes).
--}}

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/home.css') }}?v={{ filemtime(public_path('assets/css/home.css')) }}">
@if ($showcase->isNotEmpty())
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?{{ $showcase->pluck('font_param')->unique()->map(fn ($f) => 'family=' . $f)->implode('&') }}&display=swap">
@endif
{{-- Before first paint: hide the hero intro until GSAP plays it (auto-undone after 2.5s if scripts fail),
     and show the short loader only on the first visit in this tab. --}}
<script>
  (function () {
    var html = document.documentElement;
    if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    html.classList.add('h-anim');
    setTimeout(function () { html.classList.remove('h-anim'); }, 2500);
    var seen = false; try { seen = sessionStorage.getItem('lk_home_seen') === '1'; } catch (e) {}
    if (!seen) html.classList.add('h-loader-on');
  })();
</script>
@endpush

@section('content')
<div class="h-page">

    {{-- Loader (first visit per session only) --}}
    <div class="h-loader" id="h-loader" aria-hidden="true">
        <div class="h-loader-mark">
            <img src="{{ asset('assets/img/logo-icon.png') }}" alt="" width="56" height="64">
            <span class="h-loader-bar"><i></i></span>
        </div>
    </div>

    {{-- ============================== HERO ============================== --}}
    <section class="h-hero">
        <div class="h-mesh" aria-hidden="true">
            <span class="h-orb h-orb-1" data-parallax="-0.25"></span>
            <span class="h-orb h-orb-2" data-parallax="0.18"></span>
            <span class="h-orb h-orb-3" data-parallax="-0.12"></span>
            <div class="h-grid"></div>
            <svg class="h-shape h-shape-1" data-parallax="0.3" viewBox="0 0 100 100"><defs><linearGradient id="hs1" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#A78BFA"/><stop offset="1" stop-color="#6541D9"/></linearGradient></defs><rect x="14" y="14" width="72" height="72" rx="22" fill="url(#hs1)"/></svg>
            <svg class="h-shape h-shape-2" data-parallax="-0.35" viewBox="0 0 100 100"><defs><linearGradient id="hs2" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#C4B5FD"/><stop offset="1" stop-color="#7955E8"/></linearGradient></defs><circle cx="50" cy="50" r="38" fill="none" stroke="url(#hs2)" stroke-width="14"/></svg>
            <svg class="h-shape h-shape-3" data-parallax="0.22" viewBox="0 0 100 100"><defs><linearGradient id="hs3" x1="0" y1="1" x2="1" y2="0"><stop offset="0" stop-color="#7955E8"/><stop offset="1" stop-color="#F0ABFC"/></linearGradient></defs><path d="M50 8 L92 84 L8 84 Z" fill="url(#hs3)" stroke-linejoin="round"/></svg>
        </div>

        <div class="container position-relative">
            <div class="row align-items-center g-5">
                <div class="col-lg-6 h-hero-copy">
                    <a class="h-pill-badge h-intro" href="{{ route('features') }}">
                        <span class="h-pill-new">New</span>
                        Themes, background photos &amp; analytics
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>

                    <h1 class="h-title" aria-label="One link for everything you share.">
                        <span class="h-line"><span class="h-intro">One link for</span></span>
                        <span class="h-line"><span class="h-intro">everything you</span></span>
                        <span class="h-line"><span class="h-intro"><span class="h-gradient-text h-typed" data-words='["share.","sell.","create.","teach.","love."]'>share.</span><span class="h-caret" aria-hidden="true"></span></span></span>
                    </h1>

                    <p class="h-lead h-intro">Put your socials, store, videos and payments on one beautiful page. Share it anywhere and see what people tap.</p>

                    <form class="h-claim h-intro" action="{{ route('register') }}" method="GET">
                        <label for="claim-username">{{ config('linkora.domain') }}/</label>
                        <input id="claim-username" name="username" placeholder="yourname" autocomplete="off" autocapitalize="none" spellcheck="false" maxlength="30" data-username-input aria-label="Choose your username">
                        <button class="h-btn h-btn-glow" type="submit"><span>Claim my link</span><i class="fa-solid fa-arrow-right"></i></button>
                    </form>

                    <ul class="h-trust h-intro">
                        <li><i class="fa-solid fa-circle-check"></i>Free forever</li>
                        <li><i class="fa-solid fa-circle-check"></i>No card needed</li>
                        <li><i class="fa-solid fa-circle-check"></i>{{ $facts['fonts'] }} fonts, incl. Hindi</li>
                    </ul>
                </div>

                <div class="col-lg-6">
                    <div class="h-stage h-intro-stage" id="h-stage" aria-hidden="true">
                        <div class="h-stage-glow"></div>

                        {{-- Floating social icons --}}
                        <span class="h-float-icon h-fi-1" style="--c:#E1306C" data-depth="1.4"><i class="fa-brands fa-instagram"></i></span>
                        <span class="h-float-icon h-fi-2" style="--c:#FF0000" data-depth="1.1"><i class="fa-brands fa-youtube"></i></span>
                        <span class="h-float-icon h-fi-3" style="--c:#25D366" data-depth="1.6"><i class="fa-brands fa-whatsapp"></i></span>
                        <span class="h-float-icon h-fi-4" style="--c:#1DB954" data-depth="0.9"><i class="fa-brands fa-spotify"></i></span>
                        <span class="h-float-icon h-fi-5" style="--c:#0A66C2" data-depth="1.3"><i class="fa-brands fa-linkedin-in"></i></span>
                        <span class="h-float-icon h-fi-6" style="--c:#111" data-depth="1.0"><i class="fa-brands fa-x-twitter"></i></span>

                        {{-- Phone with a sample Linkora page --}}
                        <div class="h-phone" data-depth="0.4">
                            <div class="h-phone-screen">
                                <div class="h-ph-blob"></div>
                                <div class="h-ph-avatar"><span>AS</span><i class="fa-solid fa-check"></i></div>
                                <div class="h-ph-name">Aanya Sharma</div>
                                <div class="h-ph-head">Designer | Creator | Jaipur</div>
                                <div class="h-ph-socials"><b style="background:#E1306C"><i class="fa-brands fa-instagram"></i></b><b style="background:#FF0000"><i class="fa-brands fa-youtube"></i></b><b style="background:#0A66C2"><i class="fa-brands fa-linkedin-in"></i></b></div>
                                <div class="h-ph-link fill"><i class="fa-solid fa-play"></i><span><strong>Watch my latest video</strong><small>New every Friday</small></span><em><i class="fa-solid fa-arrow-right"></i></em></div>
                                <div class="h-ph-link"><b style="background:#F59E0B"><i class="fa-solid fa-bag-shopping"></i></b><span><strong>Shop my presets</strong><small>Lightroom packs</small></span><em><i class="fa-solid fa-arrow-right"></i></em></div>
                                <div class="h-ph-link fill2"><i class="fa-regular fa-calendar"></i><span><strong>Book a 1:1 call</strong><small>30-min slots</small></span><em><i class="fa-solid fa-arrow-right"></i></em></div>
                                <div class="h-ph-link"><b style="background:#25D366"><i class="fa-brands fa-whatsapp"></i></b><span><strong>Chat on WhatsApp</strong><small>Usually replies fast</small></span><em><i class="fa-solid fa-arrow-right"></i></em></div>
                            </div>
                        </div>

                        {{-- Floating glass cards --}}
                        <div class="h-glass-card h-gc-1" data-depth="1.2">
                            <div class="h-gc-top"><span>Link clicks</span><b>+18%</b></div>
                            <strong>3,482</strong>
                            <svg viewBox="0 0 120 36" class="h-spark"><defs><linearGradient id="spk" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#7955E8" stop-opacity=".35"/><stop offset="1" stop-color="#7955E8" stop-opacity="0"/></linearGradient></defs><path d="M0 30 L15 26 L30 28 L45 18 L60 21 L75 12 L90 15 L105 6 L120 8 L120 36 L0 36 Z" fill="url(#spk)"/><path class="h-spark-line" d="M0 30 L15 26 L30 28 L45 18 L60 21 L75 12 L90 15 L105 6 L120 8" fill="none" stroke="#7955E8" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <div class="h-glass-card h-gc-2" data-depth="1.5">
                            <span class="h-gc-icon" style="background:#16A34A"><i class="fa-solid fa-indian-rupee-sign"></i></span>
                            <span><strong>UPI payment</strong><small>₹499 received</small></span>
                        </div>
                        <div class="h-glass-card h-gc-3" data-depth="0.8">
                            <span class="h-gc-icon" style="background:#7955E8"><i class="fa-solid fa-qrcode"></i></span>
                            <span><strong>QR scanned</strong><small>Café menu · just now</small></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================== PLATFORMS ============================== --}}
    @php($appRows = [
        [['fa-brands fa-instagram', 'Instagram', 'Reels & posts', '#E1306C'], ['fa-brands fa-youtube', 'YouTube', 'Videos & shorts', '#FF0000'], ['fa-brands fa-whatsapp', 'WhatsApp', 'Chat & orders', '#25D366'], ['fa-solid fa-indian-rupee-sign', 'UPI', 'Get paid', '#16A34A'], ['fa-brands fa-spotify', 'Spotify', 'Music & podcasts', '#1DB954'], ['fa-brands fa-linkedin', 'LinkedIn', 'Work profile', '#0A66C2']],
        [['fa-brands fa-shopify', 'Shopify', 'Your store', '#5E8E3E'], ['fa-brands fa-telegram', 'Telegram', 'Channels', '#229ED9'], ['fa-brands fa-x-twitter', 'X', 'Posts & threads', '#111111'], ['fa-brands fa-github', 'GitHub', 'Code & projects', '#24292F'], ['fa-brands fa-facebook', 'Facebook', 'Pages & groups', '#1877F2'], ['fa-brands fa-pinterest', 'Pinterest', 'Boards & pins', '#E60023']],
    ])
    <section class="h-apps" aria-label="Works with the apps your audience already uses">
        <div class="h-apps-bg" aria-hidden="true"></div>
        <div class="container position-relative">
            <div class="h-head text-center mx-auto mb-0" data-reveal>
                <span class="h-eyebrow"><i class="fa-solid fa-plug-circle-bolt"></i> Works everywhere</span>
                <h2>Works with the apps your audience <span class="h-gradient-text">already uses</span></h2>
                <p>Paste a link from any of them — Linkora picks the right icon and colour automatically.</p>
            </div>
        </div>
        <div class="h-apps-rows" aria-hidden="true">
            @foreach ($appRows as $ri => $row)
                <div class="h-marquee {{ $ri ? 'h-marquee-rev' : '' }}">
                    <div class="h-marquee-track">
                        @for ($r = 0; $r < 2; $r++)
                            @foreach ($row as [$ic, $name, $what, $col])
                                <span class="h-app-chip" style="--c: {{ $col }}">
                                    <span class="h-app-ico"><i class="{{ $ic }}"></i></span>
                                    <span class="h-app-txt"><b>{{ $name }}</b><small>{{ $what }}</small></span>
                                </span>
                            @endforeach
                        @endfor
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ============================== FEATURES (BENTO) ============================== --}}
    {{-- Visual layer only: all copy below is unchanged. Illustrations are decorative (aria-hidden) and only animate while the section is on screen. --}}
    <section class="h-section h-why" id="features" data-why>
        <div class="h-why-bg" aria-hidden="true">
            <div class="h-why-mesh"><i></i><i></i><i></i></div>
            <div class="h-why-grid"></div>
            <span class="h-why-shape h-why-s1" data-why-depth="-0.18"></span>
            <span class="h-why-shape h-why-s2" data-why-depth="0.22"><i class="fa-solid fa-plus"></i></span>
            <span class="h-why-shape h-why-s3" data-why-depth="0.3"></span>
            <span class="h-why-shape h-why-s4" data-why-depth="-0.12">
                <svg viewBox="0 0 120 40"><path d="M2 20 Q 17 2 32 20 T 62 20 T 92 20 T 118 20"/></svg>
            </span>
            <span class="h-why-shape h-why-s5" data-why-depth="0.14"></span>
            <span class="h-why-shape h-why-s6" data-why-depth="-0.26"></span>
        </div>

        <div class="container position-relative">
            <div class="h-head h-why-head" data-reveal>
                <span class="h-eyebrow h-why-eyebrow"><i class="fa-solid fa-wand-magic-sparkles"></i> Why Linkora</span>
                <h2>
                    <span class="h-why-line">Everything your <span class="h-why-mark">link<svg class="h-why-underline" viewBox="0 0 120 14" preserveAspectRatio="none" aria-hidden="true"><path d="M3 9 C 30 3, 70 3, 117 8"/></svg></span> <span class="h-why-grad">needs.</span></span>
                    <span class="h-muted">Nothing it doesn’t.</span>
                </h2>
                <p>Built for creators, shops and small teams who want a page that looks great on every phone.</p>
            </div>

            <div class="h-bento h-why-bento">
                {{-- 1 · Analytics --}}
                <article class="h-card h-card-lg h-spot h-why-card">
                    <div class="h-card-ico"><i class="fa-solid fa-chart-line"></i></div>
                    <h3>See which links get tapped</h3>
                    <p>Views, clicks, top links, traffic sources and devices — with 7 and 30-day trends.</p>
                    <div class="h-why-viz h-why-stats" aria-hidden="true">
                        <div class="h-bars">
                            @foreach ([38, 55, 46, 72, 63, 88, 100] as $i => $hgt)
                                <i style="--h: {{ $hgt }}%; --i: {{ $i }}"></i>
                            @endforeach
                        </div>
                        <svg class="h-why-trend" viewBox="0 0 236 160" preserveAspectRatio="none">
                            <defs><linearGradient id="hWhyTrend" x1="0" x2="1"><stop offset="0" stop-color="#A78BFA"/><stop offset="1" stop-color="#F0ABFC"/></linearGradient></defs>
                            <path d="M12 104 L 46 78 L 80 92 L 114 50 L 148 66 L 182 26 L 222 6"/>
                            <circle cx="222" cy="6" r="5"/>
                        </svg>
                        <span class="h-why-bubble b1"><i class="fa-regular fa-eye"></i></span>
                        <span class="h-why-bubble b2"><i class="fa-solid fa-arrow-pointer"></i></span>
                        <span class="h-why-tap"><i class="fa-solid fa-hand-pointer"></i><b></b></span>
                    </div>
                </article>

                {{-- 2 · Speed --}}
                <article class="h-card h-card-brand h-spot h-why-card">
                    <div class="h-card-ico light"><i class="fa-solid fa-bolt"></i></div>
                    <h3>Ready in 2 minutes</h3>
                    <p>Sign up with an email code or Google, add links, pick a look, publish.</p>
                    <div class="h-ring" aria-hidden="true"></div>
                    <div class="h-why-viz h-why-timer" aria-hidden="true">
                        <svg viewBox="0 0 64 64"><circle class="t-bg" cx="32" cy="32" r="27"/><circle class="t-fg" cx="32" cy="32" r="27"/></svg>
                        <i class="fa-solid fa-stopwatch"></i>
                    </div>
                    <div class="h-why-steps" aria-hidden="true">
                        @foreach (['fa-solid fa-at', 'fa-solid fa-link', 'fa-solid fa-palette', 'fa-solid fa-globe'] as $si => $sic)
                            <span style="--s: {{ $si }}"><i class="{{ $sic }}"></i><b class="fa-solid fa-check"></b></span>
                        @endforeach
                    </div>
                </article>

                {{-- 3 · Customise --}}
                <article class="h-card h-spot h-why-card h-why-custom" style="@foreach ($showcase->take(5)->values() as $ci => $t)--c{{ $ci }}: {{ $t['bg'] }}; --b{{ $ci }}: {{ $t['button'] }}; @endforeach">
                    <div class="h-card-ico"><i class="fa-solid fa-palette"></i></div>
                    <h3>Make it yours</h3>
                    <p>{{ $facts['themes'] }} themes, {{ $facts['fonts'] }} fonts, custom colours and button styles.</p>
                    <div class="h-swatches" aria-hidden="true">
                        @foreach ($showcase->take(5) as $t)
                            <i style="background: {{ $t['bg'] }}; --ring: {{ $t['button'] }}" data-bg="{{ $t['bg'] }}" data-btn="{{ $t['button'] }}"></i>
                        @endforeach
                    </div>
                    <div class="h-why-viz h-why-mini" aria-hidden="true">
                        <span class="m-av"></span><span class="m-line"></span><span class="m-btn"></span><span class="m-btn"></span><span class="m-btn"></span>
                    </div>
                </article>

                {{-- 4 · QR --}}
                <article class="h-card h-card-dark h-card-lg h-spot h-why-card">
                    <div class="h-card-ico light"><i class="fa-solid fa-qrcode"></i></div>
                    <h3>Share with a QR code</h3>
                    <p>Print it on a card, a menu or a shop window. Scans open your page instantly.</p>
                    <div class="h-why-scan" aria-hidden="true">
                        <span class="h-why-pulse"></span><span class="h-why-pulse p2"></span>
                        <div class="qr h-qr" id="qr" aria-hidden="true"></div>
                        <span class="h-why-corners"><i></i><i></i><i></i><i></i></span>
                        <span class="h-why-beam"></span>
                    </div>
                    <div class="h-why-viz h-why-places" aria-hidden="true">
                        <span><i class="fa-regular fa-id-card"></i></span>
                        <span><i class="fa-solid fa-utensils"></i></span>
                        <span><i class="fa-solid fa-store"></i></span>
                    </div>
                </article>

                {{-- 5 · Background photos --}}
                <article class="h-card h-spot h-why-card h-why-split">
                    <div class="h-card-ico"><i class="fa-regular fa-image"></i></div>
                    <h3>Background photos</h3>
                    <p>Upload your own photo and tint it with your theme’s colours.</p>
                    <div class="h-why-viz h-why-photo" aria-hidden="true">
                        <div class="ph-card ph-back"></div>
                        <div class="ph-card ph-front">
                            <svg viewBox="0 0 120 90" preserveAspectRatio="xMidYMid slice">
                                <defs><linearGradient id="hWhySky" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#FDBA74"/><stop offset="1" stop-color="#F9A8D4"/></linearGradient></defs>
                                <rect width="120" height="90" fill="url(#hWhySky)"/>
                                <circle cx="84" cy="30" r="12" fill="#FEF3C7"/>
                                <path d="M0 70 L 30 40 L 52 60 L 76 34 L 120 72 L 120 90 L 0 90 Z" fill="#7C3AED" opacity=".85"/>
                                <path d="M0 80 L 40 58 L 70 76 L 100 60 L 120 70 L 120 90 L 0 90 Z" fill="#4C1D95"/>
                            </svg>
                            <span class="ph-tint"></span>
                        </div>
                        <span class="ph-upload"><i class="fa-solid fa-arrow-up-from-bracket"></i></span>
                    </div>
                </article>

                {{-- 6 · Emoji & Hindi --}}
                <article class="h-card h-spot h-why-card h-why-split">
                    <div class="h-card-ico"><i class="fa-regular fa-face-smile"></i></div>
                    <h3>Emoji &amp; हिन्दी ready</h3>
                    <p>Add emoji anywhere and write in Hindi with fonts made for it.</p>
                    <div class="h-why-viz h-why-lang" aria-hidden="true">
                        <span class="lg-tile">अ</span>
                        <span class="lg-emo e1">😊</span>
                        <span class="lg-emo e2">🔥</span>
                        <span class="lg-emo e3">💜</span>
                        <span class="lg-emo e4">🎉</span>
                        <span class="lg-tile sm">क</span>
                    </div>
                </article>
            </div>

            <div class="text-center h-more" data-reveal>
                <a class="h-btn h-btn-ghost" href="{{ route('features') }}">See all features <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </div>
    </section>

    {{-- ============================== THEME GALLERY (real themes) ============================== --}}
    @if ($showcase->isNotEmpty())
    <section class="h-section h-themes">
        <div class="h-themes-bg" aria-hidden="true"></div>
        <div class="container position-relative">
            <div class="h-head text-center mx-auto" data-reveal>
                <span class="h-eyebrow"><i class="fa-solid fa-swatchbook"></i> Profile previews</span>
                <h2>Pick a look in one click</h2>
                <p>Every theme below is available on Linkora today. Switch any time — your links stay the same.</p>
            </div>

            {{-- Sample people for the previews (illustrative; photos from Unsplash, initials show if a photo can't load) --}}
            @php($face = function ($id) { return 'https://images.unsplash.com/photo-' . $id . '?auto=format&fit=facearea&facepad=3&q=70&w=128&h=128'; })
            @php($img = function ($id, $w, $h) { return 'https://images.unsplash.com/photo-' . $id . '?auto=format&fit=crop&q=70&w=' . $w . '&h=' . $h; })
            @php($people = [
                ['AS', 'Aanya Sharma', 'Designer', '1596196524143-569109d9a645', '1561070791-2526d30994b5', [['Portfolio', '1561070791-2526d30994b5'], ['Design templates', '1586717791821-3f44a563fa4c'], ['Book a call', '1517694712202-14dd9538aa97']]],
                ['RK', 'Rohan Kapoor', 'Food blogger', '1793415604060-7cc69c412ccd', '1504674900247-0877df9cc836', [['Today’s recipe', '1512621776951-a57141f2eefd'], ['My cookbook', '1540189549336-e6e99c3679fe'], ['YouTube vlogs', '1504674900247-0877df9cc836']]],
                ['MJ', 'Meera Joshi', 'Yoga coach', '1706943262459-3ef6ce03305c', '1544367567-0f2fcb009e0b', [['Morning flow', '1506126613408-eca07ce68773'], ['Join a class', '1544367567-0f2fcb009e0b'], ['WhatsApp me', '1706943262459-3ef6ce03305c']]],
                ['PS', 'Priya Singh', 'Musician', '1618245472177-2a74ad3b994a', '1511379938547-c1f69419868d', [['New single', '1493225457124-a3eb161ffa5f'], ['Live shows', '1514320291840-2e0a9bf2a9ae'], ['Spotify', '1511379938547-c1f69419868d']]],
                ['NV', 'Neel Verma', 'Developer', '1778692258270-bc0e80e975c0', '1555066931-4365d14bab8c', [['Open-source work', '1555066931-4365d14bab8c'], ['Tech blog', '1498050108023-c5249f4df085'], ['Hire me', '1517694712202-14dd9538aa97']]],
            ])
            <div class="h-theme-row" data-stagger>
                @foreach ($showcase as $i => $t)
                    @php($radius = ['pill' => '999px', 'rounded' => '12px', 'square' => '4px', 'outline' => '12px', 'shadow' => '8px'][$t['button_style']] ?? '12px')
                    @php([$ini, $pname, $prole, $avatar, $cover, $plinks] = $people[$i % 5])
                    <figure class="h-theme-card" style="--d: {{ $i }}" data-parallax-card="{{ $i % 2 ? '-' : '' }}0.06">
                        <div class="h-theme-phone" style="background: {{ $t['bg'] }}; color: {{ $t['text'] }}; font-family: {{ $t['font_family'] }}">
                            <div class="h-tp-cover" style="background: {{ $t['button'] }}">
                                <img src="{{ $img($cover, 400, 200) }}" alt="" loading="lazy" decoding="async" onerror="this.remove()">
                            </div>
                            <div class="h-tp-body">
                                <div class="h-tp-avatar" style="background: {{ $t['button'] }}; color: {{ $t['button_text'] }}; --ring: {{ $t['bg'] }}">
                                    {{ $ini }}
                                    <img src="{{ $face($avatar) }}" alt="" loading="lazy" decoding="async" onerror="this.remove()">
                                </div>
                                <div class="h-tp-name">{{ $pname }}</div>
                                <div class="h-tp-sub">{{ $prole }}</div>
                                @foreach ($plinks as [$label, $thumb])
                                    <div class="h-tp-btn" style="border-radius: {{ $radius }}; border: 2px solid {{ $t['button_style'] === 'shadow' ? $t['text'] : $t['button'] }};
                                        background: {{ $t['button_style'] === 'outline' ? 'transparent' : $t['button'] }};
                                        color: {{ $t['button_style'] === 'outline' ? $t['button'] : $t['button_text'] }};
                                        {{ $t['button_style'] === 'shadow' ? 'box-shadow: 3px 3px 0 ' . $t['text'] . ';' : '' }}">
                                        <img class="h-tp-thumb" src="{{ $img($thumb, 64, 64) }}" alt="" loading="lazy" decoding="async"
                                             style="border-radius: {{ $t['button_style'] === 'pill' ? '50%' : '5px' }}" onerror="this.style.visibility='hidden'">
                                        <span>{{ $label }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <figcaption>{{ $t['name'] }}@if (! empty($t['is_pro'])) <span class="h-pro">Pro</span>@endif</figcaption>
                    </figure>
                @endforeach
            </div>

            <div class="text-center mt-5" data-reveal>
                <a class="h-btn h-btn-glow" href="{{ route('register') }}"><span>Create my page</span><i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </div>
    </section>
    @endif

    {{-- ============================== FACTS ============================== --}}
    <section class="h-section h-facts-wrap">
        <div class="container">
            <div class="h-facts" data-stagger>
                <div class="h-fact"><strong>₹0</strong><span>To start — free forever plan</span></div>
                <div class="h-fact"><strong data-count="2" data-suffix=" min">2 min</strong><span>To set up your page</span></div>
                <div class="h-fact"><strong data-count="{{ $facts['themes'] }}">{{ $facts['themes'] }}</strong><span>Ready-made themes</span></div>
                <div class="h-fact"><strong data-count="{{ $facts['fonts'] }}">{{ $facts['fonts'] }}</strong><span>Fonts, incl. Hindi</span></div>
            </div>
        </div>
    </section>

    {{-- ============================== HOW IT WORKS ============================== --}}
    <section class="h-section pt-0" id="how">
        <div class="container">
            <div class="h-head" data-reveal>
                <span class="h-eyebrow"><i class="fa-solid fa-route"></i> How it works</span>
                <h2>Three steps to your page</h2>
            </div>
            <div class="h-steps">
                <div class="h-steps-line" aria-hidden="true"><span></span></div>
                @foreach ([
                    ['fa-solid fa-at', 'Claim your name', 'Pick a short link that is easy to say out loud.'],
                    ['fa-solid fa-link', 'Add your links', 'Paste a URL, drag to reorder, done.'],
                    ['fa-solid fa-share-nodes', 'Share it everywhere', 'Bio, stories, WhatsApp status or a printed QR.'],
                ] as $i => [$icon, $title, $text])
                    <div class="h-step" data-reveal>
                        <div class="h-step-num"><i class="{{ $icon }}"></i><b>{{ $i + 1 }}</b></div>
                        <h3>{{ $title }}</h3>
                        <p>{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============================== PRICING (live plans) ============================== --}}
    @if (count($plans))
    <section class="h-section h-pricing">
        <div class="container">
            <div class="h-head text-center mx-auto" data-reveal>
                <span class="h-eyebrow"><i class="fa-solid fa-tag"></i> Pricing</span>
                <h2>Start free. Upgrade when you grow.</h2>
                <p>Simple plans in Indian Rupees. Cancel any time.</p>
            </div>
            <div class="h-plans" data-stagger>
                @foreach ($plans as $plan)
                    <div class="h-plan {{ $plan['featured'] ? 'is-featured' : '' }}">
                        @if ($plan['featured'])<span class="h-plan-badge"><i class="fa-solid fa-star"></i> Most popular</span>@endif
                        <h3>{{ $plan['name'] }}</h3>
                        <p class="h-plan-tag">{{ $plan['tagline'] }}</p>
                        <div class="h-plan-price">₹{{ number_format($plan['monthly']) }}<small>/month</small></div>
                        <ul>
                            @foreach (collect($plan['features'])->filter(fn ($f) => $f[1])->take(4) as [$feature])
                                <li><i class="fa-solid fa-check"></i>{{ $feature }}</li>
                            @endforeach
                        </ul>
                        <a class="h-btn {{ $plan['featured'] ? 'h-btn-glow' : 'h-btn-ghost' }} w-100" href="{{ route('register') }}"><span>{{ $plan['cta'] }}</span></a>
                    </div>
                @endforeach
            </div>
            <p class="text-center mt-4" data-reveal><a class="h-link" href="{{ route('pricing') }}">Compare all plans <i class="fa-solid fa-arrow-right"></i></a></p>
        </div>
    </section>
    @endif

    {{-- ============================== FAQ ============================== --}}
    <section class="h-section pt-0">
        <div class="container h-faq-wrap">
            <div class="h-head text-center mx-auto" data-reveal>
                <span class="h-eyebrow"><i class="fa-regular fa-circle-question"></i> FAQ</span>
                <h2>Questions? Answers.</h2>
            </div>
            <div class="accordion h-faq" id="homeFaq" data-stagger>
                @foreach ([
                    ['Is the Free plan really free?', 'Yes. The Free plan costs nothing, has no time limit and doesn’t need a card. Upgrade only if you want more themes, customisation or analytics.'],
                    ['Do I need a password?', 'No. Sign up with just your email — we send a 6-digit code — or continue with Google. You can add a password later in Settings if you like.'],
                    ['Can I use my own photo and colours?', 'Yes. Pick a theme, then change colours, fonts and button styles, or upload a background photo. The mobile preview updates as you go.'],
                    ['Can I see who taps my links?', 'You get views, clicks, click-through rate, top links, traffic sources and devices for the last 7 or 30 days. We never store visitors’ IP addresses.'],
                ] as $i => [$q, $a])
                    <div class="accordion-item">
                        <h3 class="accordion-header" id="hf-h{{ $i }}">
                            <button class="accordion-button {{ $i ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#hf-c{{ $i }}" aria-expanded="{{ $i ? 'false' : 'true' }}" aria-controls="hf-c{{ $i }}">{{ $q }}</button>
                        </h3>
                        <div id="hf-c{{ $i }}" class="accordion-collapse collapse {{ $i ? '' : 'show' }}" aria-labelledby="hf-h{{ $i }}" data-bs-parent="#homeFaq">
                            <div class="accordion-body">{{ $a }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="text-center mt-4 text-muted" data-reveal>More questions? <a class="h-link" href="{{ route('contact') }}">Talk to us</a></p>
        </div>
    </section>

    {{-- ============================== CTA ============================== --}}
    <section class="h-section pt-0">
        <div class="container">
            <div class="h-cta" data-reveal>
                <div class="h-cta-mesh" aria-hidden="true"><span></span><span></span><span></span></div>
                <div class="h-cta-content">
                    <h2>Your link is waiting.</h2>
                    <p>Be one of the first creators on Linkora. It’s free to start.</p>
                    <div class="h-cta-actions">
                        <a class="h-btn h-btn-white" href="{{ route('register') }}"><span>Create my page</span><i class="fa-solid fa-arrow-right"></i></a>
                        <a class="h-btn h-btn-outline-light" href="{{ route('pricing') }}"><span>See pricing</span></a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" defer></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js" defer></script>
<script src="{{ asset('assets/js/home.js') }}?v={{ filemtime(public_path('assets/js/home.js')) }}" defer></script>
@endpush
