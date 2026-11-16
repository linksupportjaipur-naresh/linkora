@extends('layouts.admin')

@section('title', 'Links')
@section('page_title', 'Links')

@section('content')
    @include('admin.partials.filters', [
        'action' => route('admin.links.index'), 'q' => $q, 'placeholder' => 'Search title, URL or domain',
        'selects' => ['state' => [$state, ['' => 'All links', 'live' => 'Live', 'hidden' => 'Hidden by user', 'blocked' => 'Blocked']]],
    ])

    <div class="p-card">
        <div class="p-card-head">
            <h2>{{ number_format($links->total()) }} {{ \Illuminate\Support\Str::plural('link', $links->total()) }}</h2>
            <span class="small text-muted">Blocked links disappear from public pages; the owner sees your reason.</span>
        </div>
        @if ($links->isEmpty())
            <div class="empty-state"><i class="fa-solid fa-link d-block"></i>No links found.</div>
        @else
            <div class="table-responsive">
                <table class="table table-lk">
                    <thead><tr><th>Link</th><th>Owner</th><th>Clicks</th><th>Status</th><th class="text-end">Moderate</th></tr></thead>
                    <tbody>
                        @foreach ($links as $link)
                            <tr>
                                <td style="max-width:380px">
                                    <div class="fw-semibold text-truncate"><i class="{{ $link->iconClass() ?: 'fa-solid fa-link' }} text-brand me-1"></i>{{ $link->title }}</div>
                                    <a href="{{ $link->url }}" target="_blank" rel="noopener nofollow noreferrer" class="small text-muted d-block text-truncate">{{ $link->url }}</a>
                                    @if ($link->is_blocked && $link->blocked_reason)<div class="small text-danger"><i class="fa-solid fa-ban me-1"></i>{{ $link->blocked_reason }}</div>@endif
                                </td>
                                <td class="text-nowrap">@if ($link->user)<a href="{{ route('admin.users.show', $link->user) }}">{{ '@' . $link->user->username }}</a>@endif</td>
                                <td>{{ number_format($link->clicks) }}</td>
                                <td>@include('admin.partials.status', ['s' => $link->is_blocked ? 'blocked' : ($link->is_active ? 'live' : 'hidden')])</td>
                                <td class="text-end text-nowrap">
                                    @if ($link->is_blocked)
                                        <form method="POST" action="{{ route('admin.links.unblock', $link) }}" class="d-inline">@csrf<button class="btn btn-sm btn-ghost"><i class="fa-solid fa-unlock me-1"></i>Unblock</button></form>
                                    @else
                                        <button type="button" class="btn btn-sm btn-ghost text-danger" data-block-link="{{ route('admin.links.block', $link) }}" data-title="{{ $link->title }}"><i class="fa-solid fa-ban me-1"></i>Block</button>
                                    @endif
                                    <form method="POST" action="{{ route('admin.links.destroy', $link) }}" class="d-inline" data-confirm="Delete this link permanently?">@csrf @method('DELETE')
                                        <button class="btn btn-sm btn-ghost" title="Delete"><i class="fa-regular fa-trash-can"></i><span class="visually-hidden">Delete</span></button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $links->links() }}</div>
        @endif
    </div>

    <div class="modal fade" id="block-modal" tabindex="-1" aria-labelledby="block-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content" id="block-form">
                @csrf
                <div class="modal-header"><h2 class="modal-title h5" id="block-title">Block link</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body">
                    <p class="text-muted small">“<span id="block-name"></span>” will be hidden from the public page and the owner can’t switch it back on.</p>
                    <label class="form-label" for="reason">Reason shown to the owner</label>
                    <select class="form-select mb-2" id="reason-preset" aria-label="Common reasons">
                        <option value="">Choose a common reason…</option>
                        <option>Spam or misleading link</option>
                        <option>Malware or phishing</option>
                        <option>Adult or violent content</option>
                        <option>Hate speech or harassment</option>
                        <option>Copyright infringement</option>
                    </select>
                    <input type="text" class="form-control" id="reason" name="reason" maxlength="200" required placeholder="Reason">
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger rounded-pill px-4">Block link</button></div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    var blockModal = new bootstrap.Modal('#block-modal');
    $('[data-block-link]').on('click', function () {
        $('#block-form').attr('action', $(this).data('block-link'));
        $('#block-name').text($(this).data('title'));
        $('#reason').val(''); $('#reason-preset').val('');
        blockModal.show();
    });
    $('#reason-preset').on('change', function () { if (this.value) $('#reason').val(this.value); });
    @if ($errors->has('reason')) lkToast(@json($errors->first('reason')), 'error'); @endif
</script>
@endpush
