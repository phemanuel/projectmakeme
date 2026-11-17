<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\MakeMeWallet;
use App\Models\MakeMeWalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MakeMeParticipantKoloController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'type' => ['nullable', Rule::in(['all', 'in', 'out'])],
        ]);

        $participant = auth('makeme')->user();
        abort_unless($participant && $participant->status === 'active', 403);

        // Resolve ONLY the authenticated contestant's wallet. Never accept a wallet_id
        // or participant_id from the URL/query string.
        $wallet = MakeMeWallet::query()
            ->where('participant_id', $participant->getKey())
            ->first();

        $balance = (int) ($wallet?->balance ?? 0);
        // Make the authoritative wallet balance available to the transfer modal.
        $walletBalance = $balance;
        $walletId = $wallet?->getKey() ?? -1;

        $base = MakeMeWalletTransaction::query()->where('wallet_id', $walletId);

        // These are ledger totals, not substitutes for the wallet's authoritative balance.
        $totalReceived = (int) (clone $base)
            ->whereIn('type', ['credit', 'refund'])
            ->sum('amount');

        $totalSpent = (int) (clone $base)
            ->whereIn('type', ['debit', 'purchase'])
            ->sum('amount');

        $filter = $validated['type'] ?? 'all';
        $transactions = (clone $base)
            ->when($filter === 'in', fn ($q) => $q->whereIn('type', ['credit', 'refund']))
            ->when($filter === 'out', fn ($q) => $q->whereIn('type', ['debit', 'purchase']))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        
        $transferRecipients = \App\Models\MakeMeParticipant::query()
            ->where('status', 'active')
            ->whereKeyNot(auth('makeme')->id())
            ->whereHas('wallet')
            ->orderBy('first_name')
            ->orderBy('surname')
            ->get([
                'id',
                'first_name',
                'other_name',
                'surname',
                'participant_number',
            ]);


        return view('makeme.participant.kolo.index', compact(
            'participant', 'balance', 'totalReceived', 'totalSpent',
            'transactions', 'filter','transferRecipients','walletBalance'
        ));
    }
}
