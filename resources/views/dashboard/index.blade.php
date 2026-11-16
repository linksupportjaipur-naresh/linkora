@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-xl-8">
            <div class="welcome-card h-100 d-flex flex-column" data-wc>
                {{-- Motion-graphic background (decorative): aurora, grid, orbiting apps around your link, and "taps" flying in --}}
                <div class="wc-fx" aria-hidden="true">
                    <div class="wc-aurora"><i></i><i></i><i></i><i></i></div>
                    <div class="wc-grid"></div>
                    <canvas class="wc-canvas"></canvas>
                    <div class="wc-orbit">
                        <span class="wc-ring r1"></span><span class="wc-ring r2"></span><span class="wc-ring r3"></span>
                        <span class="wc-core"><i class="fa-solid fa-link"></i><b></b><b></b></span>
                        @foreach ([
                            ['fa-brands fa-instagram', '#E1306C', 0, 'r1'], ['fa-brands fa-youtube', '#FF0000', 180, 'r1'],
                            ['fa-brands fa-whatsapp', '#25D366', 60, 'r2'], ['fa-brands fa-spotify', '#1DB954', 180, 'r2'], ['fa-brands fa-x-twitter', '#111111', 300, 'r2'],
                            ['fa-brands fa-telegram', '#229ED9', 30, 'r3'], ['fa-solid fa-indian-rupee-sign', '#16A34A', 120, 'r3'], ['fa-brands fa-linkedin', '#0A66C2', 210, 'r3'], ['fa-brands fa-github', '#24292F', 300, 'r3'],
                        ] as [$ic, $col, $ang, $ring])
                            <span class="wc-planet {{ $ring }}" style="--a: {{ $ang }}deg; --c: {{ $col }}"><span><i class="{{ $ic }}"></i></span></span>
                        @endforeach
                    </div>
                    <span class="wc-shine"></span>
                </div>
                <h2>Hi {{ $user->firstName() }} 👋</h2>
                <p>
                    @if ($profile->is_published)
                        Your page is live. Share your link everywhere and watch the taps come in.
                    @else
                        Your page is ready to set up. Add links, pick a theme, then publish it.
                    @endif
                </p>
                <div class="mt-auto d-flex flex-wrap align-items-center gap-2">
                    <div class="link-pill">
                        <i class="fa-solid fa-link"></i>
                        <span class="text-truncate" style="max-width:240px">{{ $user->publicUrl() }}</span>
                        <button type="button" data-copy="{{ $user->publicLink() }}"><i class="fa-regular fa-copy me-1"></i>Copy</button>
                    </div>
                    <span class="badge rounded-pill mt-3 px-3 py-2" style="background:rgba(255,255,255,.18)">
                        <i class="fa-solid {{ $profile->is_published ? 'fa-circle-check' : 'fa-eye-slash' }} me-1"></i>{{ $profile->is_published ? 'Published' : 'Not published' }}
                    </span>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="p-card">
                <div class="p-card-head"><h2>Quick actions</h2></div>
                <div class="quick-actions">
                    <a class="qa" href="{{ route('dashboard.links', ['add' => 1]) }}"><span class="ico"><i class="fa-solid fa-plus"></i></span>Add link</a>
                    <a class="qa" href="{{ route('dashboard.profile') }}"><span class="ico"><i class="fa-regular fa-id-card"></i></span>Edit profile</a>
                    <a class="qa" href="{{ route('dashboard.appearance') }}"><span class="ico"><i class="fa-solid fa-palette"></i></span>Change theme</a>
                    <a class="qa" href="{{ $user->publicLink() }}" target="_blank" rel="noopener"><span class="ico"><i class="fa-solid fa-arrow-up-right-from-square"></i></span>View page</a>
                    <a class="qa" href="{{ route('dashboard.qr') }}"><span class="ico"><i class="fa-solid fa-qrcode"></i></span>QR code</a>
                    <button type="button" class="qa" data-copy="{{ $user->publicLink() }}"><span class="ico"><i class="fa-regular fa-copy"></i></span>Copy link</button>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        @foreach ([
            ['fa-regular fa-eye', '', $stats['views'], 'Profile views', number_format($stats['views_week']) . ' in the last 7 days'],
            ['fa-solid fa-arrow-pointer', 'amber', $stats['clicks'], 'Link clicks', number_format($stats['clicks_week']) . ' in the last 7 days'],
            ['fa-solid fa-link', 'green', $stats['links'], 'Total links', $stats['active_links'] . ' visible on your page'],
        ] as [$icon, $tone, $value, $label, $sub])
            <div class="col-md-4">
                <div class="p-card stat-card">
                    <div class="ico {{ $tone }}"><i class="{{ $icon }}"></i></div>
                    <div>
                        <div class="val">{{ number_format($value) }}</div>
                        <div class="lbl">{{ $label }}</div>
                        <div class="small text-muted">{{ $sub }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="p-card mb-3">
                <div class="p-card-head">
                    <h2>Last 7 days</h2>
                    <a href="{{ route('dashboard.analytics') }}" class="small fw-semibold">Full analytics <i class="fa-solid fa-arrow-right ms-1"></i></a>
                </div>
                <div class="chart-box"><canvas id="perfChart" role="img" aria-label="Profile views and link clicks over the last 7 days"></canvas></div>
            </div>
            @if ($topLinks->isNotEmpty())
                <div class="p-card">
                    <div class="p-card-head"><h2>Top links this week</h2></div>
                    @php($maxC = max(1, $topLinks->max('period_clicks')))
                    @foreach ($topLinks as $link)
                        <div class="bar-row">
                            <div class="top"><span>{{ $link->title }}</span><strong>{{ number_format($link->period_clicks) }}</strong></div>
                            <div class="track"><div class="fill" style="width: {{ round($link->period_clicks / $maxC * 100) }}%"></div></div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="col-xl-4">
            @php($done = collect($checklist)->where(1, true)->count())
            @if ($done < count($checklist))
                <div class="p-card mb-3">
                    <div class="p-card-head"><h2>Getting started</h2><span class="badge-soft">{{ $done }}/{{ count($checklist) }}</span></div>
                    <div class="progress mb-2" style="height:6px" role="progressbar" aria-label="Setup progress" aria-valuenow="{{ round($done / count($checklist) * 100) }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar" style="width:{{ round($done / count($checklist) * 100) }}%;background:var(--lk-brand)"></div>
                    </div>
                    <ul class="checklist">
                        @foreach ($checklist as [$label, $isDone, $href])
                            <li class="{{ $isDone ? 'done' : '' }}">
                                <span class="tick"><i class="fa-solid fa-check"></i></span>
                                @if ($isDone)<span class="txt">{{ $label }}</span>@else<a class="txt fw-semibold" href="{{ $href }}">{{ $label }}</a>@endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="p-card">
                <div class="p-card-head"><h2>Recent activity</h2></div>
                @if ($activities->isEmpty())
                    <div class="empty-state py-3"><i class="fa-regular fa-clock d-block"></i>Your changes will show up here.</div>
                @else
                    <ul class="activity">
                        @foreach ($activities as $a)
                            <li>
                                <span class="a-ico {{ $a->tone() }}"><i class="{{ $a->icon() }}"></i></span>
                                <div>
                                    <div class="a-txt">{{ $a->description }}</div>
                                    <div class="a-time"><time datetime="{{ $a->created_at->toIso8601String() }}" title="{{ $a->created_at->format('d M Y, h:i A') }}">{{ $a->created_at->diffForHumans() }}</time></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/welcome-fx.css') }}?v={{ filemtime(public_path('assets/css/welcome-fx.css')) }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/js/welcome-fx.js') }}?v={{ filemtime(public_path('assets/js/welcome-fx.js')) }}" defer></script>
<script>
    (function () {
        var data = @json($chart);
        new Chart(document.getElementById('perfChart'), {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    { label: 'Views', data: data.views, borderColor: '#7955E8', backgroundColor: 'rgba(121,85,232,.12)', fill: true, tension: .35, pointRadius: 3 },
                    { label: 'Clicks', data: data.clicks, borderColor: '#2BB673', backgroundColor: 'rgba(43,182,115,.08)', fill: true, tension: .35, pointRadius: 3 }
                ]
            },
            options: {
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } } },
                scales: { y: { beginAtZero: true, suggestedMax: 5, ticks: { precision: 0 } }, x: { grid: { display: false } } }
            }
        });
    })();
</script>
@endpush
