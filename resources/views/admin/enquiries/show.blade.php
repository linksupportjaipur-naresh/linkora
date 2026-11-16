@extends('layouts.admin')

@section('title', 'Enquiry from ' . $enquiry->name)
@section('page_title', 'Enquiry')

@section('content')
    <a href="{{ route('admin.enquiries.index') }}" class="small fw-semibold d-inline-block mb-3"><i class="fa-solid fa-arrow-left me-1"></i>All enquiries</a>

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="p-card">
                <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                    <div>
                        <h2 class="h4 mb-1">{{ $enquiry->subject }}</h2>
                        <div class="text-muted small">{{ $enquiry->created_at->format('d M Y, h:i A') }} · {{ $enquiry->created_at->diffForHumans() }}</div>
                    </div>
                    @include('admin.partials.status', ['s' => $enquiry->status])
                </div>
                <div class="p-3 rounded-4" style="background:var(--lk-bg);white-space:pre-line;word-break:break-word">{{ $enquiry->message }}</div>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <a class="btn btn-primary" href="mailto:{{ $enquiry->email }}?subject={{ rawurlencode('Re: ' . $enquiry->subject) }}"><i class="fa-solid fa-reply me-1"></i>Reply by email</a>
                    <form method="POST" action="{{ route('admin.enquiries.update', $enquiry) }}" class="d-flex gap-2">
                        @csrf
                        <select name="status" class="form-select" aria-label="Status" style="width:auto">
                            @foreach (\App\Http\Controllers\Admin\EnquiryController::STATUSES as $k => $label)<option value="{{ $k }}" {{ $enquiry->status === $k ? 'selected' : '' }}>{{ $label }}</option>@endforeach
                        </select>
                        <button class="btn btn-ghost">Update</button>
                    </form>
                    <form method="POST" action="{{ route('admin.enquiries.destroy', $enquiry) }}" class="ms-auto" data-confirm="Delete this enquiry?">@csrf @method('DELETE')
                        <button class="btn btn-ghost text-danger"><i class="fa-regular fa-trash-can me-1"></i>Delete</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="p-card">
                <h3 class="section-title mb-3">Sender</h3>
                <dl class="mb-0 small">
                    <dt>Name</dt><dd>{{ $enquiry->name }}</dd>
                    <dt>Email</dt><dd><a href="mailto:{{ $enquiry->email }}">{{ $enquiry->email }}</a></dd>
                    @if ($enquiry->phone)<dt>Phone</dt><dd><a href="tel:{{ preg_replace('/[^0-9+]/', '', $enquiry->phone) }}">{{ $enquiry->phone }}</a></dd>@endif
                    @php($account = \App\Models\User::where('email', $enquiry->email)->first())
                    @if ($account)<dt>Account</dt><dd><a href="{{ route('admin.users.show', $account) }}">{{ '@' . $account->username }}</a></dd>@endif
                    <dt>IP address</dt><dd class="text-muted">{{ $enquiry->ip_address ?: '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>
@endsection
