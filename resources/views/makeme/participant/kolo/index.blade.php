@extends('makeme.participant.layouts.app')

@section('title', 'Kolo Money | Project MakeMe Live')
@section('breadcrumb', 'Kolo Money')

@section('content')
<div class="km-page">
    <div class="km-page-head">
        <div>
            <p class="cp-overline">YOUR CONTESTANT WALLET</p>
            <h1>Kolo Money<span class="km-gold">.</span></h1>
            <p>Check your available credits and track every wallet movement.</p>
        </div>
        @if(\Illuminate\Support\Facades\Route::has('makeme.participant.marketplace.index'))
            <a class="km-button km-button-gold" href="{{ route('makeme.participant.marketplace.index') }}">Explore Marketplace <span aria-hidden="true">↗</span></a>
        @endif
    </div>

    <div class="km-summary">
        <section class="km-balance-card" aria-label="Available Kolo Money credits">
            <div class="km-balance-top">
                <span class="km-eyebrow">AVAILABLE BALANCE</span>
                <span class="km-emblem" aria-hidden="true">✦</span>
            </div>
            <div class="km-balance-number">{{ number_format($balance) }}</div>
            <p>KOLO CREDITS</p>
            <div class="km-balance-bottom">
                <span>Contestant: {{ $participant->participant_number ?? '—' }}</span>
                <span>Project MakeMe Live</span>
            </div>
        </section>

        <div class="km-stat-stack">
            <div class="km-stat-card">
                <span class="km-stat-icon km-in" aria-hidden="true">↓</span>
                <div><span>Total credits received</span><strong>{{ number_format($totalReceived) }}</strong><small>Allocations and refunds</small></div>
            </div>
            <div class="km-stat-card">
                <span class="km-stat-icon km-out" aria-hidden="true">↑</span>
                <div><span>Total credits spent</span><strong>{{ number_format($totalSpent) }}</strong><small>Purchases and deductions</small></div>
            </div>
        </div>
    </div>
@include('makeme.participant.kolo.partials.transfer-modal')
    <section class="km-history" aria-labelledby="km-history-title">
        <div class="km-history-head">
            <div>
                <p class="cp-overline">WALLET ACTIVITY</p>
                <h2 id="km-history-title">Transaction History</h2>
                <p>Credits added, purchases, refunds, and adjustments.</p>
            </div>
            <form method="GET" action="{{ route('makeme.participant.kolo.index') }}" class="km-filter-form">
                <label for="km-filter">Show</label>
                <select name="type" id="km-filter" onchange="this.form.submit()">
                    <option value="all" @selected($filter === 'all')>All transactions</option>
                    <option value="in" @selected($filter === 'in')>Credits in</option>
                    <option value="out" @selected($filter === 'out')>Credits out</option>
                </select>
                <noscript><button type="submit" class="km-button">Filter</button></noscript>
            </form>
        </div>

        <div class="km-table-scroll">
            <table class="km-table">
                <thead>
                    <tr>
                        <th scope="col">Activity</th>
                        <th scope="col">Date &amp; time</th>
                        <th scope="col" class="km-right">Amount</th>
                        <th scope="col" class="km-right">Balance after</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($transactions as $transaction)
                    @php
                        $incoming = in_array($transaction->type, ['credit', 'refund'], true);
                        $labels = [
                            'credit' => 'Credits received',
                            'refund' => 'Refund received',
                            'debit' => 'Credit deduction',
                            'purchase' => 'Marketplace purchase',
                        ];
                        $label = $labels[$transaction->type] ?? ucfirst(str_replace('_', ' ', $transaction->type));
                    @endphp
                    <tr>
                        <td>
                            <div class="km-activity-cell">
                                <span class="km-activity-symbol {{ $incoming ? 'km-in' : 'km-out' }}" aria-hidden="true">{{ $incoming ? '↓' : '↑' }}</span>
                                <div>
                                    <strong>{{ $label }}</strong>
                                    @if($transaction->reason)
                                        <small>{{ $transaction->reason }}</small>
                                    @endif
                                    @if($transaction->reference)
                                        <small class="km-reference">Ref: {{ $transaction->reference }}</small>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="km-date">{{ $transaction->created_at?->format('d M Y') ?? '—' }}<small>{{ $transaction->created_at?->format('g:i A') ?? '' }}</small></td>
                        <td class="km-right"><strong class="{{ $incoming ? 'km-positive' : 'km-negative' }}">{{ $incoming ? '+' : '−' }}{{ number_format((int) $transaction->amount) }}</strong><small>credits</small></td>
                        <td class="km-right km-after">{{ number_format((int) $transaction->balance_after) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="km-empty">
                        <span aria-hidden="true">◈</span>
                        <strong>No transactions found</strong>
                        <p>{{ $filter === 'all' ? 'Your wallet activity will appear here as soon as credits are allocated or used.' : 'There are no transactions matching this filter.' }}</p>
                    </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($transactions->hasPages())
            <div class="km-pagination">{{ $transactions->links() }}</div>
        @endif
    </section>

    <p class="km-info-note"><span aria-hidden="true">ⓘ</span> Kolo Money credits are used within Project MakeMe Live. Credit allocations and adjustments are managed by the show administrators.</p>
</div>

@include('makeme.participant.kolo.partials.transfer-modal')
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/makeme-participant-kolo.css') }}?v=1">
@endpush
