@extends('layouts.auth')

@section('title', 'Log in with password')
@section('side_tag', 'Welcome back')
@section('side_title', 'Your links, your audience, all in one place.')
@section('side_text', 'Log in with your email or username and password.')

@section('content')
    <a href="{{ route('login') }}" class="back-link"><i class="fa-solid fa-chevron-left"></i> Back</a>
    <div class="text-center">
        <h1>Log in with password</h1>
        <p class="sub">Use your email or username and password.</p>
    </div>

    <form method="POST" action="{{ route('login.password.store') }}" novalidate>
        @csrf
        <div class="form-floating mb-3">
            <input type="text" class="form-control @error('login') is-invalid @enderror" id="login" name="login"
                   value="{{ old('login') }}" placeholder="Email or username" autocomplete="username" required maxlength="150" autofocus>
            <label for="login">Email or username</label>
            @error('login')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="form-floating mb-2 pass-wrap">
            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password"
                   placeholder="Password" autocomplete="current-password" required>
            <label for="password">Password</label>
            <button class="pass-eye" type="button" aria-label="Show password" data-toggle-pass="#password"><i class="fa-regular fa-eye"></i></button>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label small" for="remember">Keep me logged in</label>
            </div>
            <a class="small fw-semibold" href="{{ route('password.request') }}">Forgot password?</a>
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100">Log in</button>
    </form>

    <p class="switch">Prefer a code? <a href="{{ route('login') }}">Log in with email code</a></p>
@endsection
