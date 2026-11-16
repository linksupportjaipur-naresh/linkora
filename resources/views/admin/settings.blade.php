@extends('layouts.admin')

@section('title', 'Settings')
@section('page_title', 'Settings')

@section('content')
    <form method="POST" action="{{ route('admin.settings.update') }}" style="max-width:820px" novalidate>
        @csrf
        <div class="p-card mb-3">
            <h3 class="section-title">Sign-ups</h3>
            <p class="section-sub">Turn off to pause new accounts. Existing users can still log in.</p>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="registration_open" name="registration_open" value="1" {{ old('registration_open', $values['registration_open']) ? 'checked' : '' }}>
                <label class="form-check-label" for="registration_open">Allow new sign-ups</label>
            </div>
        </div>

        <div class="p-card mb-3">
            <h3 class="section-title">Contact details</h3>
            <p class="section-sub">Shown in the website footer and on the Contact page.</p>
            <div class="row g-3">
                @foreach (['support_email' => ['Support email', 'email'], 'support_phone' => ['Support phone', 'text'], 'address' => ['Address', 'text']] as $key => [$label, $type])
                    <div class="col-md-{{ $key === 'address' ? 12 : 6 }}">
                        <label class="form-label" for="{{ $key }}">{{ $label }}</label>
                        <input type="{{ $type }}" class="form-control @error($key) is-invalid @enderror" id="{{ $key }}" name="{{ $key }}" value="{{ old($key, $values[$key]) }}" maxlength="150">
                        @error($key)<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                @endforeach
            </div>
        </div>

        <div class="p-card mb-3">
            <h3 class="section-title">Linkora social profiles</h3>
            <p class="section-sub">Icons in the website footer.</p>
            <div class="row g-3">
                @foreach (['social_instagram' => ['fa-brands fa-instagram', 'Instagram'], 'social_x' => ['fa-brands fa-x-twitter', 'X (Twitter)'], 'social_linkedin' => ['fa-brands fa-linkedin', 'LinkedIn'], 'social_youtube' => ['fa-brands fa-youtube', 'YouTube']] as $key => [$icon, $label])
                    <div class="col-md-6">
                        <label class="form-label" for="{{ $key }}"><i class="{{ $icon }} me-1"></i>{{ $label }}</label>
                        <input type="url" class="form-control @error($key) is-invalid @enderror" id="{{ $key }}" name="{{ $key }}" value="{{ old($key, $values[$key]) }}" maxlength="255" placeholder="https://">
                        @error($key)<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                @endforeach
            </div>
        </div>

        <div class="p-card mb-3">
            <h3 class="section-title">System</h3>
            <dl class="row small mb-0">
                <dt class="col-sm-4">Environment</dt><dd class="col-sm-8">{{ app()->environment() }} {{ config('app.debug') ? '· debug ON' : '' }}</dd>
                <dt class="col-sm-4">App URL</dt><dd class="col-sm-8">{{ config('app.url') }}</dd>
                <dt class="col-sm-4">Mail</dt><dd class="col-sm-8">{{ config('mail.default') }} · from {{ config('mail.from.address') }}</dd>
                <dt class="col-sm-4">Laravel / PHP</dt><dd class="col-sm-8">{{ app()->version() }} / {{ PHP_VERSION }}</dd>
                <dt class="col-sm-4">Latest backup</dt>
                <dd class="col-sm-8">
                    @php($backups = collect(\Illuminate\Support\Facades\Storage::files('backups'))->sort()->values())
                    {{ $backups->isNotEmpty() ? basename($backups->last()) . ' (' . $backups->count() . ' kept)' : 'None yet — run php artisan linkora:backup' }}
                </dd>
            </dl>
        </div>

        <button class="btn btn-primary">Save settings</button>
    </form>
@endsection
