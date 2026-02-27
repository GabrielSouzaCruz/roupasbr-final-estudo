<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'discount_value',
        'discount_type',
        'min_purchase',
        'max_discount',
        'max_uses',
        'used',
        'max_uses_per_user',
        'expires_at',
        'active',
        'applicable_products',
        'applicable_categories',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'min_purchase' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'expires_at' => 'datetime',
        'active' => 'boolean',
        'applicable_products' => 'array',
        'applicable_categories' => 'array',
    ];

    public function isValid(): bool
    {
        if (!$this->active) {
            return false;
        }

        if ($this->max_uses > 0 && $this->used >= $this->max_uses) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function isValidForUser(User $user): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        $userUses = Order::where('user_id', $user->id)
            ->where('coupon_code', $this->code)
            ->count();

        if ($this->max_uses_per_user > 0 && $userUses >= $this->max_uses_per_user) {
            return false;
        }

        return true;
    }

    public function isValidForCart(float $subtotal, array $items): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        if ($subtotal < $this->min_purchase) {
            return false;
        }

        // Verificar produtos aplicáveis
        if (!empty($this->applicable_products)) {
            $productIds = collect($items)->pluck('product_id')->toArray();
            if (empty(array_intersect($productIds, $this->applicable_products))) {
                return false;
            }
        }

        // Verificar categorias aplicáveis
        if (!empty($this->applicable_categories)) {
            // Implementar lógica de verificação de categorias
        }

        return true;
    }

    public function calculateDiscount(float $subtotal): float
    {
        if ($this->discount_type === 'percent') {
            $discount = $subtotal * ($this->discount_value / 100);
            
            if ($this->max_discount && $discount > $this->max_discount) {
                $discount = $this->max_discount;
            }
            
            return $discount;
        }

        return min($this->discount_value, $subtotal);
    }

    public function incrementUsed(): void
    {
        $this->increment('used');
    }
}
