@extends('layouts.auth')

@section('title', 'Forgot password')
@section('side_tag', 'Account help')
@section('side_title', 'It happens to the best of us.')
@section('side_text', 'We’ll email you a secure link so you can choose a new password.')

@section('content')
    <div class="mb-4" style="width:56px;height:56px;border-radius:18px;background:var(--lk-soft);color:var(--lk-brand);display:grid;place-items:center;font-size:1.4rem">
        <i class="fa-solid fa-key"></i>
    </div>
    <h1>Forgot your password?</h1>
    <p class="sub">Enter the email you signed up with and we’ll send you a link to reset it.</p>

    <form method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf
        <div class="mb-4">
            <label class="form-label" for="email">Email</label>
            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email"
                   value="{{ old('email') }}" autocomplete="email" autofocus required maxlength="150">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <button type="submit" class="btn btn-primary btn-lg w-100">Email me a reset link</button>
    </form>

    <p class="text-center text-muted mt-4 mb-0"><a class="fw-semibold" href="{{ route('login') }}"><i class="fa-solid fa-arrow-left me-1"></i> Back to log in</a></p>
@endsection
