<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head')
    <meta name="robots" content="noindex">
</head>
<body>
    <main class="d-flex flex-column align-items-center justify-content-center text-center px-3" style="min-height:100vh">
        @include('partials.logo')
        <div class="mt-5" style="width:72px;height:72px;border-radius:22px;background:var(--lk-soft);color:var(--lk-brand);display:grid;place-items:center;font-size:1.8rem">
            <i class="fa-solid fa-link-slash"></i>
        </div>
        <h1 class="mt-4" style="font-size:clamp(1.8rem,4vw,2.6rem)">This page isn’t available</h1>
        <p class="text-muted mt-2" style="max-width:420px">The link may be wrong, or the page may have been unpublished. Want a page like this for yourself?</p>
        <div class="d-flex gap-2 mt-3 flex-wrap justify-content-center">
            <a href="{{ route('register') }}" class="btn btn-primary">Claim your link</a>
            <a href="{{ route('home') }}" class="btn btn-ghost">Go to {{ config('app.name') }}</a>
        </div>
    </main>
</body>
</html>
