{{-- Linkora logo: icon + wordmark. The white-text wordmark is shown automatically on dark
     backgrounds (site footer, admin sidebar) — see .lk-logo rules in linkora.css. --}}
<a class="lk-logo {{ $class ?? '' }}" href="{{ $href ?? route('home') }}" aria-label="{{ config('app.name') }} home">
    <img class="lk-logo-icon" src="{{ asset('assets/img/logo-icon.png') }}" alt="" width="226" height="256">
    <img class="lk-logo-wm lk-logo-wm-dark" src="{{ asset('assets/img/logo-wordmark.png') }}" alt="{{ config('app.name') }}" width="494" height="120">
    <img class="lk-logo-wm lk-logo-wm-light" src="{{ asset('assets/img/logo-wordmark-light.png') }}" alt="" width="494" height="120" aria-hidden="true">
</a>
