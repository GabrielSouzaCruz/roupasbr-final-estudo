<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use App\Models\Product;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $wishlist = Wishlist::getUserWishlist($user);
        
        return response()->json($wishlist);
    }

    public function toggle(Request $request, $productId)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'Faça login para usar a wishlist'], 401);
        }

        $product = Product::findOrFail($productId);
        $result = Wishlist::toggle($user, $productId);

        return response()->json([
            'message' => $result ? 'Adicionado à wishlist' : 'Removido da wishlist',
            'in_wishlist' => $result !== null,
        ]);
    }

    public function share($token)
    {
        $wishlist = Wishlist::where('share_token', $token)
            ->where('is_public', true)
            ->with('product.variants')
            ->get();

        if ($wishlist->isEmpty()) {
            return response()->json(['error' => 'Wishlist não encontrada'], 404);
        }

        return response()->json([
            'wishlist' => $wishlist,
            'owner' => $wishlist->first()->user->name,
        ]);
    }

    public function crossSell($productId)
    {
        $product = Product::findOrFail($productId);
        
        // Produtos da mesma categoria
        $crossSell = Product::active()
            ->available()
            ->where('category', $product->category)
            ->where('id', '!=', $productId)
            ->with('variants')
            ->limit(4)
            ->get();

        // Também poderia usar: "clientes que compraram X também compraram Y"
        // Baseado em histórico de pedidos

        return response()->json($crossSell);
    }
}
