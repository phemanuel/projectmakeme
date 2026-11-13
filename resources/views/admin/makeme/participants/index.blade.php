@extends('admin.makeme.layouts.app')
@section('title', 'Participants')
@section('content')

@php
    $admin = auth('web')->user();

    $isSuperAdmin = $admin->hasRole('Super Admin', 'web');

    $canCreate = $isSuperAdmin ||
        $admin->can('makeme.participants.create');

    $canEdit = $isSuperAdmin ||
        $admin->can('makeme.participants.edit');

    $canImport = $isSuperAdmin ||
        $admin->can('makeme.participants.import');

    $canExport = $isSuperAdmin ||
        $admin->can('makeme.participants.export');

    $canInvite = $isSuperAdmin ||
        $admin->can('makeme.participants.invite');

    $canDelete = $isSuperAdmin ||
        $admin->can('makeme.participants.delete');
@endphp
<section class="page-intro">
    <div><div class="intro-kicker"><span class="live-dot"></span> SHOW OPERATIONS / PARTICIPANTS</div><h1>Participant Management<span class="gold-period">.</span></h1><p>Manage independent Project MakeMe Live contestants.</p></div>
    <div class="intro-actions">
        @if($canExport)
            <a class="btn btn-outline" href="{{ route('admin.makeme.participants.export', request()->only(['search','skill','status'])) }}">↓ Export CSV</a>
        @endif
        
        @if($canImport)
          <button type="button" class="btn btn-outline" data-open-modal="importModal">↥ Import Excel</button>
        @endif
        
        @if($canCreate)

          <button type="button" class="btn btn-gold" data-open-modal="addModal">+ Add participant</button>

        @endif
        
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
 <div class="panel-heading"><div><h2>Contestant directory</h2><p>View and manage registrations without using the NYFEW users table.</p></div><span class="record-count">{{ $participants->total() }} records</span></div>
 <form class="filters" method="GET" action="{{ route('admin.makeme.participants.index') }}">
   <input name="search" type="search" placeholder="Name, email or participant number" value="{{ request('search') }}" aria-label="Search participants">
   <select name="skill" aria-label="Skill category"><option value="">All skills</option>@foreach($skills as $value=>$label)<option value="{{ $value }}" @selected(request('skill')===$value)>{{ $label }}</option>@endforeach</select>
   <select name="status" aria-label="Status"><option value="">All statuses</option><option value="active" @selected(request('status')==='active')>Active</option><option value="inactive" @selected(request('status')==='inactive')>Inactive</option></select>
   <button class="btn btn-gold" type="submit">Apply filters</button>
   <a class="btn btn-outline" href="{{ route('admin.makeme.participants.index') }}">Clear</a>
 </form>
 <div class="table-scroll"><table class="participant-table"><thead><tr><th>PARTICIPANT</th><th>NUMBER</th><th>SKILL</th><th>STATUS</th><th>ACTIVATION</th><th class="right">ACTIONS</th></tr></thead><tbody>
 @forelse($participants as $participant)
 <tr>
   <td><div class="participant-cell"><div class="initial-circle">{{ strtoupper(mb_substr($participant->first_name ?: 'P',0,1)) }}</div><div><strong>{{ $participant->full_name }}</strong><small>{{ $participant->email }}</small></div></div></td>
   <td><code class="participant-code">{{ $participant->participant_number }}</code></td>
   <td>{{ $skills[$participant->skill_category] ?? $participant->skill_category }}</td>
   <td><span class="status {{ $participant->status === 'active' ? 'status-active' : 'status-inactive' }}">{{ ucfirst($participant->status) }}</span></td>
   <td>{{ $participant->email_verified_at ? 'Activated' : 'Not activated' }}</td>
   <td class="right"><div class="makeme-action-row">
    @if($canEdit)

    <button type="button" class="table-action js-edit" data-url="{{ route('admin.makeme.participants.update',$participant) }}" data-first="{{ $participant->first_name }}" data-last="{{ $participant->surname }}" data-other="{{ $participant->other_name }}" data-email="{{ $participant->email }}" data-phone="{{ $participant->mobile_no }}" data-skill="{{ $participant->skill_category }}" data-status="{{ $participant->status }}">Edit</button>
    @endif
      
      @if(!$participant->email_verified_at && $participant->status === 'active')
      <form method="POST" action="{{ route('admin.makeme.participants.invite',$participant) }}">@csrf<button type="submit" class="table-action">Send invitation</button></form>
      @endif
   </div></td>
 </tr>
 @empty<tr><td colspan="6"><div class="empty-state"><strong>No participants found</strong><p>Add a participant or adjust your filters.</p></div></td></tr>@endforelse
 </tbody></table></div>
 <div class="pagination-bar"><span>{{ $participants->firstItem() ?? 0 }}–{{ $participants->lastItem() ?? 0 }} of {{ $participants->total() }}</span><div class="pagination-buttons">{{ $participants->onEachSide(1)->links() }}</div></div>
</section>
<div class="modal-backdrop" id="addModal" hidden><div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="addTitle"><div class="modal-head"><div><span class="modal-kicker">NEW CONTESTANT</span><h2 id="addTitle">Add participant</h2><p>Create a MakeMe Live profile directly.</p></div><button type="button" class="modal-close" data-close-modal aria-label="Close">×</button></div>
<form method="POST" action="{{ route('admin.makeme.participants.store') }}" class="makeme-form">@csrf
<label class="field-label">First name *</label><input name="first_name" value="{{ old('first_name') }}" required maxlength="100">
<label class="field-label">Surname *</label><input name="surname" value="{{ old('surname') }}" required maxlength="100">
<label class="field-label">Other name</label><input name="other_name" value="{{ old('other_name') }}" maxlength="100">
<label class="field-label">Email *</label><input name="email" type="email" value="{{ old('email') }}" required>
<label class="field-label">Phone number *</label><input name="mobile_no" type="tel" value="{{ old('mobile_no') }}" required>
<label class="field-label">Skill *</label><select name="skill_category" required><option value="">Select category</option>@foreach($skills as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
<label class="field-label">Status *</label><select name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select>
<div class="modal-actions"><button type="button" class="btn btn-outline-light" data-close-modal>Cancel</button><button class="btn btn-gold" type="submit">Save participant</button></div></form></div></div>
<div class="modal-backdrop" id="editModal" hidden><div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="editTitle"><div class="modal-head"><div><span class="modal-kicker">EDIT CONTESTANT</span><h2 id="editTitle">Edit participant</h2><p id="editName"></p></div><button type="button" class="modal-close" data-close-modal aria-label="Close">×</button></div>
<form method="POST" id="editForm" class="makeme-form">@csrf @method('PATCH')
<label class="field-label">First name *</label><input id="editFirst" name="first_name" required>
<label class="field-label">Surname *</label><input id="editLast" name="surname" required>
<label class="field-label">Other name</label><input id="editOther" name="other_name">
<label class="field-label">Email *</label><input id="editEmail" name="email" type="email" required>
<label class="field-label">Phone *</label><input id="editPhone" name="mobile_no" required>
<label class="field-label">Skill *</label><select id="editSkill" name="skill_category" required>@foreach($skills as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
<label class="field-label">Access *</label><select id="editStatus" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select>

<div class="modal-actions"><button type="button" class="btn btn-outline-light" data-close-modal>Cancel</button><button class="btn btn-gold" type="submit">Save changes</button></div></form></div></div>
<div class="modal-backdrop" id="importModal" hidden><div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="importTitle"><div class="modal-head"><div><span class="modal-kicker">BULK ENROLMENT</span><h2 id="importTitle">Import participants</h2><p>Preview up to 500 rows before importing.</p></div><button type="button" class="modal-close" data-close-modal aria-label="Close">×</button></div>
<a class="btn btn-outline-light" href="{{ route('admin.makeme.participants.import.template') }}">↓ Download template</a>
<form method="POST" action="{{ route('admin.makeme.participants.import.preview') }}" enctype="multipart/form-data">@csrf
<label class="field-label" for="participantExcel">Excel or CSV file *</label><input type="file" id="participantExcel" name="file" accept=".xlsx,.csv" required>
<p class="help-text">Columns: first_name, surname, email, mobile_no, skill_category. Maximum 2 MB.</p>
<div class="modal-actions"><button type="button" class="btn btn-outline-light" data-close-modal>Cancel</button><button type="submit" class="btn btn-gold">Preview import</button></div></form></div></div>
@endsection
@push('styles')<link rel="stylesheet" href="{{ asset('css/makeme-independent-participants.css') }}">@endpush
@push('scripts')<script src="{{ asset('js/makeme-participants-independent.js') }}" defer></script>@endpush
