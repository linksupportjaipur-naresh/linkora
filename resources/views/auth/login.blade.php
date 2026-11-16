@extends('layouts.auth')

@section('title', 'Log in')
@section('side_tag', 'Welcome back')
@section('side_title', 'Your links, your audience, all in one place.')
@section('side_text', 'Log in to update your page, add new links and see what people tap.')

@section('side_visual')
    @include('auth.partials.network-visual')
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/auth-visual.css') }}?v={{ filemtime(public_path('assets/css/auth-visual.css')) }}">
@endpush
@push('scripts')
    <script src="{{ asset('assets/js/auth-visual.js') }}?v={{ filemtime(public_path('assets/js/auth-visual.js')) }}" defer></script>
@endpush

@section('content')
    <div class="text-center">
        <h1>Welcome back</h1>
        <p class="sub">Log in to your {{ config('app.name') }}</p>
    </div>

    @include('auth.partials.email-step', ['intent' => 'login'])

    <p class="legal">We’ll email you a 6-digit code to log in. No password needed.</p>

    <div class="divider">Or continue with</div>
    @include('partials.google-button')

    <p class="switch mb-1"><a href="{{ route('login.password') }}"><i class="fa-solid fa-key me-1"></i>Log in with password</a></p>
    <p class="switch mt-0">Don’t have an account? <a href="{{ route('register') }}">Sign up</a></p>
@endsection
