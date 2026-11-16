@extends('layouts.admin')

@section('title', 'Enquiries')
@section('page_title', 'Enquiries')

@section('content')
    @php($opts = ['' => 'All (' . $counts->sum() . ')'] + collect(\App\Http\Controllers\Admin\EnquiryController::STATUSES)->map(fn ($l, $k) => $l . ' (' . ($counts[$k] ?? 0) . ')')->all())
    @include('admin.partials.filters', [
        'action' => route('admin.enquiries.index'), 'q' => $q, 'placeholder' => 'Search name, email or message',
        'selects' => ['status' => [$status, $opts]],
    ])

    <div class="p-card">
        @if ($enquiries->isEmpty())
            <div class="empty-state"><i class="fa-regular fa-envelope-open d-block"></i>No enquiries found.</div>
        @else
            <div class="table-responsive">
                <table class="table table-lk">
                    <thead><tr><th>From</th><th>Topic</th><th>Message</th><th>Received</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($enquiries as $e)
                            <tr class="{{ $e->status === 'new' ? 'fw-semibold' : '' }}" style="cursor:pointer" onclick="location.href='{{ route('admin.enquiries.show', $e) }}'">
                                <td><a href="{{ route('admin.enquiries.show', $e) }}" class="text-reset">{{ $e->name }}</a><div class="small text-muted fw-normal">{{ $e->email }}</div></td>
                                <td class="text-nowrap">{{ $e->subject }}</td>
                                <td class="small text-muted fw-normal" style="max-width:320px"><div class="text-truncate">{{ $e->message }}</div></td>
                                <td class="text-nowrap small">{{ $e->created_at->format('d M Y, h:i A') }}</td>
                                <td>@include('admin.partials.status', ['s' => $e->status])</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $enquiries->links() }}</div>
        @endif
    </div>
@endsection
