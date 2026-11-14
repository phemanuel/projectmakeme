@extends('admin.makeme.layouts.app')
@section('title', 'Participants')
@section('content')
@php
    $admin = auth('web')->user();
    $isSuperAdmin = $admin->hasRole('Super Admin', 'web');
    $canCreate = $isSuperAdmin || $admin->can('makeme.participants.create');
    $canEdit = $isSuperAdmin || $admin->can('makeme.participants.edit');
    $canImport = $isSuperAdmin || $admin->can('makeme.participants.import');
    $canExport = $isSuperAdmin || $admin->can('makeme.participants.export');
    $canInvite = $isSuperAdmin || $admin->can('makeme.participants.invite');
    $canDelete = $isSuperAdmin || $admin->can('makeme.participants.delete');
    $fields = [
        'first_name' => ['First name', 'text', true, 100],
        'surname' => ['Surname', 'text', true, 100],
        'other_name' => ['Other name', 'text', false, 100],
        'email' => ['Email address', 'email', true, 255],
        'mobile_no' => ['Phone number', 'tel', true, 25],
        'alternative_email' => ['Alternative email', 'email', false, 255],
        'business_name' => ['Business name', 'text', false, 180],
        'profession' => ['Profession', 'text', false, 150],
        'age' => ['Age', 'number', false, 3],
        'city' => ['City / Location', 'text', false, 120],
        'state_of_origin' => ['State of origin', 'text', false, 120],
        'instagram_handle' => ['Instagram', 'text', false, 150],
        'tiktok_handle' => ['TikTok', 'text', false, 150],
        'facebook_handle' => ['Facebook', 'text', false, 255],
        'x_handle' => ['X (Twitter)', 'text', false, 255],
        'linkedin_handle' => ['LinkedIn', 'text', false, 255],
    ];
    $sections = [
        'Personal & contact details' => ['first_name', 'surname', 'other_name', 'email', 'mobile_no', 'alternative_email', 'age', 'city', 'state_of_origin'],
        'Business & show information' => ['business_name', 'profession'],
        'Social media accounts' => ['instagram_handle', 'tiktok_handle', 'facebook_handle', 'x_handle', 'linkedin_handle'],
    ];
@endphp
<section class="page-intro">
    <div><div class="intro-kicker"><span class="live-dot"></span> SHOW OPERATIONS / PARTICIPANTS</div><h1>Participant Management<span class="gold-period">.</span></h1><p>Manage independent Project MakeMe Live contestants.</p></div>
    <div class="intro-actions">
        @if($canExport)<a class="btn btn-outline" href="{{ route('admin.makeme.participants.export', request()->only(['search','skill','status'])) }}">↓ Export CSV</a>@endif
        @if($canImport)<button type="button" class="btn btn-outline" data-open-modal="importModal">↥ Import Excel</button>@endif
        @if($canCreate)<button type="button" class="btn btn-gold" data-open-modal="addModal">+ Add participant</button>@endif
    </div>
</section>
@if(session('success'))<div class="notice success">✓ {{ session('success') }}</div>@endif
@if(session('error'))<div class="notice error">{{ session('error') }}</div>@endif
@if($errors->any())<div class="notice error"><strong>Check the information below:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="stat-grid">
    <article class="stat-card"><span class="stat-label">ALL PARTICIPANTS</span><div class="stat-value">{{ number_format($stats['total']) }}</div><span class="stat-detail">Registered for the show</span></article>
    <article class="stat-card"><span class="stat-label">ACTIVE</span><div class="stat-value">{{ number_format($stats['active']) }}</div><span class="stat-detail">Approved accounts</span></article>
    <article class="stat-card"><span class="stat-label">INACTIVE</span><div class="stat-value">{{ number_format($stats['inactive']) }}</div><span class="stat-detail">Access suspended</span></article>
</div>
<section class="panel">
    <div class="panel-heading"><div><h2>Contestant directory</h2><p>View and manage profiles without using the NYFEW users table.</p></div><span class="record-count">{{ $participants->total() }} records</span></div>
    <form class="filters" method="GET" action="{{ route('admin.makeme.participants.index') }}">
        <input name="search" type="search" placeholder="Name, business, email or participant number" value="{{ request('search') }}" aria-label="Search participants">
        <select name="skill" aria-label="Skill category"><option value="">All skills</option>@foreach($skills as $value => $label)<option value="{{ $value }}" @selected(request('skill') === $value)>{{ $label }}</option>@endforeach</select>
        <select name="status" aria-label="Status"><option value="">All statuses</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="inactive" @selected(request('status') === 'inactive')>Inactive</option></select>
        <button class="btn btn-gold" type="submit">Apply filters</button>
        <a class="btn btn-outline" href="{{ route('admin.makeme.participants.index') }}">Clear</a>
    </form>
    <div class="table-scroll"><table class="participant-table"><thead><tr><th>PARTICIPANT</th><th>NUMBER</th><th>SKILL</th><th>STATUS</th><th>ACTIVATION</th><th class="right">ACTIONS</th></tr></thead><tbody>
    @forelse($participants as $participant)
        @php
            $profile = $participant->only(array_keys($fields));
            $profile['id'] = $participant->id;
            $profile['skill_category'] = $participant->skill_category;
            $profile['status'] = $participant->status;
            $profile['photo_url'] = $participant->photo_url;
            $profile['participant_number'] = $participant->participant_number;
            $profile['full_name'] = $participant->full_name;
        @endphp
        <tr>
            <td><div class="participant-cell"><div class="initial-circle mp-avatar">
                @if($participant->photo_url)<img src="{{ $participant->photo_url }}" alt="{{ $participant->full_name }}" loading="lazy">@else{{ mb_strtoupper(mb_substr($participant->first_name ?: 'P', 0, 1)) }}@endif
                </div><div><strong>{{ $participant->full_name }}</strong><small>{{ $participant->business_name ?: $participant->email }}</small></div></div></td>
            <td><code class="participant-code">{{ $participant->participant_number }}</code></td>
            <td>{{ $skills[$participant->skill_category] ?? $participant->skill_category }}</td>
            <td><span class="status {{ $participant->status === 'active' ? 'status-active' : 'status-inactive' }}">{{ ucfirst($participant->status) }}</span></td>
            <td>{{ $participant->email_verified_at ? 'Activated' : 'Not activated' }}</td>
            
            <td class="right">
                <div class="mp-actions">

                    {{-- QUICK VIEW --}}
                    <button
                        type="button"
                        class="table-action js-profile"
                        data-profile='@json($profile)'
                    >
                        View
                    </button>

                    {{-- THREE-DOT MENU --}}
                    <div class="mp-action-menu">

                        <button
                            type="button"
                            class="mp-action-trigger"
                            aria-label="More actions for {{ $participant->full_name }}"
                            aria-haspopup="true"
                            aria-expanded="false"
                            title="More actions"
                        >
                            &#8942;
                        </button>

                        <div class="mp-action-dropdown" hidden>

                            {{-- EDIT --}}
                            @if($canEdit)
                                <button
                                    type="button"
                                    class="mp-action-item js-edit"
                                    data-url="{{ route('admin.makeme.participants.update', $participant) }}"
                                    data-first="{{ $participant->first_name }}"
                                    data-last="{{ $participant->surname }}"
                                    data-other="{{ $participant->other_name }}"
                                    data-email="{{ $participant->email }}"
                                    data-phone="{{ $participant->mobile_no }}"
                                    data-skill="{{ $participant->skill_category }}"
                                    data-status="{{ $participant->status }}"
                                    data-profile='@json($profile)'
                                >
                                    <span>✎</span>
                                    Edit Participant
                                </button>
                            @endif

                            {{-- SEND / RESEND INVITE --}}
                            @if(
                                $canInvite &&
                                $participant->status === 'active' &&
                                !$participant->email_verified_at
                            )
                                <form
                                    method="POST"
                                    action="{{ route('admin.makeme.participants.invite', $participant) }}"
                                    onsubmit="return confirm('Send an invitation to this participant?');"
                                >
                                    @csrf

                                    <button type="submit" class="mp-action-item">
                                        <span>✉</span>
                                        Send / Resend Invite
                                    </button>
                                </form>
                            @endif

                            {{-- GENERATE ACTIVATION LINK --}}
                            @if(
                                $canInvite &&
                                $participant->status === 'active' &&
                                empty($participant->password)
                            )
                                <form
                                    method="POST"
                                    action="{{ route('admin.makeme.participants.activation-link', $participant) }}"
                                >
                                    @csrf

                                    <button type="submit" class="mp-action-item">
                                        <span>🔗</span>
                                        Generate Activation Link
                                    </button>
                                </form>
                            @endif

                            
                            {{-- RESET PARTICIPANT PASSWORD --}}
                            @if(
                                $canInvite &&
                                $participant->status === 'active' &&
                                !empty($participant->password)
                            )
                                <form
                                    method="POST"
                                    action="{{ route('admin.makeme.participants.reset-password-link', $participant) }}"
                                    onsubmit="return confirm('Generate a new password reset link for this contestant?');"
                                >
                                    @csrf

                                    <button
                                        type="submit"
                                        class="mp-action-item"
                                        title="Generate password reset link"
                                    >
                                        <span>
                                            <i class="fas fa-key"></i>
                                        </span>

                                        Reset Password
                                    </button>
                                </form>
                            @endif


                            {{-- DELETE --}}
                            @if($canDelete)
                                <div class="mp-action-divider"></div>

                                <button
                                    type="button"
                                    class="mp-action-item mp-action-danger js-delete-participant"
                                    data-url="{{ route('admin.makeme.participants.destroy', $participant) }}"
                                    data-name="{{ $participant->full_name }}"
                                >
                                    <span>🗑</span>
                                    Delete Participant
                                </button>
                            @endif

                        </div>

                    </div>
                </div>
            </td>

        </tr>
    @empty
        <tr><td colspan="6"><div class="empty-state"><strong>No participants found</strong><p>Add a participant or adjust your filters.</p></div></td></tr>
    @endforelse
    </tbody></table></div>
    <div class="pagination-bar"><span>{{ $participants->firstItem() ?? 0 }}–{{ $participants->lastItem() ?? 0 }} of {{ $participants->total() }}</span><div class="pagination-buttons">{{ $participants->onEachSide(1)->links() }}</div></div>
</section>

{{-- CREATE AND EDIT MODALS: keep familiar selectors for the existing JS. --}}
@foreach(['add' => 'New contestant', 'edit' => 'Edit contestant'] as $mode => $heading)
    @php $isEdit = $mode === 'edit'; @endphp
    @if(!$isEdit && !$canCreate) @continue @endif
    @if($isEdit && !$canEdit) @continue @endif
    <div class="modal-backdrop" id="{{ $isEdit ? 'editModal' : 'addModal' }}" hidden>
        <div class="modal-card mp-modal-wide" role="dialog" aria-modal="true" aria-labelledby="{{ $isEdit ? 'editTitle' : 'addTitle' }}">
            <div class="modal-head"><div><span class="modal-kicker">{{ strtoupper($heading) }}</span><h2 id="{{ $isEdit ? 'editTitle' : 'addTitle' }}">{{ $isEdit ? 'Edit participant' : 'Add participant' }}</h2><p>{{ $isEdit ? 'Update contestant details and photograph.' : 'Create an official MakeMe Live contestant profile.' }}</p><p @if($isEdit) id="editName" @endif></p></div><button type="button" class="modal-close" data-close-modal aria-label="Close">×</button></div>
            <form method="POST" action="{{ $isEdit ? '' : route('admin.makeme.participants.store') }}" @if($isEdit) id="editForm" @endif class="makeme-form mp-profile-form" enctype="multipart/form-data">
                @csrf
                @if($isEdit) @method('PATCH') @endif
                <input type="hidden" name="_profile_modal" value="{{ $mode }}">
                <input type="hidden" name="_participant_id" value="" @if($isEdit) id="mpEditParticipantId" @endif>
                <section class="mp-form-section">
                    <h3>Contestant photograph</h3>
                    <div class="mp-photo-row"><div class="mp-photo-preview" data-photo-preview><span>Photo</span></div><div><label class="field-label" for="{{ $mode }}Photo">Upload photograph</label><input type="file" id="{{ $mode }}Photo" name="photo" accept="image/jpeg,image/png,image/webp" data-photo-input><p class="mp-hint">JPG, PNG or WebP · Maximum 3 MB · Portrait recommended. {{ $isEdit ? 'Leave empty to keep the current photo.' : 'Optional.' }}</p></div></div>
                </section>
                @foreach($sections as $sectionName => $keys)
                    <section class="mp-form-section"><h3>{{ $sectionName }}</h3><div class="mp-field-grid">
                        @foreach($keys as $key)
                            @php [$label, $type, $required, $max] = $fields[$key]; @endphp
                            <div class="mp-field"><label class="field-label" for="{{ $mode }}_{{ $key }}">{{ $label }}@if($required) * @endif</label>
                                <input id="{{ $isEdit ? (['first_name'=>'editFirst','surname'=>'editLast','other_name'=>'editOther','email'=>'editEmail','mobile_no'=>'editPhone'][$key] ?? 'edit_' . $key) : $mode . '_' . $key }}" name="{{ $key }}" type="{{ $type }}" maxlength="{{ $type === 'number' ? null : $max }}" @if($type === 'number') min="1" max="120" step="1" @endif @if($required) required @endif value="{{ !$isEdit ? old($key, '') : '' }}" autocomplete="off">
                            </div>
                        @endforeach
                    </div></section>
                @endforeach
                <section class="mp-form-section"><h3>Account settings</h3><div class="mp-field-grid">
                    <div class="mp-field"><label class="field-label" for="{{ $mode }}Skill">Skill category *</label><select id="{{ $isEdit ? 'editSkill' : 'addSkill' }}" name="skill_category" required><option value="">Select category</option>@foreach($skills as $value => $label)<option value="{{ $value }}" @selected(!$isEdit && old('skill_category') === $value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="mp-field"><label class="field-label" for="{{ $mode }}Status">Status *</label><select id="{{ $isEdit ? 'editStatus' : 'addStatus' }}" name="status" required><option value="active" @selected(!$isEdit && old('status', 'active') === 'active')>Active</option><option value="inactive" @selected(!$isEdit && old('status') === 'inactive')>Inactive</option></select></div>
                </div></section>
                <div class="modal-actions"><button type="button" class="btn btn-outline-light" data-close-modal>Cancel</button><button type="submit" class="btn btn-gold">{{ $isEdit ? 'Save changes' : 'Save participant' }}</button></div>
            </form>
        </div>
    </div>
@endforeach

{{-- VIEW PROFILE MODAL --}}
<div class="modal-backdrop" id="profileModal" hidden><div class="modal-card mp-modal-wide" role="dialog" aria-modal="true" aria-labelledby="profileModalTitle"><div class="modal-head"><div><span class="modal-kicker">CONTESTANT PROFILE</span><h2 id="profileModalTitle">Contestant details</h2></div><button type="button" class="modal-close" data-close-modal aria-label="Close">×</button></div><div class="mp-profile-preview"><div class="mp-photo-preview mp-profile-photo" id="mpViewPhoto"><span>Photo</span></div><div id="mpProfileInfo" class="mp-profile-info"></div></div><div class="modal-actions"><button type="button" class="btn btn-outline-light" data-close-modal>Close</button></div></div></div>

{{-- EXISTING EXCEL IMPORT MODAL (UNCHANGED) --}}
@if($canImport)
<div class="modal-backdrop" id="importModal" hidden><div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="importTitle"><div class="modal-head"><div><span class="modal-kicker">BULK ENROLMENT</span><h2 id="importTitle">Import participants</h2><p>Preview up to 500 rows before importing.</p></div><button type="button" class="modal-close" data-close-modal aria-label="Close">×</button></div>
<a class="btn btn-outline-light" href="{{ route('admin.makeme.participants.import.template') }}">↓ Download template</a>
<form method="POST" action="{{ route('admin.makeme.participants.import.preview') }}" enctype="multipart/form-data">@csrf
<label class="field-label" for="participantExcel">Excel or CSV file *</label><input type="file" id="participantExcel" name="file" accept=".xlsx,.csv" required><p class="help-text">Required columns: first_name, surname, email,
    mobile_no, skill_category. Other profile fields
    are optional. Maximum 500 rows / 2 MB.
    Download the updated template above.</p>
<div class="modal-actions"><button type="button" class="btn btn-outline-light" data-close-modal>Cancel</button><button type="submit" class="btn btn-gold">Preview import</button></div></form></div></div>
@endif


@if($errors->any() && in_array(old('_profile_modal'), ['add', 'edit'], true))
    @php
        $profileFields = array_merge(
            array_keys($fields),
            ['skill_category', 'status']
        );

        $oldValues = [];

        foreach ($profileFields as $field) {
            $oldValues[$field] = old($field);
        }

        $restoreProfile = [
            'modal' => old('_profile_modal'),
            'id' => old('_participant_id'),
            'values' => $oldValues,
        ];
    @endphp

    <script type="application/json" id="mpRestoreProfile">
        @json($restoreProfile)
    </script>
@endif



{{-- DELETE PARTICIPANT MODAL --}}
@if($canDelete)

<div class="modal-backdrop" id="deleteParticipantModal" hidden>

    <div
        class="modal-card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="deleteParticipantTitle"
        style="max-width:460px;"
    >

        <div class="modal-head">
            <div>
                <span class="modal-kicker">
                    CONFIRM ACTION
                </span>

                <h2 id="deleteParticipantTitle">
                    Delete Participant
                </h2>
            </div>

            <button
                type="button"
                class="modal-close"
                id="closeDeleteParticipant"
                aria-label="Close"
            >
                &times;
            </button>
        </div>

        <div style="padding:20px 0;">

            <p>
                Are you sure you want to remove
                <strong id="deleteParticipantName"></strong>?
            </p>

            <p style="font-size:13px;opacity:.75;">
                This participant will lose access to the
                contestant portal. Their previous orders,
                wallet transactions and activity history
                will be retained.
            </p>

        </div>

        <form method="POST" id="deleteParticipantForm">
            @csrf
            @method('DELETE')

            <div class="modal-actions">

                <button
                    type="button"
                    class="btn btn-outline-light"
                    id="cancelDeleteParticipant"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="btn"
                    style="background:#b42318;color:white;"
                >
                    Yes, Delete Participant
                </button>

            </div>
        </form>

    </div>
</div>

@endif



{{-- GENERATED ACTIVATION LINK MODAL --}}
@if(session('activation_link'))

    <div
        class="modal-backdrop"
        id="activationLinkModal"
        style="display:flex;"
    >
        <div
            class="modal-card"
            role="dialog"
            aria-modal="true"
            aria-labelledby="activationLinkTitle"
            style="max-width:520px;width:100%;"
        >

            <div class="modal-head">
                <div>
                    <span class="modal-kicker">
                        PARTICIPANT ACTIVATION
                    </span>

                    
                <h2 id="activationLinkTitle">
                    {{ session('activation_link.type') === 'password_reset'
                        ? 'Password Reset Link Generated'
                        : 'Activation Link Generated' }}
                </h2>

                </div>

                <button
                    type="button"
                    class="modal-close"
                    onclick="document.getElementById('activationLinkModal').style.display='none'"
                >
                    &times;
                </button>
            </div>

            <div style="padding:20px 0;">

                <p>
                    Activation link for
                    <strong>
                        {{ session('activation_link.name') }}
                    </strong>
                </p>

                <p style="font-size:12px;color:#777;">
                    Copy this link and send it privately to the contestant.
                    The link expires according to your participant
                    password broker settings.
                </p>

                <textarea
                    id="generatedActivationUrl"
                    readonly
                    rows="4"
                    style="
                        width:100%;
                        padding:12px;
                        border:1px solid #ddd;
                        border-radius:8px;
                        resize:none;
                        overflow-wrap:anywhere;
                    "
                >{{ session('activation_link.url') }}</textarea>

                <button
                    type="button"
                    class="btn btn-gold"
                    id="copyActivationLink"
                    style="margin-top:14px;"
                >
                    Copy Activation Link
                </button>

                <p
                    id="activationCopyStatus"
                    role="status"
                    style="font-size:12px;margin-top:10px;"
                ></p>

            </div>

        </div>
    </div>

@endif

@endsection
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/makeme-independent-participants.css') }}">
    <link rel="stylesheet" href="{{ asset('css/makeme-participant-profile-admin.css') }}">
@endpush
@push('scripts')
    <script src="{{ asset('js/makeme-participants-independent.js') }}" defer></script>
    <script src="{{ asset('js/makeme-participant-profile-admin.js') }}" defer></script>
    <script>

document.addEventListener('DOMContentLoaded', function () {

    const modal = document.getElementById('deleteParticipantModal');
    const form = document.getElementById('deleteParticipantForm');
    const name = document.getElementById('deleteParticipantName');

    if (!modal || !form || !name) return;

    document.querySelectorAll('.js-delete-participant')
        .forEach(button => {

            button.addEventListener('click', function () {

                form.action = this.dataset.url;
                name.textContent = this.dataset.name;

                modal.hidden = false;
            });

        });

    function closeModal() {
        modal.hidden = true;
        form.removeAttribute('action');
    }

    document.getElementById('closeDeleteParticipant')
        ?.addEventListener('click', closeModal);

    document.getElementById('cancelDeleteParticipant')
        ?.addEventListener('click', closeModal);

    modal.addEventListener('click', function (event) {
        if (event.target === modal) closeModal();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.hidden) {
            closeModal();
        }
    });

});

    </script>
    
<script>
document.addEventListener('DOMContentLoaded', function () {
    const button = document.getElementById('copyActivationLink');
    const field = document.getElementById('generatedActivationUrl');
    const status = document.getElementById('activationCopyStatus');

    if (!button || !field) return;

    button.addEventListener('click', async function () {
        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(field.value);
                status.textContent = 'Activation link copied!';
            } else {
                field.focus();
                field.select();
                status.textContent =
                    'Select and copy the highlighted link.';
            }
        } catch (error) {
            field.focus();
            field.select();
            status.textContent =
                'Please copy the highlighted link manually.';
        }
    });
});
</script>
<script>

document.addEventListener('DOMContentLoaded', function () {

    let activeMenu = null;
    let activeTrigger = null;

    function closeActionMenu() {
        if (activeMenu) {
            activeMenu.hidden = true;
        }

        if (activeTrigger) {
            activeTrigger.setAttribute('aria-expanded', 'false');
        }

        activeMenu = null;
        activeTrigger = null;
    }

    function positionMenu(trigger, menu) {
        const rect = trigger.getBoundingClientRect();

        menu.style.visibility = 'hidden';
        menu.hidden = false;

        const menuWidth = menu.offsetWidth;
        const menuHeight = menu.offsetHeight;
        const gap = 6;
        const margin = 10;

        let left = rect.right - menuWidth;
        left = Math.max(
            margin,
            Math.min(left, window.innerWidth - menuWidth - margin)
        );

        let top = rect.bottom + gap;

        // Open upward when the bottom space is insufficient.
        if (
            top + menuHeight > window.innerHeight - margin &&
            rect.top >= menuHeight + gap
        ) {
            top = rect.top - menuHeight - gap;
        }

        top = Math.max(
            margin,
            Math.min(top, window.innerHeight - menuHeight - margin)
        );

        menu.style.left = left + 'px';
        menu.style.top = top + 'px';
        menu.style.visibility = 'visible';
    }

    document.querySelectorAll('.mp-action-trigger')
        .forEach(function (trigger) {

            trigger.addEventListener('click', function (event) {
                event.stopPropagation();

                const menu = trigger.parentElement
                    .querySelector('.mp-action-dropdown');

                if (!menu) return;

                if (activeMenu === menu) {
                    closeActionMenu();
                    return;
                }

                closeActionMenu();

                activeMenu = menu;
                activeTrigger = trigger;

                trigger.setAttribute('aria-expanded', 'true');
                positionMenu(trigger, menu);
            });
        });

    document.addEventListener('click', function (event) {
        if (
            activeMenu &&
            !activeMenu.contains(event.target) &&
            !activeTrigger.contains(event.target)
        ) {
            closeActionMenu();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeActionMenu();
        }
    });

    window.addEventListener('resize', closeActionMenu);

    // Close on table scrolling to avoid misaligned menus.
    document.querySelectorAll('.table-scroll')
        .forEach(function (container) {
            container.addEventListener('scroll', closeActionMenu);
        });

});

</script>
@endpush
