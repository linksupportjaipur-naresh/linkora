@extends('layouts.auth')

@section('title', 'Sign up')
@section('side_tag', 'Free forever')
@section('side_title', 'Claim your link before someone else does.')
@section('side_text', 'Set up a beautiful page in about two minutes. No card needed.')

@section('content')
    <div class="text-center">
        @unless (config('linkora.registration_open'))
            <div class="alert alert-warning text-start small"><i class="fa-solid fa-circle-pause me-1"></i>New sign-ups are paused right now. If you already have an account, <a href="{{ route('login') }}">log in</a>.</div>
        @endunless
        <h1>Join {{ config('app.name') }}</h1>
        <p class="sub">Sign up for free!</p>
        @if ($claimUsername)
            <p class="claim-chip"><i class="fa-solid fa-link me-1"></i>Claiming <strong>{{ config('linkora.domain') }}/{{ $claimUsername }}</strong></p>
        @endif
    </div>

    @include('auth.partials.email-step', ['intent' => 'register', 'claimUsername' => $claimUsername])

    <p class="legal">By clicking <strong>Continue</strong>, you agree to {{ config('app.name') }}’s
        <a href="{{ route('home') }}">Terms of Service</a> and <a href="{{ route('home') }}">Privacy Policy</a>.</p>

    <div class="divider">Or continue with</div>
    @include('partials.google-button')

    <p class="switch">Already have an account? <a href="{{ route('login') }}">Log in</a></p>
@endsection
