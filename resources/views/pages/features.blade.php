@extends('layouts.public')

@section('title', 'Features')
@section('description', 'Explore Linkora features: unlimited links, beautiful themes, click analytics, QR codes and more.')

@push('styles')
    {{-- Fonts used by the theme previews --}}
    @if ($themePreviews->isNotEmpty())
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?{{ $themePreviews->pluck('font_param')->unique()->map(fn ($f) => 'family=' . $f)->implode('&') }}&display=swap">
    @endif
@endpush

@section('content')
    <section class="page-hero">
        <div class="aurora" aria-hidden="true"><b></b><b></b><b></b></div>
        <div class="container">
            <span class="lk-eyebrow"><i class="fa-solid fa-layer-group"></i> Features</span>
            <h1>Small page. Big possibilities.</h1>
            <p class="lead">Everything you need to bring your audience to one place and turn taps into followers, sales and bookings.</p>
        </div>
    </section>

    {{-- Feature deep-dives --}}
    <section class="lk-section-sm">
        <div class="container">
            <div class="row g-4 g-lg-5 feature-row">
                <div class="col-lg-6 rv">
                    <span class="lk-eyebrow"><i class="fa-solid fa-link"></i> Links</span>
                    <h2>Add, sort and switch links in seconds</h2>
                    <p class="text-muted mt-3">Paste any URL and Linkora fetches the title for you. Drag to reorder, and turn links on or off without deleting them.</p>
                    <ul>
                        <li><i class="fa-solid fa-circle-check"></i> Unlimited links on every plan</li>
                        <li><i class="fa-solid fa-circle-check"></i> Social icons for Instagram, YouTube, WhatsApp and more</li>
                        <li><i class="fa-solid fa-circle-check"></i> Highlight your most important link</li>
                    </ul>
                </div>
                <div class="col-lg-6 rv">
                    <div class="feature-visual">
                        <div class="mini-card">
                            <div class="mini-link"><i class="fa-solid fa-grip-vertical grip"></i><i class="fa-brands fa-youtube" style="color:#FF0000"></i> Latest video <span class="sw"></span></div>
                            <div class="mini-link"><i class="fa-solid fa-grip-vertical grip"></i><i class="fa-solid fa-bag-shopping text-brand"></i> My shop <span class="sw"></span></div>
                            <div class="mini-link"><i class="fa-solid fa-grip-vertical grip"></i><i class="fa-brands fa-whatsapp" style="color:#25D366"></i> Chat on WhatsApp <span class="sw"></span></div>
                            <div class="mini-link"><i class="fa-solid fa-grip-vertical grip"></i><i class="fa-regular fa-calendar text-muted"></i> Workshop (ended) <span class="sw off"></span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 g-lg-5 feature-row flex-lg-row-reverse">
                <div class="col-lg-6 rv">
                    <span class="lk-eyebrow"><i class="fa-solid fa-palette"></i> Themes</span>
                    <h2>A look that feels like you</h2>
                    <p class="text-muted mt-3">Start from a ready-made theme, then change colours, fonts, button styles and backgrounds to match your brand.</p>
                    <ul>
                        <li><i class="fa-solid fa-circle-check"></i> Ready-made themes for every style</li>
                        <li><i class="fa-solid fa-circle-check"></i> Custom colours, fonts and button shapes</li>
                        <li><i class="fa-solid fa-circle-check"></i> Profile photo, bio and cover image</li>
                    </ul>
                </div>
                <div class="col-lg-6 rv">
                    <div class="feature-visual feature-visual-themes">
                        {{-- Real themes from the catalogue (colours, font, button style); sample people with photos from Unsplash --}}
                        @php
                            $photo = fn ($id, $w, $h, $face = false) => 'https://images.unsplash.com/photo-' . $id . '?auto=format&q=70&w=' . $w . '&h=' . $h . ($face ? '&fit=facearea&facepad=3' : '&fit=crop');
                            $people = [
                                ['RK', 'Rohan Kapoor', 'Food blogger', '1793415604060-7cc69c412ccd', '1504674900247-0877df9cc836', [['Today’s recipe', '1512621776951-a57141f2eefd'], ['My cookbook', '1540189549336-e6e99c3679fe'], ['YouTube vlogs', '1504674900247-0877df9cc836']]],
                                ['MJ', 'Meera Joshi', 'Yoga coach', '1706943262459-3ef6ce03305c', '1544367567-0f2fcb009e0b', [['Morning flow', '1506126613408-eca07ce68773'], ['Join a class', '1544367567-0f2fcb009e0b'], ['WhatsApp me', '1706943262459-3ef6ce03305c']]],
                                ['PS', 'Priya Singh', 'Musician', '1618245472177-2a74ad3b994a', '1511379938547-c1f69419868d', [['New single', '1493225457124-a3eb161ffa5f'], ['Live shows', '1514320291840-2e0a9bf2a9ae'], ['Spotify', '1511379938547-c1f69419868d']]],
                            ];
                        @endphp
                        <div class="tp-stack" aria-label="Examples of Linkora themes">
                            @foreach ($themePreviews as $i => $t)
                                @php
                                    [$ini, $pname, $prole, $avatar, $cover, $plinks] = $people[$i % 3];
                                    $radius = ['pill' => '999px', 'rounded' => '12px', 'square' => '4px', 'outline' => '12px', 'shadow' => '8px'][$t['button_style']] ?? '12px';
                                    $outline = $t['button_style'] === 'outline';
                                    $shadow = $t['button_style'] === 'shadow';
                                @endphp
                                <figure class="tp-card tp-pos-{{ $i }}">
                                    <div class="tp-phone" style="background: {{ $t['bg'] }}; color: {{ $t['text'] }}; font-family: {{ $t['font_family'] }}">
                                        <div class="tp-cover" style="background: {{ $t['button'] }}">
                                            <img src="{{ $photo($cover, 360, 180) }}" alt="" loading="lazy" decoding="async" onerror="this.remove()">
                                        </div>
                                        <div class="tp-body">
                                            <div class="tp-avatar" style="background: {{ $t['button'] }}; color: {{ $t['button_text'] }}; --ring: {{ $t['bg'] }}">
                                                {{ $ini }}
                                                <img src="{{ $photo($avatar, 128, 128, true) }}" alt="" loading="lazy" decoding="async" onerror="this.remove()">
                                            </div>
                                            <div class="tp-name">{{ $pname }}</div>
                                            <div class="tp-sub">{{ $prole }}</div>
                                            @foreach ($plinks as [$label, $thumb])
                                                <div class="tp-btn" style="border-radius: {{ $radius }}; border: 2px solid {{ $shadow ? $t['text'] : $t['button'] }};
                                                    background: {{ $outline ? 'transparent' : $t['button'] }}; color: {{ $outline ? $t['button'] : $t['button_text'] }};
                                                    {{ $shadow ? 'box-shadow: 3px 3px 0 ' . $t['text'] . ';' : '' }}">
                                                    <img src="{{ $photo($thumb, 64, 64) }}" alt="" loading="lazy" decoding="async"
                                                         style="border-radius: {{ $t['button_style'] === 'pill' ? '50%' : '5px' }}" onerror="this.style.visibility='hidden'">
                                                    <span>{{ $label }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <figcaption>{{ $t['name'] }}@if (! empty($t['is_pro'])) <span class="tp-pro">Pro</span>@endif</figcaption>
                                </figure>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 g-lg-5 feature-row">
                <div class="col-lg-6 rv">
                    <span class="lk-eyebrow"><i class="fa-solid fa-chart-line"></i> Analytics</span>
                    <h2>Know what your audience loves</h2>
                    <p class="text-muted mt-3">See page views, link clicks and click-through rate over time, so you know what to share more of.</p>
                    <ul>
                        <li><i class="fa-solid fa-circle-check"></i> Views and clicks by day</li>
                        <li><i class="fa-solid fa-circle-check"></i> Top-performing links</li>
                        <li><i class="fa-solid fa-circle-check"></i> Visitors by device and source</li>
                    </ul>
                </div>
                <div class="col-lg-6 rv">
                    <div class="feature-visual">
                        <div class="mini-card">
                            <div class="d-flex justify-content-between mb-3">
                                <div><small class="text-muted d-block">Clicks this week</small><strong class="display-font fs-3">3,482</strong></div>
                                <span class="align-self-start badge rounded-pill" style="background:#DDF7EA;color:#157347">+18%</span>
                            </div>
                            <div class="d-flex align-items-end gap-2" style="height:120px" aria-hidden="true">
                                @foreach ([35, 52, 44, 70, 58, 88, 100] as $h)
                                    <div class="flex-fill rounded-top" style="height:{{ $h }}%;background:linear-gradient(var(--lk-brand),var(--lk-brand-light))"></div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Feature grid --}}
    <section class="lk-section">
        <div class="container">
            <div class="lk-head text-center rv">
                <h2>And there’s more</h2>
                <p>Small details that make a big difference.</p>
            </div>
            <div class="row g-3">
                @foreach ([
                    ['fa-solid fa-qrcode', 'QR code', 'Download a QR code for your page to print on cards, menus and posters.'],
                    ['fa-solid fa-mobile-screen', 'Mobile first', 'Your page loads fast and looks sharp on every phone.'],
                    ['fa-solid fa-at', 'Short username', 'A clean link like linkora.in/yourname that is easy to remember.'],
                    ['fa-solid fa-share-nodes', 'Social icons', 'Show your social profiles as neat icons on your page.'],
                    ['fa-solid fa-lock', 'Secure by default', 'Every page is served over HTTPS and your account is protected.'],
                    ['fa-solid fa-headset', 'Friendly support', 'Real people in Jaipur ready to help when you need it.'],
                ] as [$icon, $title, $text])
                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card rv">
                            <div class="ico"><i class="{{ $icon }}"></i></div>
                            <h3>{{ $title }}</h3>
                            <p>{{ $text }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    @include('partials.cta', ['title' => 'Try every feature, free.'])
@endsection
