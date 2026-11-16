@extends('layouts.auth')

@section('title', 'Check your inbox')
@section('side_tag', 'Almost there')
@section('side_title', 'One quick check and you’re in.')
@section('side_text', 'Enter the 6-digit code from your email. It keeps your account safe.')

@section('content')
    <a href="{{ $backUrl }}" class="back-link"><i class="fa-solid fa-chevron-left"></i> Back</a>

    <div class="text-center">
        <h1>Check your inbox</h1>
        <p class="sub">We sent a temporary 6-digit code to <strong class="text-break">{{ $email }}</strong>.</p>
    </div>

    <form method="POST" action="{{ route('otp.verify') }}" id="otp-form" novalidate>
        @csrf
        <input type="hidden" name="code" id="otp-code">
        <fieldset class="otp-boxes @error('code') is-invalid @enderror" aria-describedby="otp-help">
            <legend class="visually-hidden">6-digit code</legend>
            @for ($i = 0; $i < 6; $i++)
                <input type="text" class="otp-box" inputmode="numeric" pattern="[0-9]*"
                       autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}" aria-label="Digit {{ $i + 1 }}" {{ $i === 0 ? 'autofocus' : '' }}>
            @endfor
        </fieldset>
        @error('code')<div class="otp-error" role="alert"><i class="fa-solid fa-circle-exclamation me-1"></i>{{ $message }}</div>@enderror
        <p class="visually-hidden" id="otp-help">Enter the 6-digit code from your email. You can paste it into the first box.</p>

        <button type="submit" class="btn btn-primary btn-lg w-100" id="otp-submit" disabled>Submit</button>
    </form>

    <form method="POST" action="{{ route('otp.resend') }}" class="text-center mt-3">
        @csrf
        <button type="submit" class="resend-btn" id="resend-btn" data-wait="{{ $resendIn }}">Resend code</button>
    </form>

    <p class="legal mt-3 mb-0">Can’t find it? Check your spam folder. The code expires in {{ \App\Models\LoginCode::LIFETIME_MINUTES }} minutes.</p>
@endsection
