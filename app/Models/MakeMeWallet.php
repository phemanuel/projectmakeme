<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class MakeMeWallet extends Model
{
    protected $table = 'makeme_wallets';

    protected $fillable = [
        'participant_id',
        'balance',
    ];

    protected $casts = [
        'balance' => 'integer',
    ];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(
            MakeMeParticipant::class,
            'participant_id',
            'id'
        );
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(
            MakeMeWalletTransaction::class,
            'wallet_id',
            'id'
        )->latest('id');
    }
}
