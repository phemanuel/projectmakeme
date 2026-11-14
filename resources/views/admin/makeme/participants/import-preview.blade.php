
@extends('admin.makeme.layouts.app')

@section('title', 'Preview Participant Import')

@section('content')

<section class="page-intro">
    <div>
        <div class="intro-kicker">
            SHOW OPERATIONS / PARTICIPANTS
        </div>

        <h1>
            Preview Import<span class="gold-period">.</span>
        </h1>

        <p>
            Review participant information before importing.
        </p>
    </div>

    <a
        class="btn btn-outline"
        href="{{ route('admin.makeme.participants.index') }}"
    >
        ← Back
    </a>
</section>

{{-- IMPORT STATISTICS --}}
<div class="stat-grid">

    <article class="stat-card">
        <span class="stat-label">TOTAL ROWS</span>
        <div class="stat-value">
            {{ $analysis['count'] ?? 0 }}
        </div>
    </article>

    <article class="stat-card">
        <span class="stat-label">VALID ROWS</span>
        <div class="stat-value">
            {{ count($analysis['rows'] ?? []) }}
        </div>
    </article>

    <article class="stat-card">
        <span class="stat-label">ERRORS</span>
        <div class="stat-value">
            {{ count($analysis['errors'] ?? []) }}
        </div>
    </article>

</div>

<section
    class="panel"
    style="padding:24px;margin-top:24px;"
>

    {{-- VALIDATION ERRORS --}}
    @if(!empty($analysis['errors']))

        <div class="notice error">
            Some rows contain errors.
            Please correct them and upload again.
            No participant records have been saved.
        </div>

        <div class="table-scroll">

            <table class="participant-table">

                <thead>
                    <tr>
                        <th>ROW</th>
                        <th>EMAIL</th>
                        <th>ISSUE</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($analysis['errors'] as $error)
                        <tr>
                            <td>
                                {{ $error['line'] ?? '-' }}
                            </td>

                            <td>
                                {{ $error['email'] ?? '-' }}
                            </td>

                            <td>
                                {{ $error['message'] ?? 'Unknown error' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>

            </table>

        </div>

        <a
            href="{{ route('admin.makeme.participants.index') }}"
            class="btn btn-gold"
        >
            Correct and Upload Again
        </a>

    @else

        <div class="notice success">
            All {{ $analysis['count'] ?? 0 }} rows passed validation.
            Review the details below before confirming the import.
        </div>

        {{-- PARTICIPANT PREVIEW --}}
        <div class="table-scroll">

            <table class="participant-table">

                <thead>
                    <tr>
                        <th>ROW</th>
                        <th>NAME</th>
                        <th>EMAIL</th>
                        <th>PHONE</th>
                        <th>SKILL</th>
                        <th>BUSINESS</th>
                        <th>PROFESSION</th>
                        <th>AGE</th>
                        <th>LOCATION</th>
                        <th>STATUS</th>
                    </tr>
                </thead>

                <tbody>

                    @foreach(array_slice($analysis['rows'] ?? [], 0, 40) as $row)

                        @php
                            $skillKey = $row['skill_category'] ?? '';

                            $skillName =
                                \App\Http\Controllers\Admin\MakeMeParticipantController::SKILLS[$skillKey]
                                ?? $skillKey
                                ?: 'Not specified';

                            $participantName = trim(
                                ($row['first_name'] ?? '') . ' ' .
                                ($row['surname'] ?? '')
                            );
                        @endphp

                        <tr>

                            <td>
                                {{ $row['line'] ?? '-' }}
                            </td>

                            <td>
                                <strong>
                                    {{ $participantName }}
                                </strong>
                            </td>

                            <td>
                                {{ $row['email'] ?? '-' }}
                            </td>

                            <td>
                                {{ $row['mobile_no'] ?? '-' }}
                            </td>

                            <td>
                                {{ $skillName }}
                            </td>

                            <td>
                                {{ $row['business_name'] ?: '-' }}
                            </td>

                            <td>
                                {{ $row['profession'] ?: '-' }}
                            </td>

                            <td>
                                {{ $row['age'] ?: '-' }}
                            </td>

                            <td>
                                {{ $row['city'] ?: '-' }}
                            </td>

                            <td>
                                @php
                                    $status = $row['status'] ?? 'active';
                                    $status = $status ?: 'active';
                                @endphp

                                <span class="status {{ $status === 'active' ? 'status-active' : 'status-inactive' }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

        @if(count($analysis['rows'] ?? []) > 40)
            <p style="margin-top:15px;font-size:12px;">
                Showing the first 40 valid rows.
            </p>
        @endif

        {{-- IMPORT CONFIRMATION --}}
        <form
            method="POST"
            action="{{ route('admin.makeme.participants.import.confirm') }}"
            style="padding:20px 0;"
        >

            @csrf

            <input
                type="hidden"
                name="token"
                value="{{ $token }}"
            >

            <button
                type="submit"
                class="btn btn-gold"
            >
                Confirm and Import
                {{ count($analysis['rows'] ?? []) }}
                Participants
            </button>

        </form>

    @endif

</section>

@endsection
