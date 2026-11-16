@extends('layouts.public')

@section('title', 'About us')
@section('description', 'Linkora is built in Jaipur to help creators and small businesses share everything from one simple link.')

@section('content')
    <section class="page-hero">
        <div class="aurora" aria-hidden="true"><b></b><b></b><b></b></div>
        <div class="container">
            <span class="lk-eyebrow"><i class="fa-solid fa-heart"></i> About us</span>
            <h1>We help people share what they make.</h1>
            <p class="lead">Linkora started with a simple idea: everyone deserves a beautiful home on the internet, without needing to build a website.</p>
        </div>
    </section>

    <section class="lk-section-sm">
        <div class="container">
            <div class="row g-4 g-lg-5 align-items-center">
                <div class="col-lg-6 rv">
                    <h2 class="mb-4" style="font-size:clamp(1.8rem,3.6vw,2.8rem)">Our story</h2>
                    <p class="text-muted">Social apps give you a single link in your bio, but you have a shop, a channel, a booking page and a WhatsApp number. We watched friends in Jaipur juggle all of these and lose customers along the way.</p>
                    <p class="text-muted">So we built Linkora: one page that brings it all together, looks great on every phone, and shows you what your audience actually taps.</p>
                    <p class="text-muted mb-0">Linkora has just launched. We’re starting in Jaipur and building it together with our first creators, cafés, coaches and small brands.</p>
                </div>
                <div class="col-lg-6 rv">
                    <div class="story-visual">
                        <blockquote>“Make sharing simple, so people can focus on the work they love.”</blockquote>
                        <p class="mt-3 mb-0 opacity-75">Our mission</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="lk-section">
        <div class="container">
            <div class="row g-4 text-center mb-5 pb-3 rv">
                <div class="col-6 col-md-3 stat"><h3>₹0</h3><p>To start — free forever plan</p></div>
                <div class="col-6 col-md-3 stat"><h3 data-n="{{ $facts['themes'] }}">{{ $facts['themes'] }}</h3><p>Ready-made themes</p></div>
                <div class="col-6 col-md-3 stat"><h3 data-n="{{ $facts['fonts'] }}">{{ $facts['fonts'] }}</h3><p>Fonts, incl. Hindi</p></div>
                <div class="col-6 col-md-3 stat"><h3 data-n="1" data-s=" day">1 day</h3><p>Support reply time</p></div>
            </div>

            <div class="lk-head text-center rv"><h2>What we believe</h2></div>
            <div class="row g-3">
                @foreach ([
                    ['fa-solid fa-feather', 'Keep it simple', 'If it takes more than a few taps, we haven’t finished designing it.'],
                    ['fa-solid fa-mobile-screen-button', 'Phone first', 'Most of your visitors are on a phone, so that’s where we start.'],
                    ['fa-solid fa-shield-heart', 'Respect privacy', 'We collect only what we need and never sell your data.'],
                    ['fa-solid fa-hand-holding-heart', 'Fair pricing', 'A generous free plan, and paid plans priced for India.'],
                ] as [$icon, $title, $text])
                    <div class="col-md-6 col-lg-3">
                        <div class="value-card rv"><i class="{{ $icon }}"></i><h3>{{ $title }}</h3><p>{{ $text }}</p></div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="lk-section-sm pt-0">
        <div class="container">
            <div class="row g-4 g-lg-5">
                <div class="col-lg-5 rv">
                    <h2 style="font-size:clamp(1.8rem,3.6vw,2.8rem)">Our journey so far</h2>
                    <p class="text-muted mt-3">A small team with big plans. We’ve just started — and we’d love you to be part of it from day one.</p>
                </div>
                <div class="col-lg-7 rv">
                    <div class="timeline">
                        <div class="item"><h4>The idea</h4><p>Frustrated by “link in bio” limits, we set out to build one simple page for everything a creator shares.</p></div>
                        <div class="item"><h4>Built in Jaipur</h4><p>Themes, background photos, QR codes and click analytics — all included from the very first version.</p></div>
                        <div class="item"><h4>Launch — now</h4><p>Linkora is open for everyone. Your feedback shapes what we build next.</p></div>
                        <div class="item"><h4>What’s next</h4><p>Payments, bookings and team accounts, so your link can do even more.</p></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('partials.cta', ['title' => 'Join the Linkora community.'])
@endsection
