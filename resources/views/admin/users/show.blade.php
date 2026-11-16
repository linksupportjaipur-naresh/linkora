@extends('layouts.admin')

@section('title', $user->name)
@section('page_title', 'User details')

@section('content')
    <a href="{{ route('admin.users.index') }}" class="small fw-semibold d-inline-block mb-3"><i class="fa-solid fa-arrow-left me-1"></i>All users</a>

    <div class="p-card mb-3">
        <div class="d-flex flex-wrap align-items-center gap-3">
            @if ($profile->avatarUrl())
                <img src="{{ $profile->avatarUrl() }}" alt="" class="rounded-circle" style="width:64px;height:64px;object-fit:cover" referrerpolicy="no-referrer">
            @else
                <span class="u-avatar" style="width:64px;height:64px;font-size:1.3rem">{{ $user->initials() }}</span>
            @endif
            <div class="flex-fill" style="min-width:200px">
                <h2 class="h4 mb-1">{{ $user->name }} @include('admin.partials.status', ['s' => $user->status]) @if ($user->isAdmin())<span class="badge-soft">Admin</span>@endif</h2>
                <div class="text-muted small">{{ $user->email }} · <a href="{{ $user->publicLink() }}" target="_blank" rel="noopener">{{ $user->publicUrl() }}</a></div>
                <div class="text-muted small">Joined {{ $user->created_at->format('d M Y') }} · Last login {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'never' }} · {{ $user->google_id ? 'Google linked' : 'Email login' }}</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen me-1"></i>Edit</a>
                @unless ($user->isAdmin())
                    @if ($user->isBlocked())
                        <form method="POST" action="{{ route('admin.users.unblock', $user) }}">@csrf<button class="btn btn-sm btn-ghost"><i class="fa-solid fa-unlock me-1"></i>Unblock</button></form>
                    @else
                        <form method="POST" action="{{ route('admin.users.block', $user) }}" data-confirm="Block {{ '@' . $user->username }}? They won’t be able to log in and their page goes offline.">@csrf<button class="btn btn-sm btn-ghost text-danger"><i class="fa-solid fa-ban me-1"></i>Block</button></form>
                    @endif
                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" data-confirm="Permanently delete {{ '@' . $user->username }} with their page, links and analytics? This can’t be undone.">@csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger rounded-pill"><i class="fa-regular fa-trash-can me-1"></i>Delete</button>
                    </form>
                @endunless
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        @foreach ([
            ['fa-regular fa-eye', '', number_format($views), 'Profile views'],
            ['fa-solid fa-arrow-pointer', 'amber', number_format($clicks), 'Link clicks'],
            ['fa-solid fa-link', 'green', $links->count(), 'Links'],
            ['fa-solid fa-globe', 'pink', $profile->is_published ? 'Live' : 'Hidden', 'Page'],
        ] as [$icon, $tone, $value, $label])
            <div class="col-6 col-xl-3">
                <div class="p-card stat-card"><div class="ico {{ $tone }}"><i class="{{ $icon }}"></i></div><div><div class="val">{{ $value }}</div><div class="lbl">{{ $label }}</div></div></div>
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-xl-7">
            <div class="p-card mb-3">
                <div class="p-card-head"><h2>Links</h2><a href="{{ route('admin.links.index', ['q' => $user->username]) }}" class="small fw-semibold">Moderate</a></div>
                @forelse ($links as $link)
                    <div class="d-flex align-items-center gap-2 py-2 {{ $loop->last ? '' : 'border-bottom' }}" style="border-color:var(--lk-line)!important">
                        <i class="{{ $link->iconClass() ?: 'fa-solid fa-link' }} text-brand" style="width:20px"></i>
                        <div class="flex-fill" style="min-width:0">
                            <div class="fw-semibold text-truncate">{{ $link->title }}</div>
                            <div class="small text-muted text-truncate">{{ $link->url }}</div>
                        </div>
                        <span class="small text-muted text-nowrap">{{ number_format($link->clicks) }} clicks</span>
                        @include('admin.partials.status', ['s' => $link->is_blocked ? 'blocked' : ($link->is_active ? 'live' : 'hidden')])
                    </div>
                @empty
                    <div class="empty-state py-3">No links.</div>
                @endforelse
            </div>

            <div class="p-card">
                <div class="p-card-head"><h2>Recent activity</h2></div>
                @if ($activities->isEmpty())
                    <div class="empty-state py-3">No activity yet.</div>
                @else
                    <ul class="activity">
                        @foreach ($activities as $a)
                            <li><span class="a-ico {{ $a->tone() }}"><i class="{{ $a->icon() }}"></i></span><div><div class="a-txt">{{ $a->description }}</div><div class="a-time">{{ $a->created_at->diffForHumans() }}</div></div></li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <div class="col-xl-5">
            <div class="p-card mb-3">
                <h3 class="section-title">Plan</h3>
                @php($current = $subscriptions->first(fn ($s) => $s->isCurrent()))
                <p class="section-sub">Currently on <strong>{{ $current ? $current->plan->name : 'Free' }}</strong>{{ $current && $current->ends_at ? ' until ' . $current->ends_at->format('d M Y') : '' }}.</p>
                <form method="POST" action="{{ route('admin.users.plan', $user) }}" class="row g-2" novalidate>
                    @csrf
                    <div class="col-6">
                        <label class="form-label small" for="plan_id">Plan</label>
                        <select class="form-select" id="plan_id" name="plan_id">
                            @foreach ($plans as $p)<option value="{{ $p->id }}" {{ optional(optional($current)->plan)->id === $p->id ? 'selected' : '' }}>{{ $p->name }}{{ $p->is_active ? '' : ' (hidden)' }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small" for="billing_cycle">Period</label>
                        <select class="form-select" id="billing_cycle" name="billing_cycle">
                            <option value="monthly">1 month</option><option value="yearly">1 year</option><option value="lifetime">No end date</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small" for="amount">Amount received (₹)</label>
                        <input type="number" min="0" class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount" placeholder="0 if free">
                    </div>
                    <div class="col-6">
                        <label class="form-label small" for="method">Method</label>
                        <select class="form-select" id="method" name="method">
                            @foreach (\App\Models\Payment::METHODS as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <input type="text" class="form-control" name="reference" maxlength="100" placeholder="Payment reference (optional)" aria-label="Payment reference">
                    </div>
                    <div class="col-12"><button class="btn btn-primary btn-sm">Update plan</button></div>
                </form>
            </div>

            <div class="p-card mb-3">
                <h3 class="section-title mb-3">Subscriptions</h3>
                @forelse ($subscriptions as $s)
                    <div class="d-flex justify-content-between align-items-center py-2 small {{ $loop->last ? '' : 'border-bottom' }}" style="border-color:var(--lk-line)!important">
                        <div><strong>{{ $s->plan->name }}</strong> · {{ $s->billing_cycle }}<div class="text-muted">{{ $s->starts_at->format('d M Y') }} → {{ $s->ends_at ? $s->ends_at->format('d M Y') : 'no end' }}</div></div>
                        @include('admin.partials.status', ['s' => $s->isCurrent() ? 'current' : $s->displayStatus()])
                    </div>
                @empty
                    <div class="text-muted small">No subscriptions — Free plan.</div>
                @endforelse
            </div>

            <div class="p-card">
                <h3 class="section-title mb-3">Payments</h3>
                @forelse ($payments as $pay)
                    <div class="d-flex justify-content-between align-items-center py-2 small {{ $loop->last ? '' : 'border-bottom' }}" style="border-color:var(--lk-line)!important">
                        <div><strong>₹{{ number_format($pay->amount) }}</strong> · {{ \App\Models\Payment::METHODS[$pay->method] ?? $pay->method }}<div class="text-muted">{{ optional($pay->paid_at ?? $pay->created_at)->format('d M Y') }} {{ $pay->reference ? '· ' . $pay->reference : '' }}</div></div>
                        @include('admin.partials.status', ['s' => $pay->status])
                    </div>
                @empty
                    <div class="text-muted small">No payments recorded.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
