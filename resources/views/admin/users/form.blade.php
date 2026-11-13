
@extends('makeme.layouts.app')

@section('content')
@php
    $editing = isset($user);

    $action = $editing
        ? route('admin.users.update', $user)
        : route('admin.users.store');
@endphp

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold">
                {{ $editing ? 'Edit Administrator' : 'Add Administrator' }}
            </h3>
            <p class="text-muted">
                {{ $editing
                    ? 'Update administrator information and access role.'
                    : 'Create a new administrative account.' }}
            </p>
        </div>

        <a href="{{ route('admin.users.index') }}"
           class="btn btn-outline-secondary">
            Back to Admin Users
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ $action }}">
                @csrf

                @if($editing)
                    @method('PUT')
                @endif

                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">First Name</label>
                        <input type="text"
                               name="first_name"
                               class="form-control"
                               value="{{ old('first_name', $user->first_name ?? '') }}"
                               required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Surname</label>
                        <input type="text"
                               name="surname"
                               class="form-control"
                               value="{{ old('surname', $user->surname ?? '') }}"
                               required>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Email Address</label>
                        <input type="email"
                               name="email"
                               class="form-control"
                               value="{{ old('email', $user->email ?? '') }}"
                               required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            {{ $editing ? 'New Password (optional)' : 'Password' }}
                        </label>
                        <input type="password"
                               name="password"
                               class="form-control"
                               autocomplete="new-password"
                               {{ $editing ? '' : 'required' }}>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Confirm Password</label>
                        <input type="password"
                               name="password_confirmation"
                               class="form-control"
                               autocomplete="new-password"
                               {{ $editing ? '' : 'required' }}>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">
                            Administrator Role
                        </label>

                        <select name="role"
                                class="form-select"
                                required>

                            <option value="">Select Role</option>

                            @foreach($roles as $role)
                                <option value="{{ $role->name }}"
                                    @selected(
                                        old(
                                            'role',
                                            $editing ? $user->roles->first()?->name : ''
                                        ) === $role->name
                                    )>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>

                        <small class="text-muted">
                            Role permissions will determine which
                            backend modules this administrator can use.
                        </small>
                    </div>

                </div>

                <hr class="my-4">

                <button type="submit"
                        class="btn btn-primary px-4">
                    {{ $editing
                        ? 'Save Changes'
                        : 'Create Administrator' }}
                </button>

            </form>
        </div>
    </div>
</div>
@endsection
