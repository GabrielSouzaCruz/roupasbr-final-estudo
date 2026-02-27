<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number', 'user_id', 'address_id', 'subtotal',
        'shipping_cost', 'discount', 'total', 'status',
        'payment_method', 'payment_status', 'payment_id',
        'payment_data', 'notes', 'paid_at', 'shipped_at', 
        'delivered_at', 'tracking_code', 'tracking_url', 'coupon_code',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'payment_data' => 'array',
        'paid_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();
        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = 'ORD-' . strtoupper(Str::random(8));
            }
        });
    }

    public function user() { return $this->belongsTo(User::class); }
    public function address() { return $this->belongsTo(Address::class); }
    public function items() { return $this->hasMany(OrderItem::class); }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isPending(): bool
    {
        return $this->payment_status === 'pending';
    }

    public function isShipped(): bool
    {
        return $this->status === 'shipped';
    }

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function markAsPaid(string $paymentId, array $paymentData = []): void
    {
        $this->update([
            'payment_status' => 'paid',
            'payment_id' => $paymentId,
            'payment_data' => $paymentData,
            'paid_at' => now(),
            'status' => 'processing',
        ]);
    }

    public function markAsShipped(string $trackingCode = null): void
    {
        $this->update([
            'status' => 'shipped',
            'shipped_at' => now(),
            'tracking_code' => $trackingCode,
            'tracking_url' => $trackingCode 
                ? "https://www.melhorenvio.com.br/rastreio/{$trackingCode}"
                : null,
        ]);
    }

    public function markAsDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
            'payment_status' => 'refunded',
        ]);

        // Restock products
        foreach ($this->items as $item) {
            $item->product->increaseStock($item->quantity);
        }
    }

    public function getTrackingUrlAttribute(): ?string
    {
        if ($this->attributes['tracking_url']) {
            return $this->attributes['tracking_url'];
        }
        
        if ($this->attributes['tracking_code']) {
            return "https://www.melhorenvio.com.br/rastreio/{$this->attributes['tracking_code']}";
        }
        
        return null;
    }
}
