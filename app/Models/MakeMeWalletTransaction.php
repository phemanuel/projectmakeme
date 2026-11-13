<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MakeMeWalletTransaction extends Model
{
    protected $table = 'makeme_wallet_transactions';

    protected $fillable = [
        'wallet_id',
        'performed_by',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'reason',
        'reference',
    ];

    protected $casts = [
        'amount' => 'integer',
        'balance_before' => 'integer',
        'balance_after' => 'integer',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(MakeMeWallet::class, 'wallet_id');
    }

    public function administrator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
