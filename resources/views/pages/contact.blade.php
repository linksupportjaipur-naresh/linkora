@extends('layouts.public')

@section('title', 'Contact us')
@section('description', 'Get in touch with the Linkora team. We reply within one working day.')

@section('content')
    <section class="page-hero pb-4">
        <div class="aurora" aria-hidden="true"><b></b><b></b><b></b></div>
        <div class="container">
            <span class="lk-eyebrow"><i class="fa-regular fa-envelope"></i> Contact</span>
            <h1>We’d love to hear from you.</h1>
            <p class="lead">Questions, feedback or partnership ideas? Send us a message and we’ll reply within one working day.</p>
        </div>
    </section>

    <section class="lk-section-sm pt-4">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 order-lg-2 rv">
                    <div class="contact-info">
                        <div class="ico"><i class="fa-regular fa-envelope"></i></div>
                        <div><h4>Email us</h4><a href="mailto:{{ config('linkora.support_email') }}" class="text-break">{{ config('linkora.support_email') }}</a></div>
                    </div>
                    <div class="contact-info">
                        <div class="ico"><i class="fa-solid fa-phone"></i></div>
                        <div><h4>Call us</h4><a href="tel:{{ preg_replace('/\s+/', '', config('linkora.support_phone')) }}">{{ config('linkora.support_phone') }}</a><p class="small">Mon–Sat, 10am–6pm IST</p></div>
                    </div>
                    <div class="contact-info">
                        <div class="ico"><i class="fa-solid fa-location-dot"></i></div>
                        <div><h4>Visit us</h4><p>{{ config('linkora.address') }}</p></div>
                    </div>
                    <div class="contact-info">
                        <div class="ico"><i class="fa-regular fa-circle-question"></i></div>
                        <div><h4>Quick answers</h4><p>Check our <a href="{{ route('pricing') }}#faq" class="fw-semibold text-brand">FAQ</a> for common questions.</p></div>
                    </div>
                </div>

                <div class="col-lg-8 order-lg-1 rv">
                    <div class="contact-card">
                        <h2 class="h3 mb-1">Send a message</h2>
                        <p class="text-muted mb-4">Fields marked <span class="text-danger">*</span> are required.</p>

                        @include('partials.alerts')

                        <form method="POST" action="{{ route('contact.store') }}" data-ajax novalidate>
                            @csrf
                            <div class="alert form-alert d-none" role="status" aria-live="polite"></div>

                            {{-- Honeypot field for bots; hidden from people and screen readers --}}
                            <div class="hp-field" aria-hidden="true">
                                <label for="website">Website</label>
                                <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="c-name">Your name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="c-name" name="name" value="{{ old('name', optional(auth()->user())->name) }}" autocomplete="name" required maxlength="100">
                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="c-email">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="c-email" name="email" value="{{ old('email', optional(auth()->user())->email) }}" autocomplete="email" required maxlength="150">
                                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="c-phone">Phone <span class="text-muted fw-normal">(optional)</span></label>
                                    <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="c-phone" name="phone" value="{{ old('phone') }}" autocomplete="tel" maxlength="20">
                                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="c-subject">Topic <span class="text-danger">*</span></label>
                                    <select class="form-select @error('subject') is-invalid @enderror" id="c-subject" name="subject" required>
                                        <option value="" disabled {{ old('subject') ? '' : 'selected' }}>Choose a topic</option>
                                        @foreach (\App\Http\Controllers\ContactController::SUBJECTS as $subject)
                                            <option value="{{ $subject }}" {{ old('subject') === $subject ? 'selected' : '' }}>{{ $subject }}</option>
                                        @endforeach
                                    </select>
                                    @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="c-message">Message <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('message') is-invalid @enderror" id="c-message" name="message" rows="6" required minlength="10" maxlength="2000" data-counter="#msg-count">{{ old('message') }}</textarea>
                                    @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <div class="text-end small text-muted mt-1" id="msg-count" aria-live="off">0 / 2000</div>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary btn-lg"><i class="fa-regular fa-paper-plane me-2"></i>Send message</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script>$('#c-message').trigger('input');</script>
@endpush
