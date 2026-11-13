
@extends('admin.makeme.layouts.app')

@section('title', 'Wallet Management')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/makeme-admin-wallets.css') }}">
@endpush

@section('content')

@php
    /*
    |--------------------------------------------------------------------------
    | ADMINISTRATOR PERMISSIONS
    |--------------------------------------------------------------------------
    */

    $admin = auth('web')->user();

    $canAdjust = $admin && (
        $admin->hasRole('Super Admin', 'web') ||
        $admin->can('makeme.wallets.adjust')
    );

    /*
    |--------------------------------------------------------------------------
    | PARTICIPANT DATA
    |--------------------------------------------------------------------------
    |
    | Participants now come directly from makeme_participants.
    | Wallets are accessed through the wallet() relationship.
    |
    */

    $participantPayloads = [];

    $formatCredits = function ($amount) {
        return number_format((int) $amount);
    };
@endphp

<div class="wm-page">

    {{-- PAGE HEADER --}}
    <div class="wm-header">
        <div>
            <h1>Wallet Management</h1>
            <p>
                Manage participant credits, wallet balances
                and transaction history.
            </p>
        </div>
    </div>


    {{-- SUCCESS MESSAGE --}}
    @if(session('success'))
        <div class="wm-alert wm-alert-success">
            {{ session('success') }}
        </div>
    @endif


    {{-- VALIDATION ERRORS --}}
    @if($errors->any())
        <div class="wm-alert wm-alert-danger">
            <strong>
                Please correct the following:
            </strong>

            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    {{-- WALLET STATISTICS --}}
    <div class="wm-stats">

        <div class="wm-stat">
            <div class="wm-stat-icon">◎</div>

            <span>Active Participants</span>

            <strong>
                {{ number_format($stats['active_participants'] ?? 0) }}
            </strong>
        </div>


        <div class="wm-stat">
            <div class="wm-stat-icon">◈</div>

            <span>Available Credits</span>

            <strong>
                {{ $formatCredits($stats['available_credits'] ?? 0) }}
            </strong>
        </div>


        <div class="wm-stat">
            <div class="wm-stat-icon">＋</div>

            <span>Credits Issued</span>

            <strong>
                {{ $formatCredits($stats['credits_issued'] ?? 0) }}
            </strong>
        </div>


        <div class="wm-stat">
            <div class="wm-stat-icon">−</div>

            <span>Credits Deducted</span>

            <strong>
                {{ $formatCredits($stats['credits_deducted'] ?? 0) }}
            </strong>
        </div>

    </div>


    {{-- PARTICIPANT WALLETS --}}
    <div class="wm-card">

        <div class="wm-card-header">
            <div>
                <h2>Participant Wallets</h2>

                <p>
                    Search participants, review balances,
                    allocate credits and view transactions.
                </p>
            </div>
        </div>


        {{-- SEARCH AND FILTERS --}}
        <form
            method="GET"
            action="{{ route('admin.makeme.wallets.index') }}"
            class="wm-filters"
        >

            <input
                type="search"
                name="q"
                maxlength="100"
                placeholder="Search participant name, email or phone"
                value="{{ request('q', '') }}"
                aria-label="Search participants"
            >

            <select
                name="balance"
                aria-label="Filter wallet balance"
            >
                <option
                    value="all"
                    @selected(request('balance', 'all') === 'all')
                >
                    All Balances
                </option>

                <option
                    value="positive"
                    @selected(request('balance') === 'positive')
                >
                    Has Credits
                </option>

                <option
                    value="zero"
                    @selected(request('balance') === 'zero')
                >
                    Zero Balance
                </option>
            </select>

            <button
                type="submit"
                class="wm-btn wm-primary"
            >
                Search
            </button>

            <a
                href="{{ route('admin.makeme.wallets.index') }}"
                class="wm-btn wm-secondary"
            >
                Reset
            </a>

        </form>


        {{-- WALLET TABLE --}}
        <div class="wm-table-wrap">

            <table class="wm-table">

                <thead>
                    <tr>
                        <th>Participant</th>
                        <th>Wallet Balance</th>
                        <th>Wallet Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($participants as $participant)

                        @php
                            /*
                            |----------------------------------------------
                            | PARTICIPANT INFORMATION
                            |----------------------------------------------
                            */

                            $participantId = $participant->getKey();

                            $firstName = trim((string) (
                                $participant->first_name ?? ''
                            ));

                            $surname = trim((string) (
                                $participant->surname ?? ''
                            ));

                            $otherName = trim((string) (
                                $participant->other_name ?? ''
                            ));

                            $name = trim(implode(' ', array_filter([
                                $firstName,
                                $otherName,
                                $surname,
                            ], fn ($value) => $value !== '')));

                            if ($name === '') {
                                $name = trim((string) (
                                    $participant->full_name
                                    ?? $participant->name
                                    ?? ''
                                ));
                            }

                            if ($name === '') {
                                $name = 'Participant #' . $participantId;
                            }

                            $email = trim((string) (
                                $participant->email ?? ''
                            ));

                            $phone = trim((string) (
                                $participant->mobile_no
                                ?? $participant->phone
                                ?? $participant->phone_number
                                ?? ''
                            ));

                            $contact = $email !== ''
                                ? $email
                                : ($phone !== '' ? $phone : 'No contact provided');

                            $avatarLetter = mb_strtoupper(
                                mb_substr($name, 0, 1)
                            );

                            /*
                            |----------------------------------------------
                            | WALLET INFORMATION
                            |----------------------------------------------
                            |
                            | Correct relationship:
                            | MakeMeParticipant -> wallet
                            |
                            */

                            $wallet = $participant->wallet;

                            $balance = $wallet
                                ? (int) $wallet->balance
                                : 0;

                            /*
                            |----------------------------------------------
                            | TRANSACTION HISTORY
                            |----------------------------------------------
                            */

                            $transactions = $wallet
                                ? $wallet->transactions
                                    ->map(function ($tx) {

                                        $administratorName = trim(
                                            ($tx->administrator?->first_name ?? '')
                                            . ' ' .
                                            ($tx->administrator?->surname ?? '')
                                        );

                                        return [
                                            'id' => $tx->id,

                                            'date' => $tx->created_at
                                                ? $tx->created_at->format(
                                                    'd M Y, h:i A'
                                                )
                                                : '—',

                                            'type' => $tx->type,

                                            'amount' => (int) $tx->amount,

                                            'before' => (int) $tx->balance_before,

                                            'after' => (int) $tx->balance_after,

                                            'reason' => $tx->reason ?? '',

                                            'administrator' =>
                                                $administratorName !== ''
                                                    ? $administratorName
                                                    : 'System',
                                        ];
                                    })
                                    ->values()
                                    ->all()
                                : [];

                            /*
                            |----------------------------------------------
                            | JAVASCRIPT PAYLOAD
                            |----------------------------------------------
                            */

                            $participantPayloads[] = [
                                'id' => $participantId,
                                'name' => $name,
                                'email' => $email,
                                'balance' => $balance,

                                'adjust_url' => route(
                                    'admin.makeme.wallets.adjust',
                                    ['participant' => $participantId]
                                ),

                                'transactions' => $transactions,
                            ];
                        @endphp

                        <tr>

                            {{-- PARTICIPANT --}}
                            <td>
                                <div class="wm-user">

                                    <span class="wm-avatar">
                                        {{ $avatarLetter }}
                                    </span>

                                    <div>
                                        <strong>
                                            {{ $name }}
                                        </strong>

                                        <small>
                                            {{ $contact }}
                                        </small>
                                    </div>

                                </div>
                            </td>


                            {{-- WALLET BALANCE --}}
                            <td>
                                <span class="wm-balance">

                                    {{ number_format($balance) }}

                                    <small>credits</small>

                                </span>
                            </td>


                            {{-- WALLET STATUS --}}
                            <td>

                                @if($wallet)

                                    <span class="wm-badge wm-badge-success">
                                        Wallet Created
                                    </span>

                                @else

                                    <span class="wm-badge wm-badge-muted">
                                        Not Allocated
                                    </span>

                                @endif

                            </td>


                            {{-- ACTIONS --}}
                            <td>
                                <div class="wm-actions">

                                    <button
                                        type="button"
                                        class="wm-action"
                                        data-wm-history="{{ $participantId }}"
                                    >
                                        View History
                                    </button>

                                    @if($canAdjust)

                                        <button
                                            type="button"
                                            class="wm-action wm-action-primary"
                                            data-wm-adjust="{{ $participantId }}"
                                        >
                                            Adjust Credits
                                        </button>

                                    @endif

                                </div>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td
                                colspan="4"
                                class="wm-empty"
                            >
                                No participants found.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>
        </div>


        {{-- PAGINATION --}}
        @if($participants->hasPages())

            <div class="wm-pagination">
                {{ $participants->links() }}
            </div>

        @endif

    </div>

</div>


{{-- PARTICIPANT JSON DATA --}}
{{-- Keep JSON outside the table to preserve valid HTML. --}}

@foreach($participantPayloads as $payload)

    <script
        type="application/json"
        id="wmParticipant{{ $payload['id'] }}"
    >@json($payload)</script>

@endforeach


{{-- =============================================
     ADJUST WALLET CREDITS MODAL
============================================= --}}

@if($canAdjust)

<div
    class="wm-overlay"
    id="wmAdjustModal"
    hidden
>

    <section
        class="wm-modal wm-modal-small"
        role="dialog"
        aria-modal="true"
        aria-labelledby="wmAdjustTitle"
    >

        <div class="wm-modal-header">

            <div>
                <h2 id="wmAdjustTitle">
                    Adjust Wallet Credits
                </h2>

                <p id="wmAdjustSubtitle"></p>
            </div>

            <button
                type="button"
                class="wm-close"
                data-wm-close
                aria-label="Close"
            >
                &times;
            </button>

        </div>


        <form
            method="POST"
            id="wmAdjustForm"
            action=""
        >
            @csrf

            <input
                type="hidden"
                name="_modal"
                value="adjust"
            >

            <input
                type="hidden"
                name="_participant_id"
                id="wmAdjustParticipantId"
            >


            <div class="wm-modal-body">

                {{-- CURRENT BALANCE --}}
                <div class="wm-current-balance">

                    <span>
                        Current Balance
                    </span>

                    <strong id="wmCurrentBalance">
                        0 credits
                    </strong>

                </div>


                {{-- TRANSACTION TYPE --}}
                <div class="wm-field">

                    <label for="wmType">
                        Transaction Type
                    </label>

                    <select
                        name="type"
                        id="wmType"
                        required
                    >

                        <option value="credit">
                            Add Credits
                        </option>

                        <option value="debit">
                            Deduct Credits
                        </option>

                    </select>

                </div>


                {{-- CREDIT AMOUNT --}}
                <div class="wm-field">

                    <label for="wmAmount">
                        Number of Credits
                    </label>

                    <input
                        type="number"
                        name="amount"
                        id="wmAmount"
                        min="1"
                        max="999999999"
                        step="1"
                        placeholder="Enter number of credits"
                        required
                    >

                </div>


                {{-- REASON --}}
                <div class="wm-field">

                    <label for="wmReason">
                        Reason for Adjustment
                    </label>

                    <textarea
                        name="reason"
                        id="wmReason"
                        rows="3"
                        minlength="5"
                        maxlength="255"
                        placeholder="Explain why these credits are being added or deducted"
                        required
                    ></textarea>

                </div>


                {{-- BALANCE PREVIEW --}}
                <div class="wm-preview">

                    <span>
                        New Balance
                    </span>

                    <strong id="wmNewBalance">
                        0 credits
                    </strong>

                </div>


                {{-- WARNING --}}
                <p
                    class="wm-warning"
                    id="wmBalanceWarning"
                    role="alert"
                    hidden
                >
                    Insufficient credits for this deduction.
                </p>

            </div>


            <div class="wm-modal-footer">

                <button
                    type="button"
                    class="wm-btn wm-secondary"
                    data-wm-close
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    id="wmAdjustSubmit"
                    class="wm-btn wm-primary"
                >
                    Save Adjustment
                </button>

            </div>

        </form>

    </section>

</div>

@endif


{{-- =============================================
     TRANSACTION HISTORY MODAL
============================================= --}}

<div
    class="wm-overlay"
    id="wmHistoryModal"
    hidden
>

    <section
        class="wm-modal wm-modal-large"
        role="dialog"
        aria-modal="true"
        aria-labelledby="wmHistoryTitle"
    >

        <div class="wm-modal-header">

            <div>

                <h2 id="wmHistoryTitle">
                    Transaction History
                </h2>

                <p id="wmHistorySubtitle"></p>

            </div>

            <button
                type="button"
                class="wm-close"
                data-wm-close
                aria-label="Close"
            >
                &times;
            </button>

        </div>


        <div class="wm-modal-body">

            <p class="wm-history-note">
                Showing the latest 20 recorded transactions
                for this participant.
            </p>

            <div class="wm-table-wrap">

                <table class="wm-table">

                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Balance</th>
                            <th>Reason</th>
                            <th>Performed By</th>
                        </tr>
                    </thead>

                    <tbody id="wmHistoryBody">

                        {{-- Populated by JavaScript --}}

                    </tbody>

                </table>

            </div>

        </div>


        <div class="wm-modal-footer">

            <button
                type="button"
                class="wm-btn wm-secondary"
                data-wm-close
            >
                Close
            </button>

        </div>

    </section>

</div>


{{-- =============================================
     VALIDATION RESTORATION
============================================= --}}

@if($errors->any() && old('_modal') === 'adjust')

    <div
        id="wmValidation"
        hidden
        data-modal="adjust"
        data-participant-id="{{ old('_participant_id') }}"
        data-type="{{ old('type', 'credit') }}"
        data-amount="{{ old('amount', '') }}"
        data-reason="{{ old('reason', '') }}"
    ></div>

@endif

@endsection


@push('scripts')

<script src="{{ asset('js/makeme-admin-wallets.js') }}"></script>

@endpush
