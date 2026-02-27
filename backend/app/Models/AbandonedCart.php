<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AbandonedCart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cart_items',
        'total',
        'abandoned_at',
        'recovered',
        'email_sent_at',
    ];

    protected $casts = [
        'cart_items' => 'array',
        'total' => 'decimal:2',
        'abandoned_at' => 'datetime',
        'recovered' => 'boolean',
        'email_sent_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function markAsRecovered(): void
    {
        $this->update(['recovered' => true]);
    }

    public function markEmailAsSent(): void
    {
        $this->update(['email_sent_at' => now()]);
    }

    public static function saveFromSession(User $user, array $cartItems): void
    {
        if (empty($cartItems)) {
            return;
        }

        $total = collect($cartItems)->sum(fn($i) => $i['price'] * $i['quantity']);

        self::updateOrCreate(
            ['user_id' => $user->id, 'recovered' => false],
            [
                'cart_items' => $cartItems,
                'total' => $total,
                'abandoned_at' => now(),
            ]
        );
    }

    public function getItemCount(): int
    {
        return collect($this->cart_items)->sum('quantity');
    }
}
