@extends('layouts.dashboard')

@section('title', 'Settings')
@section('page_title', 'Settings')

@section('content')
    <div class="row g-3" style="max-width: 900px">
        {{-- Photo (the same photo as on My Profile / the public page) --}}
        @php
            $photoUrl = $profile ? $profile->avatarUrl() : ($user->avatar ?: null);
            $uploaded = $profile && $profile->avatar_path;
        @endphp
        <div class="col-12">
            <div class="p-card set-photo">
                <form method="POST" action="{{ route('dashboard.settings.photo') }}" enctype="multipart/form-data" id="photo-form" class="set-photo-row" novalidate>
                    @csrf
                    <div class="set-photo-av" id="set-photo-preview">
                        @if ($photoUrl)
                            <img src="{{ $photoUrl }}" alt="Your profile photo" referrerpolicy="no-referrer">
                        @else
                            <span>{{ $user->initials() }}</span>
                        @endif
                        <label for="set-avatar" class="set-photo-cam" title="Change photo" aria-label="Change photo"><i class="fa-solid fa-camera"></i></label>
                        <span class="set-photo-busy" hidden><i class="fa-solid fa-spinner fa-spin"></i></span>
                    </div>
                    <div class="flex-fill">
                        <h3 class="section-title mb-1">Profile photo</h3>
                        <p class="section-sub mb-2">Shown on your public page and in your dashboard. JPG, PNG or WebP up to 10 MB — you can zoom, move and rotate it before saving.</p>
                        <div class="d-flex flex-wrap gap-2">
                            <label class="btn btn-sm btn-primary mb-0" for="set-avatar"><i class="fa-solid fa-upload me-1"></i>{{ $photoUrl ? 'Change photo' : 'Upload photo' }}</label>
                            <button type="button" class="btn btn-sm btn-ghost" id="set-avatar-adjust" data-saved="{{ $uploaded ? $photoUrl : '' }}" @unless ($uploaded) hidden @endunless>
                                <i class="fa-solid fa-crop-simple me-1"></i>Adjust
                            </button>
                            @if ($uploaded)
                                <button type="submit" class="btn btn-sm btn-ghost text-danger" form="photo-remove-form"><i class="fa-regular fa-trash-can me-1"></i>Remove</button>
                            @endif
                        </div>
                        @if (! $uploaded && $user->avatar)
                            <div class="form-text">Currently showing your Google photo.</div>
                        @endif
                        <div class="invalid-feedback d-block" id="set-avatar-error">@error('avatar'){{ $message }}@enderror</div>
                    </div>
                    <input type="file" class="d-none" id="set-avatar" name="avatar" accept="image/jpeg,image/png,image/webp">
                </form>
                <form method="POST" action="{{ route('dashboard.settings.photo.remove') }}" id="photo-remove-form"
                      data-confirm="Your page will show {{ $user->avatar ? 'your Google photo' : 'your initials' }} instead." data-confirm-title="Remove your photo?" data-confirm-ok="Remove photo" data-confirm-tone="danger">@csrf</form>
            </div>
        </div>

        {{-- Account --}}
        <div class="col-12">
            <div class="p-card">
                <h3 class="section-title">Account</h3>
                <p class="section-sub">Your login details. Your public name and the rest of your page are on <a href="{{ route('dashboard.profile') }}">My Profile</a>.</p>
                <form method="POST" action="{{ route('dashboard.settings.account') }}" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-8">
                        <label class="form-label" for="name">Account name</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" maxlength="100" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4"><button type="submit" class="btn btn-primary w-100">Save name</button></div>
                </form>

                <hr class="my-4" style="border-color: var(--lk-line); opacity: 1">

                <label class="form-label" for="email">Email</label>
                @if ($pendingEmail)
                    <div class="alert alert-info py-2 small mb-2"><i class="fa-regular fa-envelope me-1"></i>Waiting for confirmation of <strong>{{ $pendingEmail }}</strong>. Your current email stays active until you confirm.</div>
                    <form method="POST" action="{{ route('dashboard.settings.email.confirm') }}" class="row g-2 align-items-start" novalidate>
                        @csrf
                        <div class="col-md-5">
                            <input type="text" class="form-control @error('email_code') is-invalid @enderror" name="email_code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="6-digit code" aria-label="Code from the email">
                            @error('email_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-7 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Confirm new email</button>
                            <button type="submit" class="btn btn-ghost" formaction="{{ route('dashboard.settings.email.cancel') }}">Cancel</button>
                        </div>
                    </form>
                @else
                    <form method="POST" action="{{ route('dashboard.settings.email') }}" class="row g-2 align-items-start" novalidate>
                        @csrf
                        <div class="col-md-8">
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" maxlength="150" autocomplete="email" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">We’ll send a code to the new address to confirm it’s yours.{{ $user->google_id ? ' Your Google sign-in keeps working.' : '' }}</div>
                        </div>
                        <div class="col-md-4"><button type="submit" class="btn btn-outline-primary w-100">Change email</button></div>
                    </form>
                @endif
            </div>
        </div>

        {{-- Plan --}}
        <div class="col-12">
            <div class="p-card d-flex flex-wrap align-items-center gap-3">
                <div class="stat-card flex-fill">
                    <div class="ico"><i class="fa-solid fa-crown"></i></div>
                    <div>
                        <div class="fw-bold">{{ optional($plan)->name ?? 'Free' }} plan</div>
                        <div class="small text-muted">
                            @if ($subscription && $subscription->ends_at)
                                Renews or ends on {{ $subscription->ends_at->format('d M Y') }}
                            @elseif ($subscription)
                                No end date
                            @else
                                Free forever
                            @endif
                        </div>
                    </div>
                </div>
                <a href="{{ route('pricing') }}" class="btn btn-ghost btn-sm" target="_blank" rel="noopener">See plans</a>
            </div>
        </div>

        {{-- Password --}}
        <div class="col-12">
            <div class="p-card">
                <h3 class="section-title">{{ $user->hasOwnPassword() ? 'Change password' : 'Set a password' }}</h3>
                <p class="section-sub">
                    @if ($user->hasOwnPassword())
                        Enter your current password, then choose a new one (8+ characters with letters and numbers).
                    @else
                        You log in with an email code{{ $user->google_id ? ' or Google' : '' }}. Add a password if you’d also like to log in with it.
                    @endif
                </p>
                <form method="POST" action="{{ route('dashboard.settings.password') }}" class="row g-3" novalidate>
                    @csrf
                    @if ($user->hasOwnPassword())
                        <div class="col-md-4">
                            <label class="form-label" for="current_password">Current password</label>
                            <input type="password" class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password" autocomplete="current-password" required>
                            @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    @endif
                    <div class="col-md-4">
                        <label class="form-label" for="password">New password</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" autocomplete="new-password" required data-pw-meter="#pw-meter">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="pw-meter" id="pw-meter"><span></span></div>
                        <small class="pw-label text-muted"></small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="password_confirmation">Confirm new password</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
                    </div>
                    <div class="col-12"><button type="submit" class="btn btn-primary">{{ $user->hasOwnPassword() ? 'Change password' : 'Set password' }}</button></div>
                </form>
            </div>
        </div>

        {{-- Session --}}
        <div class="col-12">
            <div class="p-card d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h3 class="section-title">Log out</h3>
                    <p class="section-sub mb-0">Sign out of {{ config('app.name') }} on this device.</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button type="submit" class="btn btn-ghost"><i class="fa-solid fa-arrow-right-from-bracket me-1"></i>Log out</button>
                </form>
            </div>
        </div>

        {{-- Danger zone --}}
        @unless ($user->isAdmin())
            <div class="col-12">
                <div class="p-card" style="box-shadow: inset 0 0 0 1px #F5C2C7">
                    <h3 class="section-title text-danger">Deactivate or delete</h3>
                    <p class="section-sub">
                        <strong>Deactivate</strong> takes your page offline and logs you out. Log in again any time to come back.<br>
                        <strong>Delete</strong> permanently removes your account, page, links and analytics. This can’t be undone.
                    </p>
                    <form method="POST" action="{{ route('dashboard.settings.deactivate') }}" class="row g-3 align-items-start" id="danger-form" novalidate>
                        @csrf
                        <div class="col-md-6">
                            <label class="form-label" for="confirm_username">Type <strong>{{ $user->username }}</strong> to confirm</label>
                            <input type="text" class="form-control @error('confirm_username') is-invalid @enderror @error('deactivate') is-invalid @enderror" id="confirm_username" name="confirm_username" autocomplete="off" autocapitalize="none" spellcheck="false" required>
                            @error('confirm_username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @error('deactivate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        @if ($user->hasOwnPassword())
                            <div class="col-md-6">
                                <label class="form-label" for="current_password_confirm">Your password</label>
                                <input type="password" class="form-control @error('current_password_confirm') is-invalid @enderror" id="current_password_confirm" name="current_password" autocomplete="current-password" required>
                                @error('current_password_confirm')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @endif
                        <div class="col-12 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-outline-secondary rounded-pill" data-danger disabled><i class="fa-solid fa-power-off me-1"></i>Deactivate account</button>
                            <button type="submit" class="btn btn-outline-danger rounded-pill" formaction="{{ route('dashboard.settings.destroy') }}" data-danger data-delete disabled><i class="fa-regular fa-trash-can me-1"></i>Delete permanently</button>
                        </div>
                    </form>
                </div>
            </div>
        @endunless
    </div>
    @include('dashboard.partials.avatar-crop-modal')
@endsection

@push('scripts')
<script>
    // Profile photo: pick → adjust → saved straight away
    (function () {
        var input = document.getElementById('set-avatar'), err = document.getElementById('set-avatar-error');
        var adjust = document.getElementById('set-avatar-adjust');
        LkAvatarCropper({
            input: input,
            adjustBtn: adjust,
            savedUrl: adjust.getAttribute('data-saved') || null,
            onError: function (msg) { err.textContent = msg; },
            onApplied: function (url) {
                var box = document.getElementById('set-photo-preview');
                var img = box.querySelector('img') || box.insertBefore(document.createElement('img'), box.firstChild);
                var initials = box.querySelector(':scope > span:not(.set-photo-busy)');
                if (initials) initials.remove();
                img.src = url; img.alt = 'Your new profile photo';
                box.querySelector('.set-photo-busy').hidden = false;
                document.getElementById('photo-form').submit();
            }
        });
    })();

    $('#confirm_username').on('input', function () {
        $('[data-danger]').prop('disabled', $.trim(this.value).toLowerCase() !== @json($user->username));
    });
    $('#danger-form').on('submit', function (e) {
        var form = this;
        if (form.dataset.lkConfirmed === '1') { form.dataset.lkConfirmed = ''; return; }
        e.preventDefault();
        var submitter = e.originalEvent && e.originalEvent.submitter;
        var del = submitter && submitter.hasAttribute('data-delete');
        lkConfirm(del ? {
            title: 'Delete your account?',
            message: 'Your page, links and analytics will be permanently deleted. This can’t be undone.',
            confirmText: 'Delete my account', cancelText: 'Keep my account', tone: 'danger'
        } : {
            title: 'Deactivate your account?',
            message: 'Your page goes offline and you’ll be logged out. Log in again any time to come back.',
            confirmText: 'Deactivate', cancelText: 'Cancel', tone: 'warning'
        }).then(function (ok) {
            if (ok !== 'confirm') return;
            form.dataset.lkConfirmed = '1';
            form.requestSubmit(submitter);
        });
    });
</script>
@endpush
