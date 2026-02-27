<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AbandonedCart;
use Illuminate\Http\Request;

class AbandonedCartController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'cart_items' => 'required|array',
        ]);

        $user = $request->user();
        
        if (!$user) {
            // Salvar por session/email para usuários não logados
            return response()->json(['message' => 'Carrinho salvo temporariamente'], 200);
        }

        AbandonedCart::saveFromSession($user, $request->cart_items);

        return response()->json(['message' => 'Carrinho salvo com sucesso'], 200);
    }

    public function recover(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['cart_items' => []], 200);
        }

        $abandonedCart = AbandonedCart::where('user_id', $user->id)
            ->where('recovered', false)
            ->latest()
            ->first();

        if ($abandonedCart) {
            $abandonedCart->markAsRecovered();
            
            return response()->json([
                'cart_items' => $abandonedCart->cart_items,
                'total' => $abandonedCart->total,
            ], 200);
        }

        return response()->json(['cart_items' => []], 200);
    }
}
