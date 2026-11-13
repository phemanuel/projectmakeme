<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MakeMeParticipant;
use App\Models\MakeMeWallet;
use App\Models\MakeMeWalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Services\MakeMeActivityLogger;

class MakeMeWalletController extends Controller
{
    private const MAX_BALANCE = 999999999;

    /*
    |--------------------------------------------------------------------------
    | AUTHORIZATION
    |--------------------------------------------------------------------------
    */

    private function authorizeAction(string $permission): void
    {
        $admin = auth('web')->user();

        abort_unless(
            $admin &&
            (int) $admin->user_type === 1 &&
            (int) $admin->user_status === 1 &&
            $admin->applicant_type !== 'WAITLIST' &&
            (
                $admin->hasRole('Super Admin', 'web') ||
                $admin->can($permission)
            ),
            403,
            'You do not have permission to perform this action.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ELIGIBLE PARTICIPANTS
    |--------------------------------------------------------------------------
    */

    private function eligibleParticipants()
    {
        return MakeMeParticipant::query()
            ->where('status', 'active');
    }

    /*
    |--------------------------------------------------------------------------
    | WALLET MANAGEMENT DASHBOARD
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $this->authorizeAction('makeme.wallets.view');

        $validated = $request->validate([
            'q' => [
                'nullable',
                'string',
                'max:100',
            ],
            'balance' => [
                'nullable',
                Rule::in([
                    'all',
                    'positive',
                    'zero',
                ]),
            ],
        ]);

        /*
        |--------------------------------------------------------------
        | Participant listing
        |--------------------------------------------------------------
        |
        | Participants are loaded directly from
        | makeme_participants, not the users table.
        |
        */

        $query = $this->eligibleParticipants()
            ->with([
                'wallet',
                'wallet.transactions' => function ($query) {
                    $query->with('administrator')
                        ->latest('id')
                        ->limit(20);
                },
            ]);

        /*
        |--------------------------------------------------------------
        | Search participants
        |--------------------------------------------------------------
        |
        | Check available columns so the search can support
        | the participant database's existing name fields.
        |
        */

        if (!empty($validated['q'])) {
            $term = $validated['q'];

            $searchableCandidates = [
                'first_name',
                'surname',
                'other_name',
                'full_name',
                'name',
                'email',
                'mobile_no',
                'phone',
            ];

            $availableColumns = Schema::getColumnListing(
                'makeme_participants'
            );

            $searchColumns = array_values(
                array_intersect(
                    $searchableCandidates,
                    $availableColumns
                )
            );

            if (!empty($searchColumns)) {
                $query->where(function ($builder) use (
                    $term,
                    $searchColumns
                ) {
                    foreach ($searchColumns as $column) {
                        $builder->orWhere(
                            $column,
                            'like',
                            '%' . $term . '%'
                        );
                    }
                });
            }
        }

        /*
        |--------------------------------------------------------------
        | Filter by wallet balance
        |--------------------------------------------------------------
        */

        $balanceFilter = $validated['balance'] ?? 'all';

        if ($balanceFilter === 'positive') {
            $query->whereHas('wallet', function ($wallet) {
                $wallet->where('balance', '>', 0);
            });
        }

        if ($balanceFilter === 'zero') {
            $query->where(function ($builder) {
                $builder
                    ->whereDoesntHave('wallet')
                    ->orWhereHas('wallet', function ($wallet) {
                        $wallet->where('balance', 0);
                    });
            });
        }

        /*
        |--------------------------------------------------------------
        | Paginate participants
        |--------------------------------------------------------------
        */

        $participants = $query
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        /*
        |--------------------------------------------------------------
        | Wallet statistics
        |--------------------------------------------------------------
        |
        | Available credits reflect all existing participant
        | wallets, including wallets belonging to participants
        | who are currently inactive.
        |
        */

        $stats = [
            'active_participants' =>
                $this->eligibleParticipants()->count(),

            'available_credits' =>
                MakeMeWallet::query()->sum('balance'),

            'credits_issued' =>
                MakeMeWalletTransaction::query()
                    ->whereIn('type', [
                        'credit',
                        'refund',
                    ])
                    ->sum('amount'),

            'credits_deducted' =>
                MakeMeWalletTransaction::query()
                    ->whereIn('type', [
                        'debit',
                        'purchase',
                    ])
                    ->sum('amount'),
        ];

        return view(
            'admin.makeme.wallets.index',
            compact(
                'participants',
                'stats'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ADJUST PARTICIPANT WALLET
    |--------------------------------------------------------------------------
    */

    public function adjust(
        Request $request,
        MakeMeParticipant $participant
    ) {
        $this->authorizeAction('makeme.wallets.adjust');

        /*
        |--------------------------------------------------------------
        | Validate transaction data
        |--------------------------------------------------------------
        */

        $validated = $request->validate([
            'type' => [
                'required',
                Rule::in([
                    'credit',
                    'debit',
                ]),
            ],

            'amount' => [
                'required',
                'integer',
                'min:1',
                'max:' . self::MAX_BALANCE,
            ],

            'reason' => [
                'required',
                'string',
                'min:5',
                'max:255',
            ],
        ]);

        /*
        |--------------------------------------------------------------
        | Execute transaction safely
        |--------------------------------------------------------------
        */

        DB::transaction(function () use (
            $participant,
            $validated
        ) {

            /*
            |----------------------------------------------------------
            | Lock the participant record
            |----------------------------------------------------------
            |
            | This serializes first-time wallet creation and
            | adjustments for the same participant.
            |
            */

            $lockedParticipant = MakeMeParticipant::query()
                ->lockForUpdate()
                ->findOrFail($participant->getKey());

            /*
            |----------------------------------------------------------
            | Verify participant eligibility
            |----------------------------------------------------------
            */

            abort_unless(
                $lockedParticipant->status === 'active',
                422,
                'Wallet adjustments are allowed only for active participants.'
            );

            /*
            |----------------------------------------------------------
            | Find and lock the wallet
            |----------------------------------------------------------
            */

            $wallet = MakeMeWallet::query()
                ->where(
                    'participant_id',
                    $lockedParticipant->getKey()
                )
                ->lockForUpdate()
                ->first();

            /*
            |----------------------------------------------------------
            | Create wallet on first credit allocation
            |----------------------------------------------------------
            */

            if (!$wallet) {

                if ($validated['type'] === 'debit') {
                    throw ValidationException::withMessages([
                        'amount' =>
                            'This participant has no credits to deduct.',
                    ]);
                }

                $wallet = MakeMeWallet::create([
                    'participant_id' =>
                        $lockedParticipant->getKey(),

                    'balance' => 0,
                ]);
            }

            /*
            |----------------------------------------------------------
            | Calculate balance
            |----------------------------------------------------------
            */

            $balanceBefore = (int) $wallet->balance;
            $amount = (int) $validated['amount'];

            if ($validated['type'] === 'credit') {
                $balanceAfter = $balanceBefore + $amount;
            } else {
                $balanceAfter = $balanceBefore - $amount;
            }

            /*
            |----------------------------------------------------------
            | Prevent negative balance
            |----------------------------------------------------------
            */

            if ($balanceAfter < 0) {
                throw ValidationException::withMessages([
                    'amount' =>
                        'Insufficient wallet balance. ' .
                        'You cannot deduct more credits than available.',
                ]);
            }

            /*
            |----------------------------------------------------------
            | Prevent balance overflow
            |----------------------------------------------------------
            */

            if ($balanceAfter > self::MAX_BALANCE) {
                throw ValidationException::withMessages([
                    'amount' =>
                        'Wallet balance cannot exceed ' .
                        number_format(self::MAX_BALANCE) .
                        ' credits.',
                ]);
            }

            /*
            |----------------------------------------------------------
            | Update wallet balance
            |----------------------------------------------------------
            */

            $wallet->balance = $balanceAfter;
            $wallet->save();

            
            /*
            |--------------------------------------------------------------------------
            | Record Transaction History
            |--------------------------------------------------------------------------
            */

            $wallet->transactions()->create([
                'performed_by' => auth('web')->id(),
                'type' => $validated['type'],
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reason' => trim($validated['reason']),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Record Activity Log
            |--------------------------------------------------------------------------
            | Keep logging inside the transaction so the variables
            | remain in scope and the operation stays atomic.
            */

            MakeMeActivityLogger::record(
                'wallets',

                $validated['type'] === 'credit'
                    ? 'credit_added'
                    : 'credit_deducted',

                $validated['type'] === 'credit'
                    ? 'Credits allocated to participant wallet'
                    : 'Credits deducted from participant wallet',

                $wallet,

                [
                    'participant_id' => $lockedParticipant->getKey(),
                    'wallet_id' => $wallet->id,
                    'transaction_type' => $validated['type'],
                    'amount' => $amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'reason' => trim($validated['reason']),
                ]
            );

            }, 3);



        /*
        |--------------------------------------------------------------
        | Success response
        |--------------------------------------------------------------
        */

        return redirect()
            ->route('admin.makeme.wallets.index')
            ->with(
                'success',
                'Participant wallet updated successfully.'
            );
    }
}
