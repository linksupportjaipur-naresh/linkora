@extends('layouts.admin')

@section('title', 'Profiles')
@section('page_title', 'Profiles')

@section('content')
    @include('admin.partials.filters', [
        'action' => route('admin.profiles.index'), 'q' => $q, 'placeholder' => 'Search name, username or bio',
        'selects' => ['state' => [$state, ['' => 'All pages', 'published' => 'Published', 'hidden' => 'Hidden']]],
    ])

    <div class="p-card">
        <div class="p-card-head"><h2>{{ number_format($profiles->total()) }} {{ \Illuminate\Support\Str::plural('profile', $profiles->total()) }}</h2></div>
        @if ($profiles->isEmpty())
            <div class="empty-state"><i class="fa-regular fa-id-card d-block"></i>No profiles found.</div>
        @else
            <div class="table-responsive">
                <table class="table table-lk">
                    <thead><tr><th>Profile</th><th>Bio</th><th>Theme</th><th>Links</th><th>Views</th><th>Page</th><th class="text-end">Moderate</th></tr></thead>
                    <tbody>
                        @foreach ($profiles as $p)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($p->avatarUrl())
                                            <img src="{{ $p->avatarUrl() }}" alt="" class="rounded-circle" style="width:36px;height:36px;object-fit:cover" referrerpolicy="no-referrer">
                                        @else
                                            <span class="u-avatar" style="width:36px;height:36px;font-size:.75rem">{{ $p->initials() }}</span>
                                        @endif
                                        <div class="lh-sm">
                                            <a href="{{ route('admin.users.show', $p->user) }}" class="fw-semibold text-reset">{{ $p->name() }}</a>
                                            <div><a href="{{ $p->user->publicLink() }}" target="_blank" rel="noopener" class="small text-muted">/{{ $p->user->username }} <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:.65rem"></i></a></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="small text-muted" style="max-width:260px"><div class="text-truncate">{{ $p->bio ?: '—' }}</div></td>
                                <td class="small">{{ \App\Models\Theme::catalog(false)[$p->themeKey()]['name'] ?? $p->theme }}</td>
                                <td>{{ $linkCounts[$p->user_id] ?? 0 }}</td>
                                <td>{{ number_format($viewCounts[$p->user_id] ?? 0) }}</td>
                                <td>@include('admin.partials.status', ['s' => $p->user->isBlocked() ? 'blocked' : ($p->is_published ? 'published' : 'hidden')])</td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-ghost" data-bs-toggle="dropdown" aria-expanded="false">Actions <i class="fa-solid fa-chevron-down ms-1 small"></i></button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                @if ($p->is_published)
                                                    <form method="POST" action="{{ route('admin.profiles.unpublish', $p) }}">@csrf<button class="dropdown-item"><i class="fa-solid fa-eye-slash me-2"></i>Unpublish page</button></form>
                                                @else
                                                    <form method="POST" action="{{ route('admin.profiles.publish', $p) }}">@csrf<button class="dropdown-item"><i class="fa-solid fa-globe me-2"></i>Publish page</button></form>
                                                @endif
                                            </li>
                                            @if ($p->avatarUrl())
                                                <li><form method="POST" action="{{ route('admin.profiles.clear', $p) }}" data-confirm="Remove this profile photo?">@csrf<input type="hidden" name="field" value="avatar"><button class="dropdown-item"><i class="fa-regular fa-image me-2"></i>Remove photo</button></form></li>
                                            @endif
                                            @if ($p->bg_image_path)
                                                <li><form method="POST" action="{{ route('admin.profiles.clear', $p) }}" data-confirm="Remove this background image?">@csrf<input type="hidden" name="field" value="bg_image"><button class="dropdown-item"><i class="fa-regular fa-image me-2"></i>Remove background</button></form></li>
                                            @endif
                                            @if ($p->bio)
                                                <li><form method="POST" action="{{ route('admin.profiles.clear', $p) }}" data-confirm="Clear this bio?">@csrf<input type="hidden" name="field" value="bio"><button class="dropdown-item"><i class="fa-solid fa-eraser me-2"></i>Clear bio</button></form></li>
                                            @endif
                                            <li><a class="dropdown-item" href="{{ route('admin.links.index', ['q' => $p->user->username]) }}"><i class="fa-solid fa-link me-2"></i>Review links</a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $profiles->links() }}</div>
        @endif
    </div>
@endsection
