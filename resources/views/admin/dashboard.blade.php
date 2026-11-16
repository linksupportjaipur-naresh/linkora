@extends('layouts.admin')

@section('title', 'Admin dashboard')
@section('page_title', 'Dashboard')

@php
    // % change vs the previous period → [css class, label]
    $delta = function (int $now, int $prev) {
        if ($prev === 0) {
            return $now > 0 ? ['up', 'New'] : ['flat', 'No change'];
        }
        $pct = (int) round(($now - $prev) / $prev * 100);

        return $pct === 0 ? ['flat', '0%'] : [$pct > 0 ? 'up' : 'down', ($pct > 0 ? '+' : '') . $pct . '%'];
    };
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $firstName = \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->first();
    $pctOf = fn ($part, $whole) => $whole ? (int) round($part / $whole * 100) : 0;
    $rings = [
        [$pctOf($stats['published'], $stats['profiles']), 'Pages live'],
        [$pctOf($stats['active_7d'], $stats['users']), 'Active this week'],
        [min(100, (int) round($stats['ctr'])), 'Click-through'],
    ];
    $sum30 = ['signups' => array_sum($chart['signups']->all()), 'views' => array_sum($chart['views']->all()), 'clicks' => array_sum($chart['clicks']->all())];
@endphp

@section('content')
    {{-- ============ Hero ============ --}}
    <section class="ad-hero ad-reveal" aria-label="Overview">
        <div class="ad-hero-mesh" aria-hidden="true"><i></i><i></i><i></i></div>
        <div class="ad-hero-grid" aria-hidden="true"></div>
        <div class="ad-hero-row">
            <div class="ad-hero-copy">
                <span class="ad-hero-date"><span class="dot"></span>Live · {{ now()->format('l, d M Y') }}</span>
                <h2>{{ $greeting }}, {{ $firstName }} 👋</h2>
                <p>
                    <b>{{ number_format($stats['new_this_week']) }}</b> new {{ \Illuminate\Support\Str::plural('member', $stats['new_this_week']) }} this week,
                    <b>{{ number_format($stats['views_7d']) }}</b> profile {{ \Illuminate\Support\Str::plural('view', $stats['views_7d']) }}
                    @if ($stats['enquiries_new'])
                        and <b>{{ $stats['enquiries_new'] }}</b> {{ \Illuminate\Support\Str::plural('enquiry', $stats['enquiries_new']) }} waiting for a reply.
                    @else
                        and no enquiries waiting. 🎉
                    @endif
                </p>
                <div class="ad-hero-actions">
                    <a class="ad-hbtn solid" href="{{ route('admin.users.index') }}"><i class="fa-solid fa-users"></i>Manage users</a>
                    <a class="ad-hbtn" href="{{ route('admin.themes.create') }}"><i class="fa-solid fa-plus"></i>New theme</a>
                    <a class="ad-hbtn" href="{{ route('admin.enquiries.index', ['status' => 'new']) }}"><i class="fa-regular fa-envelope"></i>Enquiries</a>
                </div>
            </div>
            <div class="ad-hero-rings">
                <svg width="0" height="0" aria-hidden="true" style="position:absolute">
                    <defs><linearGradient id="adRingGrad" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#FFFFFF"/><stop offset="1" stop-color="#C4B5FD"/></linearGradient></defs>
                </svg>
                @foreach ($rings as [$pct, $label])
                    <div class="ad-ring" role="img" aria-label="{{ $label }}: {{ $pct }}%">
                        <svg viewBox="0 0 80 80"><circle class="bg" cx="40" cy="40" r="32"/><circle class="fg" cx="40" cy="40" r="32" data-ring="{{ $pct }}"/></svg>
                        <div class="ad-ring-val">{{ $pct }}%</div>
                        <div class="ad-ring-lbl">{{ $label }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ KPI cards ============ --}}
    <div class="row g-3 mb-3">
        @php($kpis = [
            ['Members', $stats['users'], '', $stats['new_this_week'] . ' joined in the last 7 days', $delta($stats['new_this_week'], $stats['new_prev_week']), 'signups', '#8B6CF0', '#6541D9', 'fa-solid fa-users', route('admin.users.index')],
            ['Profile views · 7 days', $stats['views_7d'], '', number_format($stats['views']) . ' all time', $delta($stats['views_7d'], $stats['views_prev_7d']), 'views', '#60A5FA', '#2563EB', 'fa-regular fa-eye', route('admin.profiles.index')],
            ['Link clicks · 7 days', $stats['clicks_7d'], '', $stats['ctr'] . '% click-through all time', $delta($stats['clicks_7d'], $stats['clicks_prev_7d']), 'clicks', '#F472B6', '#DB2777', 'fa-solid fa-arrow-pointer', route('admin.links.index')],
            ['Revenue · 30 days', $stats['revenue_30d'], '₹', '₹' . number_format($stats['revenue']) . ' all time · ' . $stats['subscriptions'] . ' paid ' . \Illuminate\Support\Str::plural('plan', $stats['subscriptions']), null, null, '#34D399', '#059669', 'fa-solid fa-indian-rupee-sign', route('admin.payments.index')],
        ])
        @foreach ($kpis as $k => [$label, $value, $prefix, $sub, $d, $series, $c1, $c2, $icon, $href])
            <div class="col-sm-6 col-xl-3">
                <a href="{{ $href }}" class="p-card ad-kpi text-reset ad-reveal" style="--i: {{ $k + 1 }}; --c1: {{ $c1 }}; --c2: {{ $c2 }}; --glow: {{ $c1 }}22">
                    <div class="ad-kpi-top">
                        <span class="ad-kpi-ico"><i class="{{ $icon }}"></i></span>
                        @if ($d)<span class="ad-delta {{ $d[0] }}" title="Compared with the 7 days before">
                            @if ($d[0] === 'up')<i class="fa-solid fa-arrow-trend-up"></i>@elseif ($d[0] === 'down')<i class="fa-solid fa-arrow-trend-down"></i>@endif{{ $d[1] }}</span>@endif
                    </div>
                    <div class="ad-kpi-lbl">{{ $label }}</div>
                    <div class="ad-kpi-val" data-count="{{ $value }}" data-prefix="{{ $prefix }}">{{ $prefix }}{{ number_format($value) }}</div>
                    <div class="ad-kpi-sub">{{ $sub }}</div>
                    @if ($series)
                        <div class="ad-spark"><canvas data-spark="{{ $series }}" data-color="{{ $c2 }}" aria-hidden="true"></canvas></div>
                    @else
                        <div style="height: 20px"></div>
                    @endif
                </a>
            </div>
        @endforeach
    </div>

    {{-- ============ Growth chart + plan mix ============ --}}
    <div class="row g-3 mb-3">
        <div class="col-xl-8">
            <div class="p-card ad-reveal" style="--i: 5">
                <div class="p-card-head flex-wrap">
                    <div>
                        <h2>Growth <span class="sub">Last 30 days</span></h2>
                    </div>
                    <div class="ad-seg" role="tablist" aria-label="Choose metric">
                        <button type="button" class="on" data-metric="signups" role="tab" aria-selected="true">Sign-ups</button>
                        <button type="button" data-metric="views" role="tab" aria-selected="false">Views</button>
                        <button type="button" data-metric="clicks" role="tab" aria-selected="false">Clicks</button>
                    </div>
                </div>
                <div class="ad-chart-total" aria-live="polite"><span id="adTotal">{{ number_format($sum30['signups']) }}</span><small id="adTotalLbl">sign-ups in 30 days</small></div>
                <div class="ad-chart-box mt-2"><canvas id="adGrowth" role="img" aria-label="Daily sign-ups, profile views and link clicks for the last 30 days"></canvas></div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="p-card ad-reveal" style="--i: 6">
                <div class="p-card-head"><h2>Members by plan <span class="sub">Who is on which plan today</span></h2><a class="ad-link" href="{{ route('admin.plans.index') }}">Plans <i class="fa-solid fa-arrow-right"></i></a></div>
                <div class="ad-donut">
                    <canvas id="adPlans" role="img" aria-label="Members by plan"></canvas>
                    <div class="ad-donut-center"><b>{{ number_format($stats['users']) }}</b><span>members</span></div>
                </div>
                @php($planColors = ['#C4B5FD', '#7955E8', '#2A1B66', '#F472B6', '#60A5FA'])
                <ul class="ad-legend">
                    @foreach ($plans as $i => $p)
                        <li><i style="background: {{ $planColors[$i % 5] }}"></i>{{ $p['name'] }}<b>{{ number_format($p['value']) }}</b><small>{{ $pctOf($p['value'], $stats['users']) }}%</small></li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    {{-- ============ Funnel · devices · attention ============ --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-6 col-xl-4">
            <div class="p-card ad-reveal" style="--i: 7">
                <div class="p-card-head"><h2>Activation funnel <span class="sub">How far members get</span></h2></div>
                <div class="ad-funnel">
                    @foreach ($funnel as $step)
                        <div class="ad-funnel-row">
                            <span>{{ $step['label'] }}</span>
                            <b>{{ number_format($step['value']) }}<small>{{ $step['pct'] }}%</small></b>
                            <div class="ad-bar"><i data-w="{{ $step['pct'] }}"></i></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-xl-4">
            <div class="p-card ad-reveal" style="--i: 8">
                @php($devTotal = array_sum($devices))
                <div class="p-card-head"><h2>Visitor devices <span class="sub">Profile views, last 30 days</span></h2></div>
                @php($devStyle = ['Mobile' => ['#7955E8', 'fa-solid fa-mobile-screen'], 'Desktop' => ['#60A5FA', 'fa-solid fa-desktop'], 'Tablet' => ['#F472B6', 'fa-solid fa-tablet-screen-button']])
                @if ($devTotal)
                    <div class="ad-split" role="img" aria-label="Mobile {{ $pctOf($devices['Mobile'], $devTotal) }}%, desktop {{ $pctOf($devices['Desktop'], $devTotal) }}%, tablet {{ $pctOf($devices['Tablet'], $devTotal) }}%">
                        @foreach ($devices as $name => $n)
                            @if ($n)<i style="flex-grow: {{ $n }}; background: {{ $devStyle[$name][0] }}"></i>@endif
                        @endforeach
                    </div>
                @else
                    <div class="ad-split"></div>
                @endif
                <div class="ad-dev">
                    @foreach ($devices as $name => $n)
                        <div><i class="{{ $devStyle[$name][1] }}" style="color: {{ $devStyle[$name][0] }}"></i><b>{{ $pctOf($n, $devTotal) }}%</b><span>{{ $name }} · {{ number_format($n) }}</span></div>
                    @endforeach
                </div>
                @unless ($devTotal)<p class="small text-muted mt-3 mb-0">No profile views in the last 30 days yet.</p>@endunless
            </div>
        </div>
        <div class="col-lg-12 col-xl-4">
            <div class="p-card ad-reveal" style="--i: 9">
                <div class="p-card-head"><h2>Needs attention <span class="sub">Things waiting on an admin</span></h2></div>
                @php($attention = array_filter([
                    $stats['enquiries_new'] ? [$stats['enquiries_new'], 'New enquiries', 'Reply from the inbox', 'fa-regular fa-envelope', 'linear-gradient(135deg,#F472B6,#DB2777)', route('admin.enquiries.index', ['status' => 'new'])] : null,
                    $stats['links_blocked'] ? [$stats['links_blocked'], 'Blocked links', 'Review or remove them', 'fa-solid fa-ban', 'linear-gradient(135deg,#FBBF24,#D97706)', route('admin.links.index', ['state' => 'blocked'])] : null,
                    $stats['blocked'] ? [$stats['blocked'], 'Suspended members', 'Check if they can return', 'fa-solid fa-user-lock', 'linear-gradient(135deg,#F87171,#DC2626)', route('admin.users.index', ['status' => 'suspended'])] : null,
                    ($stats['profiles'] - $stats['published']) ? [$stats['profiles'] - $stats['published'], 'Unpublished pages', 'Members who haven’t gone live', 'fa-regular fa-eye-slash', 'linear-gradient(135deg,#A78BFA,#6541D9)', route('admin.profiles.index')] : null,
                ]))
                @if ($attention)
                    <div class="ad-attn">
                        @foreach ($attention as [$n, $title, $hint, $icon, $bg, $href])
                            <a href="{{ $href }}"><span class="ico" style="background: {{ $bg }}"><i class="{{ $icon }}"></i></span><span>{{ $title }}<small>{{ $hint }}</small></span><b>{{ number_format($n) }}</b></a>
                        @endforeach
                    </div>
                @else
                    <div class="ad-allclear"><i class="fa-solid fa-circle-check fa-lg"></i>All clear — nothing needs your attention.</div>
                @endif
            </div>
        </div>
    </div>

    {{-- ============ Top profiles · activity · enquiries ============ --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-6 col-xl-4">
            <div class="p-card ad-reveal" style="--i: 10">
                <div class="p-card-head"><h2>Top profiles <span class="sub">Most viewed, last 30 days</span></h2></div>
                @if ($topProfiles->isNotEmpty())
                    @php($maxViews = max(1, (int) $topProfiles->max('views')))
                    <ol class="ad-list">
                        @foreach ($topProfiles as $i => $tp)
                            <li>
                                <a class="ad-row" href="{{ route('admin.users.show', $tp->id) }}">
                                    <span class="ad-rank {{ $i === 0 ? 'r1' : '' }}">{{ $i + 1 }}</span>
                                    <span class="ad-row-main"><span class="ad-row-title d-block text-truncate">{{ $tp->name }}</span><span class="ad-row-sub">{{ '@' . $tp->username }}</span></span>
                                    <span class="ad-mini-bar" aria-hidden="true"><i style="width: {{ round($tp->views / $maxViews * 100) }}%"></i></span>
                                    <b class="small">{{ number_format($tp->views) }}</b>
                                </a>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <div class="ad-empty"><i class="fa-regular fa-eye"></i>No profile views in the last 30 days yet.</div>
                @endif
            </div>
        </div>
        <div class="col-lg-6 col-xl-4">
            <div class="p-card ad-reveal" style="--i: 11">
                <div class="p-card-head"><h2>Recent activity <span class="sub">What members are doing</span></h2></div>
                @if ($activity->isNotEmpty())
                    <ul class="ad-timeline">
                        @foreach ($activity as $a)
                            <li>
                                <span class="ad-tl-ico {{ $a->tone() }}"><i class="{{ $a->icon() }}"></i></span>
                                <div>
                                    <p>
                                        @if ($a->user)<a href="{{ route('admin.users.show', $a->user) }}">{{ $a->user->name }}</a>@else<b>Deleted user</b>@endif
                                        {{ $a->adminSummary() }}
                                    </p>
                                    <time datetime="{{ $a->created_at->toIso8601String() }}" title="{{ $a->created_at->format('d M Y, H:i') }}">{{ $a->created_at->diffForHumans() }}</time>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="ad-empty"><i class="fa-solid fa-wave-square"></i>No activity yet.</div>
                @endif
            </div>
        </div>
        <div class="col-lg-12 col-xl-4">
            <div class="p-card ad-reveal" style="--i: 12">
                <div class="p-card-head"><h2>Latest enquiries <span class="sub">From the contact form</span></h2><a class="ad-link" href="{{ route('admin.enquiries.index') }}">All <i class="fa-solid fa-arrow-right"></i></a></div>
                @if ($recentEnquiries->isNotEmpty())
                    <ul class="ad-list">
                        @foreach ($recentEnquiries as $e)
                            <li>
                                <a class="ad-row" href="{{ route('admin.enquiries.show', $e) }}">
                                    <span class="ad-av">{{ strtoupper(mb_substr($e->name, 0, 1)) }}</span>
                                    <span class="ad-row-main">
                                        <span class="d-flex justify-content-between gap-2"><span class="ad-row-title text-truncate">{{ $e->name }}</span>@include('admin.partials.status', ['s' => $e->status])</span>
                                        <span class="ad-row-sub d-block text-truncate">{{ $e->subject }} · {{ $e->created_at->diffForHumans(null, true) }}</span>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="ad-empty"><i class="fa-regular fa-envelope-open"></i>No enquiries yet.</div>
                @endif
            </div>
        </div>
    </div>

    {{-- ============ Newest members ============ --}}
    <div class="p-card ad-reveal" style="--i: 13">
        <div class="p-card-head"><h2>Newest members <span class="sub">The latest people to sign up</span></h2><a class="ad-link" href="{{ route('admin.users.index') }}">All users <i class="fa-solid fa-arrow-right"></i></a></div>
        @include('admin.users.table', ['users' => $recentUsers, 'compact' => true])
    </div>
@endsection

@push('scripts')
<script>
(function () {
    var data = @json($chart);
    var plans = @json($plans);
    var totals = @json($sum30);
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var META = {
        signups: { label: 'Sign-ups', unit: 'sign-ups in 30 days', color: '#7955E8', rgb: '121,85,232' },
        views:   { label: 'Profile views', unit: 'profile views in 30 days', color: '#2563EB', rgb: '37,99,235' },
        clicks:  { label: 'Link clicks', unit: 'link clicks in 30 days', color: '#DB2777', rgb: '219,39,119' }
    };

    // Count-up for KPI numbers
    document.querySelectorAll('[data-count]').forEach(function (el) {
        var target = +el.getAttribute('data-count'), prefix = el.getAttribute('data-prefix') || '';
        if (reduce || !target) return;
        var t0 = null, fmt = new Intl.NumberFormat('en-IN');
        function step(t) {
            if (!t0) t0 = t;
            var p = Math.min(1, (t - t0) / 1100), e = 1 - Math.pow(1 - p, 3);
            el.textContent = prefix + fmt.format(Math.round(target * e));
            if (p < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    });

    // Progress rings, funnel bars
    requestAnimationFrame(function () {
        document.querySelectorAll('[data-ring]').forEach(function (c) { c.style.strokeDashoffset = 201 - 201 * Math.min(100, +c.getAttribute('data-ring')) / 100; });
        document.querySelectorAll('.ad-bar i[data-w]').forEach(function (b) { b.style.width = b.getAttribute('data-w') + '%'; });
    });

    if (!window.Chart) return;
    function fade(ctx, rgb, h) {
        var g = ctx.createLinearGradient(0, 0, 0, h || 300);
        g.addColorStop(0, 'rgba(' + rgb + ',.28)'); g.addColorStop(1, 'rgba(' + rgb + ',0)');
        return g;
    }

    // Sparklines in KPI cards (last 14 days)
    document.querySelectorAll('canvas[data-spark]').forEach(function (cv) {
        var series = data[cv.getAttribute('data-spark')].slice(-14), color = cv.getAttribute('data-color');
        var rgb = META[cv.getAttribute('data-spark')].rgb;
        new Chart(cv, {
            type: 'line',
            data: { labels: data.labels.slice(-14), datasets: [{ data: series, borderColor: color, borderWidth: 2, tension: .4, pointRadius: 0, fill: true, backgroundColor: fade(cv.getContext('2d'), rgb, 56) }] },
            options: { maintainAspectRatio: false, animation: reduce ? false : { duration: 900 }, plugins: { legend: { display: false }, tooltip: { enabled: false } },
                scales: { x: { display: false }, y: { display: false, beginAtZero: true, suggestedMax: Math.max.apply(null, series) * 1.25 || 1 } },
                layout: { padding: 0 } }
        });
    });

    // Main growth chart with metric switch
    var cv = document.getElementById('adGrowth'), ctx = cv.getContext('2d');
    var growth = new Chart(cv, {
        type: 'line',
        data: { labels: data.labels, datasets: [{ label: META.signups.label, data: data.signups, borderColor: META.signups.color, borderWidth: 2.5, tension: .4, fill: true,
            backgroundColor: fade(ctx, META.signups.rgb), pointRadius: 0, pointHoverRadius: 5, pointHoverBackgroundColor: '#fff', pointHoverBorderWidth: 3 }] },
        options: {
            maintainAspectRatio: false, animation: reduce ? false : { duration: 900 },
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { display: false }, tooltip: { backgroundColor: '#171329', padding: 12, cornerRadius: 12, displayColors: false, titleFont: { weight: '600' } } },
            scales: {
                x: { grid: { display: false }, ticks: { maxTicksLimit: 8, maxRotation: 0 } },
                y: { beginAtZero: true, suggestedMax: 4, ticks: { precision: 0, maxTicksLimit: 5 }, grid: { color: '#F1EDFB' }, border: { display: false } }
            }
        }
    });
    var fmt = new Intl.NumberFormat('en-IN');
    document.querySelectorAll('.ad-seg button').forEach(function (b) {
        b.addEventListener('click', function () {
            var m = b.getAttribute('data-metric'), meta = META[m], ds = growth.data.datasets[0];
            document.querySelectorAll('.ad-seg button').forEach(function (x) { x.classList.toggle('on', x === b); x.setAttribute('aria-selected', x === b); });
            ds.data = data[m]; ds.label = meta.label; ds.borderColor = meta.color; ds.backgroundColor = fade(ctx, meta.rgb);
            growth.update();
            document.getElementById('adTotal').textContent = fmt.format(totals[m]);
            document.getElementById('adTotalLbl').textContent = meta.unit;
        });
    });

    // Plan donut
    var palette = ['#C4B5FD', '#7955E8', '#2A1B66', '#F472B6', '#60A5FA'];
    var any = plans.some(function (p) { return p.value > 0; });
    new Chart(document.getElementById('adPlans'), {
        type: 'doughnut',
        data: { labels: plans.map(function (p) { return p.name; }),
            datasets: [{ data: any ? plans.map(function (p) { return p.value; }) : [1], backgroundColor: any ? palette : ['#F1EDFB'], borderWidth: 0, hoverOffset: 6, borderRadius: 6, spacing: any ? 3 : 0 }] },
        options: { maintainAspectRatio: false, cutout: '74%', animation: reduce ? false : { animateRotate: true, duration: 1000 },
            plugins: { legend: { display: false }, tooltip: { enabled: any, backgroundColor: '#171329', cornerRadius: 10 } } }
    });
})();
</script>
@endpush
