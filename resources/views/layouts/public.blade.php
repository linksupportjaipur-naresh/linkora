<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="no-js">
<head>
    @include('partials.head')
    <script>document.documentElement.classList.remove('no-js')</script>
    @stack('styles')
</head>
<body>
    <a class="visually-hidden-focusable position-absolute top-0 start-0 m-2 btn btn-primary btn-sm" style="z-index:2000" href="#main">Skip to content</a>

    {{-- Floating rounded header (white pill with a gap from the top; stays floating while scrolling) --}}
    <header class="lk-nav">
        <nav class="navbar navbar-expand-lg" aria-label="Main navigation">
            <div class="container lk-nav-pill">
                @include('partials.logo')
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="collapse navbar-collapse" id="mainNav">
                    <ul class="navbar-nav mx-auto gap-lg-1">
                        @foreach ([
                            'home' => 'Home',
                            'features' => 'Features',
                            'pricing' => 'Pricing',
                            'about' => 'About',
                            'contact' => 'Contact',
                        ] as $route => $label)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs($route) ? 'active' : '' }}" href="{{ route($route) }}" @if (request()->routeIs($route)) aria-current="page" @endif>{{ $label }}</a>
                            </li>
                        @endforeach
                    </ul>
                    <div class="nav-actions">
                        @auth
                            <a class="lk-nav-cta" href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('dashboard') }}">
                                <i class="fa-solid fa-gauge-high me-1"></i> {{ auth()->user()->isAdmin() ? 'Admin panel' : 'My dashboard' }}
                            </a>
                        @else
                            <a class="lk-nav-login" href="{{ route('login') }}">Log in</a>
                            <a class="lk-nav-cta" href="{{ route('register') }}">Sign up free</a>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <main id="main">
        @if (session('status') || session('error'))
            <div class="container pt-3">@include('partials.alerts')</div>
        @endif
        @yield('content')
    </main>

    <footer class="lk-footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    @include('partials.logo')
                    <p class="mt-3 mb-4" style="max-width:320px">One simple page for all your links. Built in Jaipur for creators, shops and small teams everywhere.</p>
                    <div class="social">
                        @foreach (config('linkora.socials') as $icon => $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="Linkora on {{ ucfirst(str_replace('-twitter', '', $icon)) }}"><i class="fa-brands fa-{{ $icon }}"></i></a>
                        @endforeach
                    </div>
                </div>
                <div class="col-6 col-md-3 col-lg-2 offset-lg-1">
                    <h5>Product</h5>
                    <ul>
                        <li><a href="{{ route('features') }}">Features</a></li>
                        <li><a href="{{ route('pricing') }}">Pricing</a></li>
                        <li><a href="{{ route('register') }}">Create a page</a></li>
                    </ul>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <h5>Company</h5>
                    <ul>
                        <li><a href="{{ route('about') }}">About us</a></li>
                        <li><a href="{{ route('contact') }}">Contact</a></li>
                        <li><a href="{{ route('pricing') }}#faq">FAQ</a></li>
                    </ul>
                </div>
                <div class="col-md-6 col-lg-3">
                    <h5>Get in touch</h5>
                    <ul>
                        <li><i class="fa-regular fa-envelope me-2"></i><a href="mailto:{{ config('linkora.support_email') }}">{{ config('linkora.support_email') }}</a></li>
                        <li><i class="fa-solid fa-location-dot me-2"></i>{{ config('linkora.address') }}</li>
                    </ul>
                </div>
            </div>
            <div class="bottom d-flex flex-column flex-md-row justify-content-between gap-2">
                <span>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</span>
                <span>Made with <i class="fa-solid fa-heart" style="color:var(--lk-brand-light)"></i> in India</span>
            </div>
        </div>
    </footer>

    @include('partials.scripts')
    @stack('scripts')
</body>
</html>
