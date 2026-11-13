<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MakeMeOrderItem extends Model
{
    protected $table = 'makeme_order_items';

    protected $fillable = [
        'order_id', 'product_id', 'product_name', 'sku',
        'quantity', 'unit_price', 'line_total',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'integer',
        'line_total' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(MakeMeOrder::class, 'order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(MakeMeProduct::class, 'product_id');
    }
}
