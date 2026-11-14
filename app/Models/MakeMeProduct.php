<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MakeMeProduct extends Model
{
    protected $table = 'makeme_products';

    protected $fillable = [
        'name',
        'unit_label',
        'sku',
        'description',
        'category',
        'credit_price',
        'stock_quantity',
        'max_per_participant',
        'image_path',
        'is_active',
    ];

    protected $casts = [
        'credit_price' => 'integer',
        'stock_quantity' => 'integer',
        'max_per_participant' => 'integer',
        'is_active' => 'boolean',
    ];

    public function stockMovements(): HasMany
    {
        return $this->hasMany(
            MakeMeStockMovement::class,
            'product_id'
        )->latest();
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->stock_quantity <= 0) {
            return 'out_of_stock';
        }

        if ($this->stock_quantity <= 5) {
            return 'low_stock';
        }

        return 'in_stock';
    }


    public function scopeVisibleToParticipant(
        \Illuminate\Database\Eloquent\Builder $query,
        \App\Models\MakeMeParticipant $participant
    ): \Illuminate\Database\Eloquent\Builder {

        return $query->where(function ($q) use ($participant) {

            $q->whereIn('category', [
                'general',
                'groceries',
            ]);

            if (!empty($participant->skill_category)) {
                $q->orWhere(
                    'category',
                    $participant->skill_category
                );
            }

        });
    }

}
