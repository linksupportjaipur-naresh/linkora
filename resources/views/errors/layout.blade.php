<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') · {{ config('app.name', 'Linkora') }}</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/img/favicon-32.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('assets/img/icon-192.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/img/apple-touch-icon.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,800&family=Instrument+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; background: #F8F6FF; color: #1B1233; font: 400 17px/1.6 "Instrument Sans", system-ui, sans-serif; text-align: center; }
        .box { max-width: 480px; }
        .logo { display: inline-flex; align-items: center; gap: 10px; font: 800 1.3rem "Bricolage Grotesque", sans-serif; color: #1B1233; text-decoration: none; }
        .logo img { width: 30px; height: 30px; }
        .code { font: 800 clamp(4rem, 14vw, 7rem)/1 "Bricolage Grotesque", sans-serif; color: #7955E8; letter-spacing: -.04em; margin: 32px 0 8px; }
        h1 { font: 800 1.8rem/1.2 "Bricolage Grotesque", sans-serif; letter-spacing: -.02em; margin: 0 0 10px; }
        p { color: #6B6384; margin: 0 0 24px; }
        .btns { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 22px; border-radius: 999px; font-weight: 600; text-decoration: none; border: 1.5px solid #E3DBFA; color: #1B1233; background: #fff; cursor: pointer; font-size: 1rem; font-family: inherit; }
        .btn.primary { background: #7955E8; border-color: #7955E8; color: #fff; }
        .btn:focus-visible { outline: 3px solid #7955E8; outline-offset: 3px; }
    </style>
</head>
<body>
    <main class="box">
        <a class="logo" href="{{ url('/') }}" aria-label="{{ config('app.name', 'Linkora') }} home"><img src="{{ asset('assets/img/logo-icon.png') }}" alt="" style="width:auto;height:38px"><img src="{{ asset('assets/img/logo-wordmark.png') }}" alt="{{ config('app.name', 'Linkora') }}" style="width:auto;height:27px"></a>
        <div class="code">@yield('code')</div>
        <h1>@yield('heading')</h1>
        <p>@yield('message')</p>
        <div class="btns">
            @section('actions')
                <a class="btn primary" href="{{ url('/') }}"><i class="fa-solid fa-house"></i>Go home</a>
                <button class="btn" type="button" onclick="history.back()"><i class="fa-solid fa-arrow-left"></i>Go back</button>
            @show
        </div>
    </main>
</body>
</html>
