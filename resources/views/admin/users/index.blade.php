@extends('admin.makeme.layouts.app')

@section('title', 'Admin Users')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/makeme-admin-users.css') }}">
@endpush

@section('content')
<div class="au-page">

    <div class="au-page-header">
        <div>
            <h1>Admin Users</h1>
            <p>Manage administrator accounts, roles and access.</p>
        </div>

        <button type="button"
                class="au-btn au-btn-primary"
                data-open-modal="createAdminModal">
            <span>＋</span> Add Administrator
        </button>
    </div>

    @if(session('success'))
        <div class="au-alert au-alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="au-alert au-alert-error">
            <strong>Please correct the following:</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="au-card">
        <div class="au-card-header">
            <div>
                <h2>Administrators</h2>
                <p>{{ $users->total() }} administrative accounts</p>
            </div>
        </div>

        <div class="au-table-wrap">
            <table class="au-table">
                <thead>
                    <tr>
                        <th>Administrator</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($users as $user)
                        @php
                            $protected = (
                                (int) $user->id === (int) auth()->id()
                                || $user->hasRole('Super Admin')
                            );

                            $roleName = $user->roles->first()?->name ?? '';
                            $active = (int) $user->user_status === 1;
                        @endphp

                        <tr>
                            <td>
                                <div class="au-user">
                                    <span class="au-avatar">
                                        {{ strtoupper(substr($user->first_name ?: 'A', 0, 1)) }}
                                    </span>

                                    <div>
                                        <strong>
                                            {{ $user->first_name }}
                                            {{ $user->surname }}
                                        </strong>
                                        <small>{{ $user->email }}</small>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <span class="au-role">
                                    {{ $roleName ?: 'Unassigned' }}
                                </span>
                            </td>

                            <td>
                                <span class="au-status {{ $active ? 'is-active' : 'is-inactive' }}">
                                    <span class="au-status-dot"></span>
                                    {{ $active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>

                            <td>
                                @if($protected)
                                    <span class="au-protected">
                                        🔒 Protected
                                    </span>
                                @else
                                    <div class="au-actions">
                                        <button
                                            type="button"
                                            class="au-action-btn"
                                            data-edit-user
                                            data-id="{{ $user->id }}"
                                            data-first-name="{{ $user->first_name }}"
                                            data-surname="{{ $user->surname }}"
                                            data-email="{{ $user->email }}"
                                            data-role="{{ $roleName }}"
                                            data-url="{{ route('admin.users.update', $user) }}">
                                            Edit
                                        </button>

                                        <button
                                            type="button"
                                            class="au-action-btn {{ $active ? 'au-danger-text' : 'au-success-text' }}"
                                            data-status-user
                                            data-name="{{ $user->first_name }} {{ $user->surname }}"
                                            data-action="{{ $active ? 'deactivate' : 'activate' }}"
                                            data-url="{{ route('admin.users.status', $user) }}">
                                            {{ $active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="au-empty">
                                No administrative accounts found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="au-pagination">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>


{{-- CREATE ADMINISTRATOR MODAL --}}
<div class="au-modal-overlay"
     id="createAdminModal"
     role="presentation"
     hidden>

    <div class="au-modal"
         role="dialog"
         aria-modal="true"
         aria-labelledby="createAdminTitle">

        <div class="au-modal-header">
            <div>
                <h2 id="createAdminTitle">Add Administrator</h2>
                <p>Create a new administrative account.</p>
            </div>

            <button type="button"
                    class="au-close"
                    data-close-modal
                    aria-label="Close">
                &times;
            </button>
        </div>

        <form method="POST"
              action="{{ route('admin.users.store') }}"
              class="au-modal-form"
              data-admin-form>
            @csrf
            <input type="hidden" name="_modal" value="create">

            <div class="au-modal-body">
                <div class="au-form-grid">
                    <div class="au-field">
                        <label for="createFirstName">First Name</label>
                        <input id="createFirstName"
                               type="text"
                               name="first_name"
                               value="{{ old('first_name') }}"
                               required>
                    </div>

                    <div class="au-field">
                        <label for="createSurname">Surname</label>
                        <input id="createSurname"
                               type="text"
                               name="surname"
                               value="{{ old('surname') }}"
                               required>
                    </div>

                    <div class="au-field au-full">
                        <label for="createEmail">Email Address</label>
                        <input id="createEmail"
                               type="email"
                               name="email"
                               value="{{ old('email') }}"
                               required>
                    </div>

                    <div class="au-field au-full">
                        <label for="createRole">Administrator Role</label>
                        <select id="createRole" name="role" required>
                            <option value="">Select role</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}"
                                    @selected(old('role') === $role->name)>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                   <div class="au-field">
                        <label for="createPassword">Password</label>

                        <div class="au-password-wrapper">
                            <input
                                id="createPassword"
                                type="password"
                                name="password"
                                minlength="10"
                                autocomplete="new-password"
                                required
                            >

                            <button type="button"
                                    class="au-password-toggle"
                                    data-toggle-password="createPassword"
                                    aria-label="Show password"
                                    aria-pressed="false">
                                <span class="au-eye-icon"></span>
                            </button>
                        </div>
                    </div>

                    <div class="au-field">
                        <label for="createPasswordConfirmation">
                            Confirm Password
                        </label>

                        <div class="au-password-wrapper">
                            <input
                                id="createPasswordConfirmation"
                                type="password"
                                name="password_confirmation"
                                minlength="10"
                                autocomplete="new-password"
                                required
                            >

                            <button type="button"
                                    class="au-password-toggle"
                                    data-toggle-password="createPasswordConfirmation"
                                    aria-label="Show password"
                                    aria-pressed="false">
                                <span class="au-eye-icon"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="au-modal-footer">
                <button type="button"
                        class="au-btn au-btn-secondary"
                        data-close-modal>
                    Cancel
                </button>
                <button type="submit"
                        class="au-btn au-btn-primary">
                    Create Administrator
                </button>
            </div>
        </form>
    </div>
</div>


{{-- EDIT ADMINISTRATOR MODAL --}}
<div class="au-modal-overlay"
     id="editAdminModal"
     role="presentation"
     hidden>

    <div class="au-modal"
         role="dialog"
         aria-modal="true"
         aria-labelledby="editAdminTitle">

        <div class="au-modal-header">
            <div>
                <h2 id="editAdminTitle">Edit Administrator</h2>
                <p>Update account information and role.</p>
            </div>

            <button type="button"
                    class="au-close"
                    data-close-modal
                    aria-label="Close">
                &times;
            </button>
        </div>

        <form method="POST"
              action=""
              id="editAdminForm"
              class="au-modal-form"
              data-admin-form>
            @csrf
            @method('PUT')
            <input type="hidden" name="_modal" value="edit">

            <input type="hidden"
                name="_admin_id"
                id="editAdminId">

            <div class="au-modal-body">
                <div class="au-form-grid">
                    <div class="au-field">
                        <label for="editFirstName">First Name</label>
                        <input id="editFirstName"
                               type="text"
                               name="first_name"
                               required>
                    </div>

                    <div class="au-field">
                        <label for="editSurname">Surname</label>
                        <input id="editSurname"
                               type="text"
                               name="surname"
                               required>
                    </div>

                    <div class="au-field au-full">
                        <label for="editEmail">Email Address</label>
                        <input id="editEmail"
                               type="email"
                               name="email"
                               required>
                    </div>

                    <div class="au-field au-full">
                        <label for="editRole">Administrator Role</label>
                        <select id="editRole" name="role" required>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}">
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="au-field">
                        <label for="editPassword">New Password</label>

                        <div class="au-password-wrapper">
                            <input
                                id="editPassword"
                                type="password"
                                name="password"
                                minlength="10"
                                autocomplete="new-password"
                                placeholder="Leave blank to keep current"
                            >

                            <button type="button"
                                    class="au-password-toggle"
                                    data-toggle-password="editPassword"
                                    aria-label="Show password"
                                    aria-pressed="false">
                                <span class="au-eye-icon"></span>
                            </button>
                        </div>
                    </div>

                    <div class="au-field">
                        <label for="editPasswordConfirmation">
                            Confirm New Password
                        </label>

                        <div class="au-password-wrapper">
                            <input
                                id="editPasswordConfirmation"
                                type="password"
                                name="password_confirmation"
                                minlength="10"
                                autocomplete="new-password"
                                placeholder="Only if changing password"
                            >

                            <button type="button"
                                    class="au-password-toggle"
                                    data-toggle-password="editPasswordConfirmation"
                                    aria-label="Show password"
                                    aria-pressed="false">
                                <span class="au-eye-icon"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="au-modal-footer">
                <button type="button"
                        class="au-btn au-btn-secondary"
                        data-close-modal>
                    Cancel
                </button>
                <button type="submit"
                        class="au-btn au-btn-primary">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>


{{-- ACCOUNT STATUS CONFIRMATION MODAL --}}
<div class="au-modal-overlay"
     id="statusAdminModal"
     role="presentation"
     hidden>

    <div class="au-modal au-modal-small"
         role="dialog"
         aria-modal="true"
         aria-labelledby="statusAdminTitle">

        <div class="au-modal-header">
            <div>
                <h2 id="statusAdminTitle">Confirm Account Action</h2>
                <p id="statusAdminMessage"></p>
            </div>

            <button type="button"
                    class="au-close"
                    data-close-modal
                    aria-label="Close">
                &times;
            </button>
        </div>

        <form method="POST" action="" id="statusAdminForm">
            @csrf
            @method('PATCH')

            <div class="au-modal-footer">
                <button type="button"
                        class="au-btn au-btn-secondary"
                        data-close-modal>
                    Cancel
                </button>

                <button type="submit"
                        id="statusAdminSubmit"
                        class="au-btn au-btn-primary">
                    Confirm
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')

@if($errors->any())
    <div id="adminValidationData"
         hidden
         data-modal="{{ old('_modal') }}"
         data-admin-id="{{ old('_admin_id') }}"
         data-first-name="{{ old('first_name') }}"
         data-surname="{{ old('surname') }}"
         data-email="{{ old('email') }}"
         data-role="{{ old('role') }}">
    </div>
@endif

<script src="{{ asset('js/makeme-admin-users.js') }}"></script>

@endpush
