<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MakeMeOrder extends Model
{
    protected $table = 'makeme_orders';

    protected $fillable = [
        'request_key', 'reference', 'participant_id', 'created_by',
        'fulfilled_by', 'cancelled_by', 'status', 'total_credits',
        'notes', 'cancellation_reason', 'fulfilled_at', 'cancelled_at',
    ];

    protected $casts = [
        'total_credits' => 'integer',
        'fulfilled_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(MakeMeParticipant::class, 'participant_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MakeMeOrderItem::class, 'order_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function fulfiller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fulfilled_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
