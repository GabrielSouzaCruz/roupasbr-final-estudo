<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = $request->user()
            ->orders()
            ->with('items.product', 'address')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($orders);
    }

    public function show(Request $request, string $orderNumber)
    {
        $order = $request->user()
            ->orders()
            ->where('order_number', $orderNumber)
            ->with('items.product', 'address')
            ->firstOrFail();

        return response()->json($order);
    }

    public function store(Request $request)
    {
        $request->validate([
            'address_id' => 'required|exists:addresses,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.size' => 'nullable|string',
            'items.*.color' => 'nullable|string',
            'payment_method' => 'required|in:credit_card,pix,boleto',
            'payment_data' => 'required|array',
        ]);

        return DB::transaction(function () use ($request) {
            $user = $request->user();
            $items = $request->items;
            $subtotal = 0;
            $validatedItems = [];

            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);

                if (!$product->hasStock($item['quantity'])) {
                    throw new \Exception("Produto {$product->name} sem estoque suficiente");
                }

                $unitPrice = $product->price;
                $total = $unitPrice * $item['quantity'];
                $subtotal += $total;

                $validatedItems[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'size' => $item['size'] ?? null,
                    'color' => $item['color'] ?? null,
                    'unit_price' => $unitPrice,
                    'total' => $total,
                ];
            }

            $shippingCost = $subtotal > 299 ? 0 : 25.90;
            $total = $subtotal + $shippingCost;

            $order = Order::create([
                'user_id' => $user->id,
                'address_id' => $request->address_id,
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'discount' => 0,
                'total' => $total,
                'status' => 'pending',
                'payment_method' => $request->payment_method,
                'payment_status' => 'pending',
            ]);

            foreach ($validatedItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product']->id,
                    'product_name' => $item['product']->name,
                    'size' => $item['size'],
                    'color' => $item['color'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['total'],
                ]);

                $item['product']->decreaseStock($item['quantity']);
            }

            return response()->json([
                'order' => $order->load('items', 'address'),
                'payment' => ['status' => 'pending'],
            ], 201);
        });
    }
}
