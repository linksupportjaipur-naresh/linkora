@extends('layouts.dashboard')

@section('title', 'Analytics')
@section('page_title', 'Analytics')

@section('content')
    <div class="page-head">
        <div>
            <h2>How your page is doing</h2>
            <p>Last {{ $range }} days, including today. Your own visits aren’t counted.</p>
        </div>
        <nav class="range-tabs" aria-label="Date range">
            @foreach (\App\Http\Controllers\Dashboard\AnalyticsController::RANGES as $r)
                <a href="{{ route('dashboard.analytics', ['range' => $r]) }}" class="{{ $range === $r ? 'active' : '' }}" @if ($range === $r) aria-current="true" @endif>{{ $r }} days</a>
            @endforeach
        </nav>
    </div>

    <div class="row g-3 mb-3">
        @foreach ([
            ['fa-regular fa-eye', '', number_format($views), 'Profile views'],
            ['fa-solid fa-user-group', 'green', number_format($unique), 'Unique visitors'],
            ['fa-solid fa-arrow-pointer', 'amber', number_format($clicks), 'Link clicks'],
            ['fa-solid fa-percent', 'pink', $ctr . '%', 'Click-through rate'],
        ] as [$icon, $tone, $value, $label])
            <div class="col-sm-6 col-xl-3">
                <div class="p-card stat-card">
                    <div class="ico {{ $tone }}"><i class="{{ $icon }}"></i></div>
                    <div><div class="val">{{ $value }}</div><div class="lbl">{{ $label }}</div></div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="p-card mb-3">
        <div class="p-card-head"><h2>Daily trend</h2></div>
        <div class="chart-box"><canvas id="trendChart" role="img" aria-label="Profile views and link clicks per day for the last {{ $range }} days"></canvas></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="p-card">
                <div class="p-card-head"><h2>Most-clicked links</h2></div>
                @php($maxClicks = max(1, $topLinks->max('period_clicks')))
                @forelse ($topLinks as $i => $link)
                    <div class="bar-row">
                        <div class="top">
                            <span>{{ $i + 1 }}. {{ $link->title }} @unless ($link->is_active)<span class="badge-soft amber ms-1">Hidden</span>@endunless</span>
                            <strong>{{ number_format($link->period_clicks) }}</strong>
                        </div>
                        <div class="track"><div class="fill" style="width: {{ round($link->period_clicks / $maxClicks * 100) }}%"></div></div>
                    </div>
                @empty
                    <div class="empty-state"><i class="fa-solid fa-link d-block"></i>Add links to see which ones people tap.<br><a href="{{ route('dashboard.links') }}" class="fw-semibold">Add a link</a></div>
                @endforelse
            </div>
        </div>
        <div class="col-lg-6">
            <div class="p-card mb-3">
                <div class="p-card-head"><h2>Where visitors come from</h2></div>
                @php($maxRef = max(1, $referrers->max('total')))
                @forelse ($referrers as $ref)
                    <div class="bar-row">
                        <div class="top"><span>{{ $ref->source }}</span><strong>{{ number_format($ref->total) }}</strong></div>
                        <div class="track"><div class="fill" style="width: {{ round($ref->total / $maxRef * 100) }}%"></div></div>
                    </div>
                @empty
                    <div class="empty-state"><i class="fa-solid fa-share-nodes d-block"></i>Share your link to start seeing visitors.</div>
                @endforelse
            </div>
            <div class="p-card">
                <div class="p-card-head"><h2>Devices</h2></div>
                @php($deviceTotal = array_sum($devices))
                @if ($deviceTotal)
                    <div class="row g-2 text-center">
                        @foreach (['mobile' => 'fa-solid fa-mobile-screen', 'desktop' => 'fa-solid fa-desktop', 'tablet' => 'fa-solid fa-tablet-screen-button'] as $d => $icon)
                            <div class="col-4">
                                <div class="p-2 rounded-4" style="background:var(--lk-bg)">
                                    <i class="{{ $icon }} text-brand"></i>
                                    <div class="fw-bold fs-5 mt-1">{{ round($devices[$d] / $deviceTotal * 100) }}%</div>
                                    <div class="small text-muted">{{ ucfirst($d) }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state py-3">No visits in this period yet.</div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        var data = @json($daily);
        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    { label: 'Views', data: data.views, borderColor: '#7955E8', backgroundColor: 'rgba(121,85,232,.12)', fill: true, tension: .35, pointRadius: data.labels.length > 10 ? 2 : 4 },
                    { label: 'Clicks', data: data.clicks, borderColor: '#2BB673', backgroundColor: 'rgba(43,182,115,.08)', fill: true, tension: .35, pointRadius: data.labels.length > 10 ? 2 : 4 }
                ]
            },
            options: {
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } } },
                scales: { y: { beginAtZero: true, suggestedMax: 5, ticks: { precision: 0 } }, x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkipPadding: 12 } } }
            }
        });
    })();
</script>
@endpush
