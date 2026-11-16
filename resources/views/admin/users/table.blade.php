@if ($users->isEmpty())
    <div class="empty-state"><i class="fa-solid fa-user-group d-block"></i>No users found.</div>
@else
    <div class="table-responsive">
        <table class="table table-lk">
            <thead><tr><th>User</th><th>Page</th><th>Status</th>@unless ($compact ?? false)<th>Links</th>@endunless<th>Joined</th><th>Last login</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                @foreach ($users as $u)
                    <tr>
                        <td>
                            <a href="{{ route('admin.users.show', $u) }}" class="d-flex align-items-center gap-2 text-reset">
                                <span class="u-avatar" style="width:34px;height:34px;font-size:.75rem">{{ $u->initials() }}</span>
                                <span class="lh-sm" style="min-width:0">
                                    <span class="fw-semibold d-block text-truncate" style="max-width:220px">{{ $u->name }} @if ($u->isAdmin())<span class="badge-soft ms-1">Admin</span>@endif</span>
                                    <small class="text-muted d-block text-truncate" style="max-width:220px">{{ $u->email }}</small>
                                </span>
                            </a>
                        </td>
                        <td class="text-nowrap">
                            <a href="{{ $u->publicLink() }}" target="_blank" rel="noopener" class="text-muted">/{{ $u->username }}</a>
                            @if (optional($u->profile)->is_published)<i class="fa-solid fa-circle text-success ms-1" style="font-size:.5rem" title="Published"></i>@endif
                        </td>
                        <td>@include('admin.partials.status', ['s' => $u->status])</td>
                        @unless ($compact ?? false)<td>{{ $u->links_count ?? $u->links()->count() }}</td>@endunless
                        <td class="text-nowrap">{{ $u->created_at->format('d M Y') }}</td>
                        <td class="text-nowrap text-muted">{{ $u->last_login_at ? $u->last_login_at->diffForHumans() : 'Never' }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.users.show', $u) }}" class="btn btn-sm btn-ghost" title="View"><i class="fa-regular fa-eye"></i><span class="visually-hidden">View {{ $u->username }}</span></a>
                            <a href="{{ route('admin.users.edit', $u) }}" class="btn btn-sm btn-ghost" title="Edit"><i class="fa-solid fa-pen"></i><span class="visually-hidden">Edit {{ $u->username }}</span></a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
