{{-- Shared shell for the user dashboard and the admin panel. Child layouts fill the "sidebar" section. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
    <meta name="robots" content="noindex">
    <link rel="stylesheet" href="{{ asset('assets/css/panel.css') }}?v={{ filemtime(public_path('assets/css/panel.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/dashboard-modules.css') }}?v={{ filemtime(public_path('assets/css/dashboard-modules.css')) }}">
    @stack('styles')
</head>
<body class="panel @yield('body_class')">
    @php($user = auth()->user())

    <aside class="sidebar @yield('sidebar_class')" id="sidebar" aria-label="Sidebar">
        <div class="brand">
            @include('partials.logo', ['href' => $homeRoute ?? route('home')])
            @hasSection('panel_tag')<span class="tag">@yield('panel_tag')</span>@endif
        </div>
        <nav>
            @yield('sidebar')
        </nav>
        <div class="s-foot">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="s-link w-100 border-0 bg-transparent"><i class="fa-solid fa-arrow-right-from-bracket"></i> Log out</button>
            </form>
        </div>
    </aside>
    <div class="sb-backdrop" data-sb-toggle></div>

    <div class="main">
        <header class="topbar">
            <button class="sb-toggle" type="button" data-sb-toggle aria-controls="sidebar" aria-label="Open menu"><i class="fa-solid fa-bars"></i></button>
            <h1>@yield('page_title', 'Dashboard')</h1>
            <div class="ms-auto d-flex align-items-center gap-2">
                @yield('topbar_actions')
                <div class="dropdown">
                    <button class="user-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account menu">
                        @php($topPhoto = $user->profile ? $user->profile->avatarUrl() : ($user->avatar ?: null))
                        @if ($topPhoto)
                            <img class="u-avatar u-avatar-img" src="{{ $topPhoto }}" alt="" referrerpolicy="no-referrer">
                        @else
                            <span class="u-avatar">{{ $user->initials() }}</span>
                        @endif
                        <span class="d-none d-md-block text-start lh-sm">
                            <span class="d-block fw-semibold">{{ $user->name }}</span>
                            <small class="text-muted">{{ $user->isAdmin() ? 'Administrator' : '@' . $user->username }}</small>
                        </span>
                        <i class="fa-solid fa-chevron-down small text-muted d-none d-md-inline"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="px-3 py-2 small text-muted text-truncate" style="max-width:240px">{{ $user->email }}</li>
                        <li><hr class="dropdown-divider"></li>
                        @if ($user->isAdmin())
                            <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}"><i class="fa-solid fa-shield-halved me-2"></i>Admin panel</a></li>
                        @endif
                        <li><a class="dropdown-item" href="{{ route('dashboard') }}"><i class="fa-solid fa-gauge-high me-2"></i>My dashboard</a></li>
                        <li><a class="dropdown-item" href="{{ route('home') }}"><i class="fa-solid fa-globe me-2"></i>View website</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Log out</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="content" id="main">
            @include('partials.alerts')
            @yield('content')
        </main>

        <footer class="panel-footer">&copy; {{ date('Y') }} {{ config('app.name') }}</footer>
    </div>

    <div class="lk-toasts" id="lk-toasts" role="status" aria-live="polite"></div>

    @include('partials.scripts')
    <script src="{{ asset('assets/js/emoji-field.js') }}?v={{ filemtime(public_path('assets/js/emoji-field.js')) }}"></script>
    <script>
        /* Small toast helper for AJAX actions: lkToast('Saved') / lkToast('Oops', 'error') */
        window.lkToast = function (message, type) {
            var $t = $('<div class="lk-toast">').toggleClass('error', type === 'error')
                .append($('<i>').addClass(type === 'error' ? 'fa-solid fa-circle-exclamation' : 'fa-solid fa-circle-check'))
                .append($('<span>').text(message));
            $('#lk-toasts').append($t);
            setTimeout(function () { $t.fadeOut(250, function () { $t.remove(); }); }, 3200);
        };
        window.lkAjaxError = function (xhr) {
            var r = xhr.responseJSON || {};
            var msg = r.errors ? r.errors[Object.keys(r.errors)[0]][0] : (r.message || 'Something went wrong. Please try again.');
            if (xhr.status === 419) msg = 'Your session expired. Please refresh the page.';
            lkToast(msg, 'error');
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        if (window.Chart) {
            Chart.defaults.font.family = '"Instrument Sans", system-ui, sans-serif';
            Chart.defaults.color = '#6B6384';
            Chart.defaults.borderColor = '#EDE7FF';
        }
    </script>
    @stack('scripts')
</body>
</html>
