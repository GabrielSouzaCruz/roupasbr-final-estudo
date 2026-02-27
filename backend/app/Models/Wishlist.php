<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Wishlist extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'product_variant_id',
        'share_token',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function generateShareableLink(): string
    {
        if (!$this->share_token) {
            $this->share_token = Str::random(32);
            $this->is_public = true;
            $this->save();
        }
        
        return route('wishlist.shared', $this->share_token);
    }

    public static function toggle(User $user, int $productId): self
    {
        $wishlist = self::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();

        if ($wishlist) {
            $wishlist->delete();
            return null;
        }

        return self::create([
            'user_id' => $user->id,
            'product_id' => $productId,
        ]);
    }

    public static function getUserWishlist(User $user)
    {
        return self::where('user_id', $user->id)
            ->with('product.variants')
            ->latest()
            ->get();
    }
}
