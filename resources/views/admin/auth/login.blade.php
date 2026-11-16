@extends('layouts.auth')

@section('title', 'Admin sign in')
@section('side_tag', 'Admin console')
@section('side_title', 'Run Linkora from one place.')
@section('side_text', 'Members, pages, themes, plans and enquiries — all managed from the admin console.')

@section('side_visual')
    @include('auth.partials.network-visual')
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/auth-visual.css') }}?v={{ filemtime(public_path('assets/css/auth-visual.css')) }}">
    <style>
        .adm-login-badge { display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px 6px 6px; border-radius: 999px; margin-bottom: 18px;
            background: #F5F2FF; color: #6541D9; font-weight: 600; font-size: .82rem; border: 1px solid #E4DBFF; }
        .adm-login-badge i { width: 26px; height: 26px; display: grid; place-items: center; border-radius: 50%; color: #fff; background: linear-gradient(135deg, #7955E8, #6541D9); font-size: .75rem; }
        .adm-login-note { display: flex; gap: 10px; align-items: flex-start; margin-top: 18px; padding: 12px 14px; border-radius: 14px; background: #F8F7FC; color: #6B6384; font-size: .84rem; line-height: 1.45; }
        .adm-login-note i { color: #7955E8; margin-top: 3px; }
    </style>
@endpush
@push('scripts')
    <script src="{{ asset('assets/js/auth-visual.js') }}?v={{ filemtime(public_path('assets/js/auth-visual.js')) }}" defer></script>
@endpush

@section('content')
    <div class="text-center">
        <span class="adm-login-badge"><i class="fa-solid fa-shield-halved"></i>Administrators only</span>
        <h1>Admin sign in</h1>
        <p class="sub">Sign in with your administrator email or username.</p>
    </div>

    <form method="POST" action="{{ route('admin.login') }}" novalidate>
        @csrf
        <div class="form-floating mb-3">
            <input type="text" class="form-control @error('login') is-invalid @enderror" id="login" name="login"
                   value="{{ old('login') }}" placeholder="Email or username" autocomplete="username" required maxlength="150" autofocus>
            <label for="login">Admin email or username</label>
            @error('login')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="form-floating mb-2 pass-wrap">
            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password"
                   placeholder="Password" autocomplete="current-password" required maxlength="255">
            <label for="password">Password</label>
            <button class="pass-eye" type="button" aria-label="Show password" data-toggle-pass="#password"><i class="fa-regular fa-eye"></i></button>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label small" for="remember">Keep me signed in</label>
            </div>
            <a class="small fw-semibold" href="{{ route('password.request') }}">Forgot password?</a>
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100"><i class="fa-solid fa-lock me-2"></i>Sign in to admin</button>
    </form>

    <div class="adm-login-note"><i class="fa-solid fa-circle-info"></i><span>This page is only for Linkora administrators. Members can <a href="{{ route('login') }}" class="fw-semibold">log in here</a>.</span></div>
@endsection
