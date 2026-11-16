@extends('layouts.panel', ['homeRoute' => route('admin.dashboard')])

@section('sidebar_class', 'dark')
@section('panel_tag', 'Admin')
@section('body_class', 'panel-admin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}?v={{ filemtime(public_path('assets/css/admin.css')) }}">
@endpush

@section('sidebar')
    @php
        $newEnquiries = \App\Models\Enquiry::unread()->count();
        $blockedLinks = \App\Models\Link::where('is_blocked', true)->count();
        // [route, active pattern, icon, label, badge]
        $menu = [
            'Overview' => [
                ['admin.dashboard', 'admin.dashboard', 'fa-solid fa-gauge-high', 'Dashboard', null],
            ],
            'Manage' => [
                ['admin.users.index', 'admin.users.*', 'fa-solid fa-users', 'Users', null],
                ['admin.profiles.index', 'admin.profiles.*', 'fa-regular fa-id-card', 'Profiles', null],
                ['admin.links.index', 'admin.links.*', 'fa-solid fa-link', 'Links', $blockedLinks ? ['muted', $blockedLinks] : null],
                ['admin.themes.index', 'admin.themes.*', 'fa-solid fa-palette', 'Themes', null],
                ['admin.plans.index', 'admin.plans.*', 'fa-solid fa-crown', 'Plans', null],
            ],
            'Billing' => [
                ['admin.subscriptions.index', 'admin.subscriptions.*', 'fa-solid fa-arrows-rotate', 'Subscriptions', null],
                ['admin.payments.index', 'admin.payments.*', 'fa-solid fa-indian-rupee-sign', 'Payments', null],
            ],
            'Support' => [
                ['admin.enquiries.index', 'admin.enquiries.*', 'fa-regular fa-envelope', 'Enquiries', $newEnquiries ? ['hot', $newEnquiries] : null],
                ['admin.settings', 'admin.settings*', 'fa-solid fa-sliders', 'Settings', null],
            ],
        ];
    @endphp
    @foreach ($menu as $group => $items)
        <div class="nav-label">{{ $group }}</div>
        @foreach ($items as [$route, $pattern, $icon, $label, $badge])
            <a class="s-link {{ request()->routeIs($pattern) ? 'active' : '' }}" href="{{ route($route) }}" @if (request()->routeIs($pattern)) aria-current="page" @endif>
                <span class="s-ico"><i class="{{ $icon }}"></i></span>
                <span class="s-text">{{ $label }}</span>
                @if ($badge)<span class="s-badge {{ $badge[0] }}" aria-label="{{ $badge[1] }} pending">{{ $badge[1] }}</span>@endif
            </a>
        @endforeach
    @endforeach

    <div class="s-promo">
        <span class="s-promo-orb" aria-hidden="true"></span>
        <i class="fa-solid fa-shield-halved"></i>
        <strong>Admin mode</strong>
        <p>Changes here affect every Linkora user.</p>
        <a href="{{ route('home') }}" target="_blank" rel="noopener">Open website <i class="fa-solid fa-arrow-up-right-from-square"></i></a>
    </div>
@endsection

@section('topbar_actions')
    <form class="adm-search d-none d-lg-flex" method="GET" action="{{ route('admin.users.index') }}" role="search">
        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
        <input type="search" name="q" placeholder="Search users by name, email or @username" aria-label="Search users" value="{{ request()->routeIs('admin.users.index') ? request('q') : '' }}">
        <kbd>/</kbd>
    </form>
    <a href="{{ route('admin.enquiries.index', ['status' => 'new']) }}" class="adm-bell" aria-label="{{ $newEnquiries }} new enquiries" title="New enquiries">
        <i class="fa-regular fa-bell"></i>
        @if ($newEnquiries)<span>{{ $newEnquiries > 9 ? '9+' : $newEnquiries }}</span>@endif
    </a>
@endsection

@push('scripts')
<script>
    // Press "/" anywhere (outside a text field) to jump to the user search
    document.addEventListener('keydown', function (e) {
        if (e.key !== '/' || /input|textarea|select/i.test(e.target.tagName) || e.target.isContentEditable) return;
        var box = document.querySelector('.adm-search input');
        if (box && box.offsetParent) { e.preventDefault(); box.focus(); }
    });
</script>
@endpush
