<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'order_id',
        'rating',
        'comment',
        'approved',
        'verified_purchase',
        'helpful_count',
    ];

    protected $casts = [
        'rating' => 'integer',
        'approved' => 'boolean',
        'verified_purchase' => 'boolean',
        'helpful_count' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('approved', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('verified_purchase', true);
    }

    public function markAsVerified(): void
    {
        $this->update(['verified_purchase' => true]);
    }

    public function approve(): void
    {
        $this->update(['approved' => true]);
    }

    public function reject(): void
    {
        $this->update(['approved' => false]);
    }

    public function incrementHelpful(): void
    {
        $this->increment('helpful_count');
    }
}
