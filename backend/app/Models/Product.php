<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'description', 'price', 'compare_at_price',
        'stock', 'status', 'category', 'sizes', 'colors',
        'main_image', 'images', 'weight',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'sizes' => 'array',
        'colors' => 'array',
        'images' => 'array',
        'weight' => 'decimal:3',
    ];

    public static function boot()
    {
        parent::boot();
        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeAvailable($query)
    {
        return $query->where('stock', '>', 0);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function hasStock(int $quantity = 1): bool
    {
        // Se tiver variants, verifica o estoque deles
        if ($this->variants->isNotEmpty()) {
            return $this->variants->sum('stock') >= $quantity;
        }
        return $this->stock >= $quantity;
    }

    public function decreaseStock(int $quantity): void
    {
        if ($this->variants->isNotEmpty()) {
            // Baixa do primeiro variant com estoque
            $variant = $this->variants->firstWhere('stock', '>=', $quantity);
            if ($variant) {
                $variant->decreaseStock($quantity);
            }
        } else {
            $this->decrement('stock', $quantity);
        }
    }

    public function increaseStock(int $quantity): void
    {
        if ($this->variants->isNotEmpty()) {
            $this->variants->first()?->increaseStock($quantity);
        } else {
            $this->increment('stock', $quantity);
        }
    }

    public function getVariantBySizeAndColor($size, $color)
    {
        return $this->variants()
            ->where('size', $size)
            ->where('color', $color)
            ->first();
    }
}

