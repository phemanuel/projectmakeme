<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MakeMeProduct extends Model
{
    protected $table = 'makeme_products';

    protected $fillable = [
        'name', 'sku', 'description', 'category', 'credit_price',
        'stock_quantity', 'max_per_participant', 'image_path', 'is_active',
    ];

    protected $casts = [
        'credit_price' => 'integer',
        'stock_quantity' => 'integer',
        'max_per_participant' => 'integer',
        'is_active' => 'boolean',
    ];
}
