<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MakeMeStockMovement extends Model
{
    protected $table = 'makeme_stock_movements';

    protected $fillable = [
        'product_id',
        'performed_by',
        'type',
        'quantity_before',
        'quantity_change',
        'quantity_after',
        'reason',
    ];

    protected $casts = [
        'quantity_before' => 'integer',
        'quantity_change' => 'integer',
        'quantity_after' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(
            MakeMeProduct::class,
            'product_id'
        );
    }

    public function administrator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'performed_by'
        );
    }
}
