<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Product;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, $productId)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();
        $product = Product::findOrFail($productId);

        // Verificar se usuário comprou o produto
        $purchased = \App\Models\OrderItem::where('product_id', $productId)
            ->whereHas('order', fn($q) => $q->where('user_id', $user->id)->whereIn('status', ['delivered', 'shipped']))
            ->exists();

        if (!$purchased) {
            return response()->json(['error' => 'Apenas clientes que compraram podem avaliar'], 403);
        }

        // Verificar se já avaliou
        $existing = Review::where('product_id', $productId)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return response()->json(['error' => 'Você já avaliou este produto'], 400);
        }

        $review = Review::create([
            'user_id' => $user->id,
            'product_id' => $productId,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'verified_purchase' => true,
            'approved' => false, // Precisa de aprovação
        ]);

        return response()->json([
            'message' => 'Avaliação enviada para aprovação',
            'review' => $review,
        ], 201);
    }

    public function index($productId)
    {
        $reviews = Review::where('product_id', $productId)
            ->approved()
            ->with('user:id,name')
            ->latest()
            ->paginate(10);

        return response()->json($reviews);
    }

    public function helpful(Request $request, $reviewId)
    {
        $review = Review::findOrFail($reviewId);
        $review->incrementHelpful();

        return response()->json(['message' => 'Obrigado pelo feedback!']);
    }
}
