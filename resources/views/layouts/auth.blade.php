<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="auth-page">
<head>
    @include('partials.head')
    <meta name="robots" content="noindex">
    @stack('styles')
</head>
<body>
    <div class="auth-split">
        {{-- Left: the form --}}
        <div class="auth-left">
            <header class="auth-brand">
                @include('partials.logo')
                <a href="{{ route('home') }}" class="auth-back"><i class="fa-solid fa-arrow-left me-1"></i>Back to site</a>
            </header>
            <main class="auth-center" id="main">
                <div class="auth-box">
                    @include('partials.alerts')
                    @yield('content')
                </div>
            </main>
        </div>

        {{-- Right: illustration (hidden on small screens) --}}
        <aside class="auth-visual" aria-hidden="true">
            {{-- A page can swap the default artwork for its own scene with @section('side_visual') --}}
            <div class="auth-visual-inner @hasSection('side_visual') is-scene @endif">
                @hasSection('side_visual')
                    @yield('side_visual')
                @else
                    <div class="aurora"><b></b><b></b><b></b></div>
                @endif
                <div class="auth-visual-text">
                    <span class="auth-visual-tag">@yield('side_tag', 'Linkora')</span>
                    <h2>@yield('side_title', 'One link for everything you share.')</h2>
                    <p>@yield('side_text', 'Put your socials, store, videos and payments on one beautiful page.')</p>
                </div>
                @unless (View::hasSection('side_visual'))
                    <img class="auth-visual-img" src="{{ asset('assets/img/auth-illustration.svg') }}" alt="" width="640" height="720">
                @endunless
            </div>
        </aside>
    </div>

    @include('partials.scripts')
    @stack('scripts')
</body>
</html>
