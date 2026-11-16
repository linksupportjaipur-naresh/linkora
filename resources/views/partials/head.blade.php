<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="description" content="@yield('description', 'Linkora puts your socials, store, videos and payments on one beautiful page. Share one link and see what people tap.')">
<meta name="theme-color" content="#7955E8">
@hasSection('title')
<title>@yield('title') · {{ config('app.name') }}</title>
@else
<title>{{ config('app.name') }} · One link for everything you share</title>
@endif
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/img/favicon-32.png') }}">
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('assets/img/icon-192.png') }}">
<link rel="apple-touch-icon" href="{{ asset('assets/img/apple-touch-icon.png') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="{{ asset('assets/css/linkora.css') }}?v={{ filemtime(public_path('assets/css/linkora.css')) }}">
