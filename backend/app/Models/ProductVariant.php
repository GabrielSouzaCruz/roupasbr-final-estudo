<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'size',
        'color',
        'stock',
        'price_override',
        'image',
    ];

    protected $casts = [
        'price_override' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function hasStock(int $qty = 1): bool
    {
        return $this->stock >= $qty;
    }

    public function decreaseStock(int $qty): void
    {
        $this->decrement('stock', $qty);
    }

    public function increaseStock(int $qty): void
    {
        $this->increment('stock', $qty);
    }

    public function getPriceAttribute(): float
    {
        return $this->price_override ?? $this->product->price;
    }
}
