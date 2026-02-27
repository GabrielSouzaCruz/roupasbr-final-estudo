<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('variants')->active();

        // Busca por texto
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%");
            });
        }

        // Categoria
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Preço mínimo
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        // Preço máximo
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Tamanho (via variants)
        if ($request->filled('size')) {
            $query->whereHas('variants', function ($q) use ($request) {
                $q->where('size', $request->size)->where('stock', '>', 0);
            });
        }

        // Cor (via variants)
        if ($request->filled('color')) {
            $query->whereHas('variants', function ($q) use ($request) {
                $q->where('color', $request->color)->where('stock', '>', 0);
            });
        }

        // Em estoque
        if ($request->boolean('in_stock')) {
            $query->available();
        }

        // Ordenação
        $sort = $request->get('sort', 'created_at');
        $order = $request->get('order', 'desc');
        
        $query->when($sort === 'price', function ($q) use ($order) {
            return $q->orderBy('price', $order);
        })->when($sort === 'rating', function ($q) use ($order) {
            return $q->withAvg('reviews as reviews_avg_rating', 'rating')
                     ->orderByDesc('reviews_avg_rating');
        })->when($sort === 'name', function ($q) use ($order) {
            return $q->orderBy('name', $order);
        })->when($sort === 'created_at', function ($q) use ($order) {
            return $q->orderBy('created_at', $order);
        });

        $products = $query->paginate($request->get('per_page', 12));

        return response()->json($products);
    }

    public function filters()
    {
        // Retorna todos os filtros disponíveis
        $sizes = ProductVariant::select('size')
            ->whereNotNull('size')
            ->where('stock', '>', 0)
            ->distinct()
            ->pluck('size')
            ->sort()
            ->values();

        $colors = ProductVariant::select('color')
            ->whereNotNull('color')
            ->where('stock', '>', 0)
            ->distinct()
            ->pluck('color')
            ->sort()
            ->values();

        $categories = Product::active()
            ->select('category')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        $priceRange = Product::active()
            ->selectRaw('MIN(price) as min, MAX(price) as max')
            ->first();

        return response()->json([
            'sizes' => $sizes,
            'colors' => $colors,
            'categories' => $categories,
            'price_range' => [
                'min' => (float) $priceRange->min,
                'max' => (float) $priceRange->max,
            ],
        ]);
    }

    public function show(string $slug)
    {
        $product = Product::active()
            ->with('variants', 'reviews')
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json($product);
    }

    public function featured()
    {
        $products = Product::active()
            ->available()
            ->with('variants')
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        return response()->json($products);
    }

    public function categories()
    {
        $categories = Product::active()
            ->select('category')
            ->distinct()
            ->pluck('category');

        return response()->json($categories);
    }
}
