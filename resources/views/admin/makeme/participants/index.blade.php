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
            <td class="right"><div class="makeme-action-row">
                <button type="button" class="table-action js-profile" data-profile='@json($profile)'>View</button>
                @if($canEdit)
                    <button type="button" class="table-action js-edit"
                        data-url="{{ route('admin.makeme.participants.update', $participant) }}"
                        data-first="{{ $participant->first_name }}" data-last="{{ $participant->surname }}"
                        data-other="{{ $participant->other_name }}" data-email="{{ $participant->email }}"
                        data-phone="{{ $participant->mobile_no }}" data-skill="{{ $participant->skill_category }}"
                        data-status="{{ $participant->status }}" data-profile='@json($profile)'>Edit</button>
                @endif
                @if($canInvite && !$participant->email_verified_at && $participant->status === 'active')
                    <form method="POST" action="{{ route('admin.makeme.participants.invite', $participant) }}">@csrf<button type="submit" class="table-action">Send invitation</button></form>
                @endif
            </div></td>
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
<label class="field-label" for="participantExcel">Excel or CSV file *</label><input type="file" id="participantExcel" name="file" accept=".xlsx,.csv" required><p class="help-text">Columns: first_name, surname, email, mobile_no, skill_category. Maximum 2 MB.</p>
<div class="modal-actions"><button type="button" class="btn btn-outline-light" data-close-modal>Cancel</button><button type="submit" class="btn btn-gold">Preview import</button></div></form></div></div>
@endif

@if($errors->any() && in_array(old('_profile_modal'), ['add','edit'], true))
    @php $restoreProfile = ['modal' => old('_profile_modal'), 'id' => old('_participant_id'), 'values' => old(array_merge(array_keys($fields), ['skill_category','status']))]; @endphp
    <script type="application/json" id="mpRestoreProfile">@json($restoreProfile)</script>
@endif
@endsection
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/makeme-independent-participants.css') }}">
    <link rel="stylesheet" href="{{ asset('css/makeme-participant-profile-admin.css') }}">
@endpush
@push('scripts')
    <script src="{{ asset('js/makeme-participants-independent.js') }}" defer></script>
    <script src="{{ asset('js/makeme-participant-profile-admin.js') }}" defer></script>
@endpush
