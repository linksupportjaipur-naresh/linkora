@extends('layouts.admin')

@section('title', 'Subscriptions')
@section('page_title', 'Subscriptions')

@section('content')
    <div class="page-head">
        <div>
            <h2>Subscriptions</h2>
            <p>{{ number_format($currentCount) }} active paid {{ \Illuminate\Support\Str::plural('plan', $currentCount) }}. Assign a plan from a user’s page; online payments can be connected later.</p>
        </div>
        <nav class="range-tabs" aria-label="Filter subscriptions">
            @foreach (['' => 'All', 'current' => 'Active', 'expired' => 'Expired', 'cancelled' => 'Cancelled'] as $k => $label)
                <a href="{{ route('admin.subscriptions.index', array_filter(['status' => $k])) }}" class="{{ (string) $status === (string) $k ? 'active' : '' }}">{{ $label }}</a>
            @endforeach
        </nav>
    </div>

    <div class="p-card">
        @if ($subscriptions->isEmpty())
            <div class="empty-state"><i class="fa-solid fa-arrows-rotate d-block"></i>No subscriptions yet. Open a user and use “Update plan” to give them a paid plan.</div>
        @else
            <div class="table-responsive">
                <table class="table table-lk">
                    <thead><tr><th>User</th><th>Plan</th><th>Period</th><th>Started</th><th>Ends</th><th>Source</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($subscriptions as $s)
                            <tr>
                                <td>@if ($s->user)<a href="{{ route('admin.users.show', $s->user) }}">{{ '@' . $s->user->username }}</a>@else<span class="text-muted">Deleted user</span>@endif</td>
                                <td class="fw-semibold">{{ optional($s->plan)->name }}</td>
                                <td>{{ ucfirst($s->billing_cycle) }}</td>
                                <td class="text-nowrap">{{ $s->starts_at->format('d M Y') }}</td>
                                <td class="text-nowrap">{{ $s->ends_at ? $s->ends_at->format('d M Y') : '—' }}</td>
                                <td>{{ ucfirst($s->source) }}</td>
                                <td>@include('admin.partials.status', ['s' => $s->isCurrent() ? 'current' : $s->displayStatus()])</td>
                                <td class="text-end">
                                    @if ($s->isCurrent())
                                        <form method="POST" action="{{ route('admin.subscriptions.cancel', $s) }}" data-confirm="Cancel this subscription now? The user goes back to the Free plan.">@csrf<button class="btn btn-sm btn-ghost text-danger">Cancel</button></form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $subscriptions->links() }}</div>
        @endif
    </div>
@endsection
