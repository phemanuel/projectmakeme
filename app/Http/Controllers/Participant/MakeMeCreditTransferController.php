<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\MakeMeParticipant;
use App\Models\MakeMeWallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MakeMeCreditTransferController extends Controller
{
    private const MAX_BALANCE = 999999999;

    public function store(Request $request)
    {
        $data = $request->validate([
            'recipient_id' => [
                'required', 'integer',
                'exists:makeme_participants,id'
            ],
            'amount' => [
                'required', 'integer',
                'min:1',
                'max:999999999'
            ],
            'password' => ['required', 'string'],
            'request_key' => ['required', 'uuid'],
        ]);

        $senderId = (int) auth('makeme')->id();
        $recipientId = (int) $data['recipient_id'];
        $amount = (int) $data['amount'];

        abort_unless($senderId, 403);

        if ($senderId === $recipientId) {
            throw ValidationException::withMessages([
                'recipient_id' => 'You cannot transfer credits to yourself.',
            ]);
        }

        $transfer = DB::transaction(function () use (
            $senderId,
            $recipientId,
            $amount,
            $data
        ) {
            // Lock both participant records in stable order.
            $participants = MakeMeParticipant::query()
                ->whereIn('id', [$senderId, $recipientId])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $sender = $participants->get($senderId);
            $recipient = $participants->get($recipientId);

            abort_unless(
                $sender && $sender->status === 'active',
                403
            );

            if (!$recipient || $recipient->status !== 'active') {
                throw ValidationException::withMessages([
                    'recipient_id' =>
                        'This Kreativ is not available for transfers.',
                ]);
            }

            // Reuse a committed transfer if the same request
            // was already successfully processed.
            $existing = DB::table('makeme_credit_transfers')
                ->where('request_key', $data['request_key'])
                ->first();

            if ($existing) {
                if ((int) $existing->sender_id !== $senderId) {
                    abort(403);
                }

                if (
                    (int) $existing->recipient_id !== $recipientId ||
                    (int) $existing->amount !== $amount
                ) {
                    throw ValidationException::withMessages([
                        'amount' => 'This transfer request has already been used.',
                    ]);
                }

                return $existing->reference;
            }

            // Authenticate the sender.
            if (!Hash::check($data['password'], $sender->password)) {
                throw ValidationException::withMessages([
                    'password' => 'Incorrect password.',
                ]);
            }

            // Lock both wallets in stable order.
            $wallets = MakeMeWallet::query()
                ->whereIn('participant_id', [$senderId, $recipientId])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('participant_id');

            $senderWallet = $wallets->get($senderId);
            $recipientWallet = $wallets->get($recipientId);

            if (!$senderWallet || !$recipientWallet) {
                throw ValidationException::withMessages([
                    'recipient_id' =>
                        'One of the wallets is unavailable.',
                ]);
            }

            $senderBefore = (int) $senderWallet->balance;
            $recipientBefore = (int) $recipientWallet->balance;

            if ($senderBefore < $amount) {
                throw ValidationException::withMessages([
                    'amount' => 'Insufficient KoloMoni credits.',
                ]);
            }

            if ($amount > self::MAX_BALANCE - $recipientBefore) {
                throw ValidationException::withMessages([
                    'amount' => 'Recipient wallet balance limit exceeded.',
                ]);
            }

            $senderAfter = $senderBefore - $amount;
            $recipientAfter = $recipientBefore + $amount;

            $reference = 'KMT-' .
                strtoupper((string) Str::ulid());

            $senderWallet->balance = $senderAfter;
            $senderWallet->save();

            $recipientWallet->balance = $recipientAfter;
            $recipientWallet->save();

            // Reuse the existing wallet ledger format.
            // Confirm these type values are permitted by
            // the deployed wallet transaction schema.
            $senderWallet->transactions()->create([
                'performed_by' => null,
                'type' => 'debit',
                'amount' => $amount,
                'balance_before' => $senderBefore,
                'balance_after' => $senderAfter,
                'reason' => 'Transfer to Kreativ #' . $recipientId,
                'reference' => $reference . ':sent',
            ]);

            $recipientWallet->transactions()->create([
                'performed_by' => null,
                'type' => 'credit',
                'amount' => $amount,
                'balance_before' => $recipientBefore,
                'balance_after' => $recipientAfter,
                'reason' => 'Transfer from Kreativ #' . $senderId,
                'reference' => $reference . ':received',
            ]);

            DB::table('makeme_credit_transfers')->insert([
                'request_key' => $data['request_key'],
                'reference' => $reference,
                'sender_id' => $senderId,
                'recipient_id' => $recipientId,
                'amount' => $amount,
                'sender_balance_before' => $senderBefore,
                'sender_balance_after' => $senderAfter,
                'recipient_balance_before' => $recipientBefore,
                'recipient_balance_after' => $recipientAfter,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $reference;
        }, 3);

        return redirect()
            ->route('makeme.participant.kolo.index')
            ->with(
                'success',
                "Transfer completed successfully. Reference: {$transfer}"
            );
    }
}
