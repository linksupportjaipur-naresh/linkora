@extends('layouts.panel')

@section('sidebar')
    @php
        $menu = [
            'Main' => [
                ['dashboard', 'fa-solid fa-gauge-high', 'Dashboard'],
                ['dashboard.profile', 'fa-regular fa-id-card', 'My Profile'],
                ['dashboard.links', 'fa-solid fa-link', 'Manage Links'],
                ['dashboard.appearance', 'fa-solid fa-palette', 'Appearance'],
                ['dashboard.analytics', 'fa-solid fa-chart-line', 'Analytics'],
                ['dashboard.qr', 'fa-solid fa-qrcode', 'QR Code'],
            ],
            'Account' => [
                ['dashboard.settings', 'fa-solid fa-gear', 'Settings'],
            ],
        ];
    @endphp
    @foreach ($menu as $group => $items)
        <div class="nav-label">{{ $group }}</div>
        @foreach ($items as [$route, $icon, $label])
            <a class="s-link {{ request()->routeIs($route) ? 'active' : '' }}" href="{{ route($route) }}" @if (request()->routeIs($route)) aria-current="page" @endif><i class="{{ $icon }}"></i> {{ $label }}</a>
        @endforeach
    @endforeach

    @php($sbProfile = auth()->user()->profileRecord())
    <div class="sb-page-card mt-4">
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="dot {{ $sbProfile->is_published ? 'live' : '' }}"></span>
            <strong class="small">{{ $sbProfile->is_published ? 'Your page is live' : 'Your page is hidden' }}</strong>
        </div>
        <div class="small text-muted text-truncate mb-2">{{ auth()->user()->publicUrl() }}</div>
        <a href="{{ auth()->user()->publicLink() }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary w-100"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i>View my page</a>
    </div>
@endsection

@section('topbar_actions')
    <a href="{{ auth()->user()->publicLink() }}" target="_blank" rel="noopener" class="btn btn-sm btn-ghost d-none d-sm-inline-flex align-items-center"><i class="fa-solid fa-arrow-up-right-from-square me-2"></i>My page</a>
@endsection
