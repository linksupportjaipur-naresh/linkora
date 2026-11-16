@extends('layouts.auth')

@section('title', 'Choose a new password')
@section('side_tag', 'Account help')
@section('side_title', 'Almost there.')
@section('side_text', 'Pick a strong password you haven’t used anywhere else.')

@section('content')
    <h1>Choose a new password</h1>
    <p class="sub">Your new password must be at least 8 characters, with letters and numbers.</p>

    <form method="POST" action="{{ route('password.update') }}" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email"
                   value="{{ old('email', $email) }}" autocomplete="email" required>
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="password">New password</label>
            <div class="input-group has-validation">
                <input type="password" class="form-control with-toggle @error('password') is-invalid @enderror" id="password" name="password"
                       autocomplete="new-password" autofocus required data-pw-meter="#pw-meter">
                <button class="btn-toggle-pass" type="button" aria-label="Show password"><i class="fa-regular fa-eye"></i></button>
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="pw-meter" id="pw-meter"><span></span></div>
            <small class="pw-label text-muted"></small>
        </div>

        <div class="mb-4">
            <label class="form-label" for="password_confirmation">Confirm new password</label>
            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100">Reset password</button>
    </form>
@endsection
