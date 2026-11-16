@extends('layouts.admin')

@section('title', 'Payments')
@section('page_title', 'Payments')

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-xl-3"><div class="p-card stat-card"><div class="ico green"><i class="fa-solid fa-indian-rupee-sign"></i></div><div><div class="val">₹{{ number_format($revenue) }}</div><div class="lbl">Total received</div></div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="p-card stat-card"><div class="ico"><i class="fa-regular fa-calendar"></i></div><div><div class="val">₹{{ number_format($month) }}</div><div class="lbl">This month</div></div></div></div>
    </div>

    <div class="p-card">
        <div class="p-card-head">
            <h2>Payment records</h2>
            <nav class="range-tabs" aria-label="Filter payments">
                @foreach (['' => 'All', 'paid' => 'Paid', 'pending' => 'Pending', 'refunded' => 'Refunded', 'failed' => 'Failed'] as $k => $label)
                    <a href="{{ route('admin.payments.index', array_filter(['status' => $k])) }}" class="{{ (string) $status === (string) $k ? 'active' : '' }}">{{ $label }}</a>
                @endforeach
            </nav>
        </div>
        @if ($payments->isEmpty())
            <div class="empty-state"><i class="fa-solid fa-receipt d-block"></i>No payments recorded yet.</div>
        @else
            <div class="table-responsive">
                <table class="table table-lk">
                    <thead><tr><th>Date</th><th>User</th><th>Plan</th><th>Amount</th><th>Method</th><th>Reference</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($payments as $p)
                            <tr>
                                <td class="text-nowrap">{{ optional($p->paid_at ?? $p->created_at)->format('d M Y') }}</td>
                                <td>@if ($p->user)<a href="{{ route('admin.users.show', $p->user) }}">{{ '@' . $p->user->username }}</a>@else<span class="text-muted">Deleted user</span>@endif</td>
                                <td>{{ optional(optional($p->subscription)->plan)->name ?? '—' }}</td>
                                <td class="fw-semibold">₹{{ number_format($p->amount) }}</td>
                                <td>{{ \App\Models\Payment::METHODS[$p->method] ?? $p->method }}</td>
                                <td class="small text-muted">{{ $p->reference ?: '—' }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.payments.update', $p) }}">
                                        @csrf
                                        <select name="status" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()" aria-label="Payment status">
                                            @foreach (\App\Models\Payment::STATUSES as $st)<option value="{{ $st }}" {{ $p->status === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>@endforeach
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $payments->links() }}</div>
        @endif
    </div>
@endsection
