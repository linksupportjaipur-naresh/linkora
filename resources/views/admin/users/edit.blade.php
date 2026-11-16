@extends('layouts.admin')

@section('title', 'Edit ' . $user->name)
@section('page_title', 'Edit user')

@section('content')
    <a href="{{ route('admin.users.show', $user) }}" class="small fw-semibold d-inline-block mb-3"><i class="fa-solid fa-arrow-left me-1"></i>Back to user</a>

    <div class="p-card" style="max-width:760px">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="row g-3" novalidate>
            @csrf @method('PUT')
            <div class="col-md-6">
                <label class="form-label" for="name">Name</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" maxlength="100" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="email">Email</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" maxlength="150" required>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="username">Username</label>
                <input type="text" class="form-control @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username', $user->username) }}" maxlength="30" required>
                @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="role">Role</label>
                <select class="form-select @error('role') is-invalid @enderror" id="role" name="role">
                    <option value="user" {{ old('role', $user->role) === 'user' ? 'selected' : '' }}>User</option>
                    <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Admin</option>
                </select>
                @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label" for="status">Status</label>
                <select class="form-select" id="status" name="status">
                    @foreach (['active' => 'Active', 'suspended' => 'Blocked', 'deactivated' => 'Deactivated'] as $v => $l)
                        <option value="{{ $v }}" {{ old('status', $user->status) === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary">Save changes</button>
                <a href="{{ route('admin.users.show', $user) }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
@endsection
