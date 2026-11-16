{{-- Email → Continue form, shared by the sign-up and log-in pages. --}}
<form method="POST" action="{{ route('otp.send') }}" class="email-step" novalidate>
    @csrf
    <input type="hidden" name="intent" value="{{ $intent }}">
    @if (!empty($claimUsername))
        <input type="hidden" name="username" value="{{ $claimUsername }}">
    @endif

    <div class="form-floating mb-3">
        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email"
               value="{{ old('email') }}" placeholder="Email" autocomplete="email" inputmode="email"
               maxlength="150" required autofocus data-email-input>
        <label for="email">Email</label>
        @error('email')
            <div class="invalid-feedback">
                {{ $message }}
                @if (session('no_account')) <a href="{{ route('register') }}" class="fw-semibold">Create an account</a> @endif
            </div>
        @enderror
    </div>

    <button type="submit" class="btn btn-primary btn-lg w-100" data-email-submit disabled>Continue</button>
</form>
