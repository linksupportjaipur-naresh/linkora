@extends('layouts.admin')

@section('title', 'Users')
@section('page_title', 'Users')

@section('content')
    @include('admin.partials.filters', [
        'action' => route('admin.users.index'), 'q' => $q, 'placeholder' => 'Search name, email or username',
        'selects' => [
            'status' => [$status, ['' => 'All statuses', 'active' => 'Active', 'suspended' => 'Blocked', 'deactivated' => 'Deactivated']],
            'role'   => [$role, ['' => 'All roles', 'user' => 'Users', 'admin' => 'Admins']],
        ],
    ])

    <div class="p-card">
        <div class="p-card-head"><h2>{{ number_format($users->total()) }} {{ \Illuminate\Support\Str::plural('user', $users->total()) }}</h2></div>
        @include('admin.users.table', ['users' => $users])
        <div class="mt-3">{{ $users->links() }}</div>
    </div>
@endsection
