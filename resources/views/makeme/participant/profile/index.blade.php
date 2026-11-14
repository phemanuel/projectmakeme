@extends('makeme.participant.layouts.app')

@section('title', 'My Profile | Project MakeMe Live')
@section('breadcrumb', 'My Profile')

@section('content')
<div class="mpf-page">
    <div class="mpf-heading">
        <div>
            <p class="cp-overline">YOUR CONTESTANT IDENTITY</p>
            <h1>My Profile<span>.</span></h1>
            <p>Review your contestant information and keep your details up to date.</p>
        </div>
        <a href="{{ route('makeme.participant.dashboard') }}" class="mpf-btn mpf-outline">← Dashboard</a>
    </div>

    @if(session('success'))
        <div class="mpf-alert mpf-success" role="status">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mpf-alert mpf-error" role="alert">
            <strong>Please correct the following:</strong>
            <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ route('makeme.participant.profile.update') }}" method="POST" enctype="multipart/form-data" class="mpf-grid">
        @csrf
        @method('PATCH')

        <div class="mpf-left">
            <section class="mpf-card mpf-identity" aria-labelledby="mpf-id-title">
                <div class="mpf-card-head"><p class="cp-overline">OFFICIAL PROFILE</p><h2 id="mpf-id-title">Contestant ID</h2></div>
                @php
                    $displayName = trim(implode(' ', array_filter([$participant->first_name, $participant->other_name, $participant->surname])));
                    $initial = mb_strtoupper(mb_substr($participant->first_name ?: 'C', 0, 1));
                @endphp
                <div class="mpf-photo" id="mpfPhotoBox">
                    @if($participant->photo_url)
                        <img id="mpfPhotoImage" src="{{ $participant->photo_url }}" alt="{{ $displayName }}'s portrait" onerror="this.hidden=true;document.getElementById('mpfPhotoFallback').hidden=false;">
                        <span id="mpfPhotoFallback" hidden>{{ $initial }}</span>
                    @else
                        <img id="mpfPhotoImage" alt="Preview of contestant portrait" hidden>
                        <span id="mpfPhotoFallback">{{ $initial }}</span>
                    @endif
                </div>
                <label for="mpfPhotoInput" class="mpf-btn mpf-outline mpf-upload-btn">Change photograph</label>
                <input id="mpfPhotoInput" type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="mpf-file-input">
                <small class="mpf-photo-note">JPG, PNG or WebP · Maximum 3 MB</small>
                <h3>{{ $displayName }}</h3>
                <p class="mpf-number">{{ $participant->participant_number }}</p>
                <div class="mpf-divider"></div>
                <div class="mpf-readonly-row"><span>Email</span><strong>{{ $participant->email }}</strong></div>
                <div class="mpf-readonly-row"><span>Skill category</span><strong>{{ \App\Http\Controllers\Admin\MakeMeParticipantController::SKILLS[$participant->skill_category] ?? ucwords(str_replace('_', ' ', $participant->skill_category ?? 'Not set')) }}</strong></div>
                <div class="mpf-readonly-row"><span>Account status</span><strong class="mpf-active">{{ ucfirst($participant->status ?? 'inactive') }}</strong></div>
                <p class="mpf-locked-note">Email, contestant number, skill category and account status are managed by NYFEW administrators.</p>
            </section>
        </div>

        <div class="mpf-right">
            <section class="mpf-card" aria-labelledby="mpf-personal-title">
                <div class="mpf-card-head"><p class="cp-overline">PERSONAL DETAILS</p><h2 id="mpf-personal-title">About You</h2></div>
                <div class="mpf-fields">
                    <div class="mpf-field"><label for="mpfFirst">First name <b>*</b></label><input id="mpfFirst" name="first_name" value="{{ old('first_name', $participant->first_name) }}" required maxlength="100"></div>
                    <div class="mpf-field"><label for="mpfSurname">Surname <b>*</b></label><input id="mpfSurname" name="surname" value="{{ old('surname', $participant->surname) }}" required maxlength="100"></div>
                    <div class="mpf-field"><label for="mpfOther">Other name</label><input id="mpfOther" name="other_name" value="{{ old('other_name', $participant->other_name) }}" maxlength="100"></div>
                    <div class="mpf-field"><label for="mpfPhone">Phone number <b>*</b></label><input id="mpfPhone" name="mobile_no" type="tel" value="{{ old('mobile_no', $participant->mobile_no) }}" required maxlength="25"></div>
                    <div class="mpf-field"><label for="mpfAge">Age</label><input id="mpfAge" name="age" type="number" min="16" max="120" value="{{ old('age', $participant->age) }}"></div>
                    <div class="mpf-field"><label for="mpfAltEmail">Alternative email</label><input id="mpfAltEmail" name="alternative_email" type="email" maxlength="255" value="{{ old('alternative_email', $participant->alternative_email) }}"></div>
                    <div class="mpf-field"><label for="mpfCity">City / Location</label><input id="mpfCity" name="city" maxlength="120" value="{{ old('city', $participant->city) }}"></div>
                    <div class="mpf-field"><label for="mpfOrigin">State of origin</label><input id="mpfOrigin" name="state_of_origin" maxlength="120" value="{{ old('state_of_origin', $participant->state_of_origin) }}"></div>
                </div>
            </section>

            <section class="mpf-card" aria-labelledby="mpf-business-title">
                <div class="mpf-card-head"><p class="cp-overline">CREATIVE WORK</p><h2 id="mpf-business-title">Business & Profession</h2></div>
                <div class="mpf-fields">
                    <div class="mpf-field"><label for="mpfBusiness">Business name</label><input id="mpfBusiness" name="business_name" maxlength="180" value="{{ old('business_name', $participant->business_name) }}"></div>
                    <div class="mpf-field"><label for="mpfProfession">Profession</label><input id="mpfProfession" name="profession" maxlength="150" value="{{ old('profession', $participant->profession) }}"></div>
                </div>
            </section>

            <section class="mpf-card" aria-labelledby="mpf-social-title">
                <div class="mpf-card-head"><p class="cp-overline">FIND YOUR WORK</p><h2 id="mpf-social-title">Social Media</h2><p>Enter your usernames, page names or profile links.</p></div>
                <div class="mpf-fields">
                    @foreach([
                        'instagram_handle' => 'Instagram',
                        'tiktok_handle' => 'TikTok',
                        'facebook_handle' => 'Facebook',
                        'x_handle' => 'X (Twitter)',
                        'linkedin_handle' => 'LinkedIn',
                    ] as $column => $label)
                        <div class="mpf-field">
                            <label for="mpf-{{ $column }}">{{ $label }}</label>
                            <input id="mpf-{{ $column }}" name="{{ $column }}" type="text" maxlength="{{ in_array($column, ['instagram_handle', 'tiktok_handle']) ? 150 : 255 }}" value="{{ old($column, $participant->{$column}) }}" placeholder="Username or profile URL">
                        </div>
                    @endforeach
                </div>
            </section>

            <div class="mpf-submit-row">
                <p>Changes to your profile may appear across your dashboard and contestant portal.</p>
                <button type="submit" class="mpf-btn mpf-gold">Save Profile Changes</button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/makeme-participant-profile.css') }}?v=1">
@endpush

@push('scripts')
<script>
(function () {
    const input = document.getElementById('mpfPhotoInput');
    const image = document.getElementById('mpfPhotoImage');
    const fallback = document.getElementById('mpfPhotoFallback');
    if (!input || !image || !fallback) return;
    let previewUrl = null;
    input.addEventListener('change', function () {
        const file = this.files && this.files[0];
        if (!file) return;
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 3 * 1024 * 1024) {
            alert('Select a JPG, PNG or WebP image not larger than 3 MB.');
            this.value = '';
            return;
        }
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = URL.createObjectURL(file);
        image.src = previewUrl;
        image.hidden = false;
        fallback.hidden = true;
    });
})();
</script>
@endpush
