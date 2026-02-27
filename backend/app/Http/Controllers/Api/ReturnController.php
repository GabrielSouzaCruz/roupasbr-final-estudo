<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReturnItem;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReturnController extends Controller
{
    public function index(Request $request)
    {
        $returns = ReturnItem::where('user_id', $request->user()->id)
            ->with('order', 'item.product')
            ->latest()
            ->paginate(10);

        return response()->json($returns);
    }

    public function store(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'order_item_id' => 'required|exists:order_items,id',
            'reason' => 'required|string|max:1000',
            'images' => 'nullable|array',
        ]);

        $user = $request->user();
        $order = Order::where('id', $request->order_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Verificar se pedido está dentro do prazo (7 dias úteis)
        if ($order->delivered_at && $order->delivered_at->addDays(7)->isPast()) {
            return response()->json([
                'error' => 'Prazo para devolução expirado (7 dias úteis)',
            ], 400);
        }

        $orderItem = $order->items()->findOrFail($request->order_item_id);

        $return = ReturnItem::create([
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'user_id' => $user->id,
            'reason' => $request->reason,
            'images' => $request->images ?? [],
            'refund_amount' => $orderItem->total,
            'status' => 'requested',
        ]);

        return response()->json([
            'message' => 'Solicitação de devolução enviada',
            'return' => $return,
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $return = ReturnItem::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->with('order', 'item.product')
            ->firstOrFail();

        return response()->json($return);
    }

    public function uploadLabel(Request $request, $id)
    {
        $return = ReturnItem::findOrFail($id);
        
        // Gerar QR Code da etiqueta (integrar com Correios)
        $qrCodeUrl = $return->generateQRCode();

        return response()->json([
            'qr_code_url' => $qrCodeUrl,
            'message' => 'Etiqueta gerada com sucesso',
        ]);
    }
}
