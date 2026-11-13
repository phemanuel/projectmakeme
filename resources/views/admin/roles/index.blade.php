@extends('admin.makeme.layouts.app')

@section('title', 'Roles & Permissions')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/makeme-admin-roles.css') }}">
@endpush

@section('content')

@php
    $editablePermissions = collect($modules)
        ->flatMap(function ($details, $module) {
            return collect(array_keys($details['actions']))
                ->map(fn ($action) => "{$module}.{$action}");
        })
        ->values()
        ->all();
@endphp

<div class="rp-page">

    <div class="rp-page-header">
        <div>
            <h1>Roles & Permissions</h1>
            <p>
                Manage administrative roles and control
                access to Project MakeMe modules.
            </p>
        </div>

        <button type="button"
                class="rp-btn rp-btn-primary"
                data-rp-open="rpCreateModal">
            <span>＋</span>
            Create Role
        </button>
    </div>

    @if(session('success'))
        <div class="rp-alert rp-alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rp-alert rp-alert-error">
            <strong>Please correct the following:</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rp-summary">
        <div class="rp-stat">
            <small>Total Roles</small>
            <strong>{{ $roles->count() }}</strong>
        </div>

        <div class="rp-stat">
            <small>Editable Modules</small>
            <strong>{{ count($modules) }}</strong>
        </div>

        <div class="rp-stat">
            <small>Available Permissions</small>
            <strong>{{ count($editablePermissions) }}</strong>
        </div>
    </div>

    <div class="rp-card">
        <div class="rp-card-header">
            <div>
                <h2>Administrative Roles</h2>
                <p>
                    Define what each role can do
                    inside the admin backend.
                </p>
            </div>
        </div>

        <div class="rp-table-wrap">
            <table class="rp-table">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Assigned Users</th>
                        <th>Module Access</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($roles as $role)
                        @php
                            $isSuperAdmin = $role->name === 'Super Admin';

                            $rolePermissions = $role->permissions
                                ->pluck('name')
                                ->all();

                            $moduleCount = collect($modules)
                                ->filter(function ($details, $module) use ($rolePermissions) {
                                    foreach (array_keys($details['actions']) as $action) {
                                        if (in_array("{$module}.{$action}", $rolePermissions, true)) {
                                            return true;
                                        }
                                    }

                                    return false;
                                })
                                ->count();

                            $deletable = !$isSuperAdmin
                                && !in_array(
                                    $role->name,
                                    ['Administrator', 'Manager', 'Viewer'],
                                    true
                                )
                                && (int) $role->users_count === 0;
                        @endphp

                        <tr>
                            <td>
                                <div class="rp-role-info">
                                    <span class="rp-role-icon">
                                        {{ $isSuperAdmin ? '★' : '♙' }}
                                    </span>

                                    <div>
                                        <strong>{{ $role->name }}</strong>
                                        <small>
                                            {{ $isSuperAdmin
                                                ? 'Protected system role'
                                                : 'Administrative role' }}
                                        </small>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <span class="rp-count">
                                    {{ $role->users_count }}
                                </span>
                            </td>

                            <td>
                                @if($isSuperAdmin)
                                    <span class="rp-access rp-access-full">
                                        Full Access
                                    </span>
                                @elseif($moduleCount > 0)
                                    <span class="rp-access">
                                        {{ $moduleCount }} of {{ count($modules) }} modules
                                    </span>
                                @else
                                    <span class="rp-access rp-access-none">
                                        No MakeMe access
                                    </span>
                                @endif
                            </td>

                            <td>
                                @if($isSuperAdmin)
                                    <span class="rp-protected">
                                        🔒 Protected
                                    </span>
                                @else
                                    <div class="rp-actions">

                                        <button type="button"
                                                class="rp-action"
                                                data-rp-edit
                                                data-role-id="{{ $role->id }}"
                                                data-role-name="{{ $role->name }}"
                                                data-role-permissions="{{ implode(',', $rolePermissions) }}"
                                                data-url="{{ route('admin.roles.update', $role) }}">
                                            Edit Permissions
                                        </button>

                                        @if($deletable)
                                            <button type="button"
                                                    class="rp-action rp-action-danger"
                                                    data-rp-delete
                                                    data-role-name="{{ $role->name }}"
                                                    data-url="{{ route('admin.roles.destroy', $role) }}">
                                                Delete
                                            </button>
                                        @endif

                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="rp-empty">
                                No roles have been created yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rp-info-note">
        <strong>Super Admin protection</strong>
        <p>
            The Super Admin role has full administrative
            access and cannot be edited or deleted here.
            Admin Users and Role Management are reserved
            for Super Admins.
        </p>
    </div>

</div>


{{-- CREATE ROLE MODAL --}}
<div class="rp-modal-overlay"
     id="rpCreateModal"
     hidden>

    <div class="rp-modal"
         role="dialog"
         aria-modal="true"
         aria-labelledby="rpCreateTitle">

        <div class="rp-modal-header">
            <div>
                <h2 id="rpCreateTitle">Create Role</h2>
                <p>
                    Name the role and select its permissions.
                </p>
            </div>

            <button type="button"
                    class="rp-close"
                    data-rp-close
                    aria-label="Close">
                &times;
            </button>
        </div>

        <form method="POST"
              action="{{ route('admin.roles.store') }}"
              class="rp-form"
              data-rp-form>
            @csrf

            <input type="hidden"
                   name="_modal"
                   value="create">

            <div class="rp-modal-body">

                <div class="rp-field">
                    <label for="rpCreateName">
                        Role Name
                    </label>

                    <input type="text"
                           id="rpCreateName"
                           name="name"
                           value="{{ old('name') }}"
                           placeholder="e.g. Inventory Manager"
                           maxlength="100"
                           required>
                </div>

                <div class="rp-permission-heading">
                    <div>
                        <h3>Module Permissions</h3>
                        <p>
                            Select the actions this role can perform.
                        </p>
                    </div>
                    <button type="button"
                            class="rp-select-all"
                            data-rp-select-all>
                        Select All
                    </button>
                </div>

                @include('admin.roles.partials.permission-matrix', [
                    'modules' => $modules,
                    'prefix' => 'create',
                    'selected' => old('permissions', []),
                ])

            </div>

            <div class="rp-modal-footer">
                <button type="button"
                        class="rp-btn rp-btn-secondary"
                        data-rp-close>
                    Cancel
                </button>

                <button type="submit"
                        class="rp-btn rp-btn-primary">
                    Create Role
                </button>
            </div>
        </form>
    </div>
</div>


{{-- EDIT ROLE MODAL --}}
<div class="rp-modal-overlay"
     id="rpEditModal"
     hidden>

    <div class="rp-modal"
         role="dialog"
         aria-modal="true"
         aria-labelledby="rpEditTitle">

        <div class="rp-modal-header">
            <div>
                <h2 id="rpEditTitle">Edit Role</h2>
                <p>
                    Update the role and its module permissions.
                </p>
            </div>

            <button type="button"
                    class="rp-close"
                    data-rp-close
                    aria-label="Close">
                &times;
            </button>
        </div>

        <form method="POST"
              action=""
              id="rpEditForm"
              class="rp-form"
              data-rp-form>
            @csrf
            @method('PUT')

            <input type="hidden"
                   name="_modal"
                   value="edit">

            <input type="hidden"
                   name="_role_id"
                   id="rpEditRoleId">

            <div class="rp-modal-body">

                <div class="rp-field">
                    <label for="rpEditName">
                        Role Name
                    </label>

                    <input type="text"
                           id="rpEditName"
                           name="name"
                           maxlength="100"
                           required>
                </div>

                <div class="rp-permission-heading">
                    <div>
                        <h3>Module Permissions</h3>
                        <p>
                            Checked permissions will be assigned
                            to this role.
                        </p>
                    </div>

                    <button type="button"
                            class="rp-select-all"
                            data-rp-select-all>
                        Select All
                    </button>
                </div>

                @include('admin.roles.partials.permission-matrix', [
                    'modules' => $modules,
                    'prefix' => 'edit',
                    'selected' => [],
                ])

            </div>

            <div class="rp-modal-footer">
                <button type="button"
                        class="rp-btn rp-btn-secondary"
                        data-rp-close>
                    Cancel
                </button>

                <button type="submit"
                        class="rp-btn rp-btn-primary">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>


{{-- DELETE ROLE MODAL --}}
<div class="rp-modal-overlay"
     id="rpDeleteModal"
     hidden>

    <div class="rp-modal rp-modal-small"
         role="dialog"
         aria-modal="true"
         aria-labelledby="rpDeleteTitle">

        <div class="rp-modal-header">
            <div>
                <h2 id="rpDeleteTitle">Delete Role</h2>
                <p>This action cannot be undone.</p>
            </div>

            <button type="button"
                    class="rp-close"
                    data-rp-close
                    aria-label="Close">
                &times;
            </button>
        </div>

        <form method="POST" action="" id="rpDeleteForm">
            @csrf
            @method('DELETE')

            <div class="rp-modal-body">
                <p id="rpDeleteMessage"></p>
            </div>

            <div class="rp-modal-footer">
                <button type="button"
                        class="rp-btn rp-btn-secondary"
                        data-rp-close>
                    Cancel
                </button>

                <button type="submit"
                        class="rp-btn rp-btn-danger">
                    Delete Role
                </button>
            </div>
        </form>
    </div>
</div>

@if($errors->any())
    <div id="rpValidationData"
         hidden
         data-modal="{{ old('_modal') }}"
         data-role-id="{{ old('_role_id') }}"
         data-role-name="{{ old('name') }}"
         data-permissions="{{ implode(',', old('permissions', [])) }}">
    </div>
@endif

@endsection

@push('scripts')
<script src="{{ asset('js/makeme-admin-roles.js') }}"></script>
@endpush
