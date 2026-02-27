<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AntiFraudMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $kondutoApiKey = config('services.konduto.api_key');
        
        if (!$kondutoApiKey) {
            return $next($request);
        }

        $orderData = $request->session()->get('pending_order');
        
        if (!$orderData) {
            return $next($request);
        }

        try {
            $response = Http::withHeaders([
                'X-Api-Key' => $kondutoApiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.konduto.com/v1/order', [
                'id' => $orderData['order_number'],
                'total_amount' => (int) ($orderData['total'] * 100),
                'created_at' => now()->toISOString(),
                'customer' => [
                    'id' => $orderData['user_id'],
                    'name' => $orderData['customer_name'],
                    'email' => $orderData['customer_email'],
                    'tax_id' => $orderData['customer_cpf'],
                    'phone' => $orderData['customer_phone'],
                ],
                'billing_addresses' => [[
                    'street' => $orderData['address_street'],
                    'number' => $orderData['address_number'],
                    'complement' => $orderData['address_complement'] ?? '',
                    'neighborhood' => $orderData['address_neighborhood'],
                    'city' => $orderData['address_city'],
                    'state' => $orderData['address_state'],
                    'zip' => preg_replace('/[^0-9]/', '', $orderData['address_cep']),
                    'country' => 'BRA',
                ]],
                'payments' => [[
                    'type' => $orderData['payment_method'],
                    'amount' => (int) ($orderData['total'] * 100),
                ]],
                'items' => collect($orderData['items'])->map(fn($item) => [
                    'sku' => $item['product_id'],
                    'name' => $item['product_name'],
                    'unit_price' => (int) ($item['unit_price'] * 100),
                    'quantity' => $item['quantity'],
                ])->toArray(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            $result = $response->json();
            $score = $result['score'] ?? 0;
            $recommendation = $result['recommendation'] ?? 'APPROVE';

            // Salvar score no pedido
            $request->session()->put('fraud_score', $score);
            $request->session()->put('fraud_recommendation', $recommendation);

            Log::info("Konduto Fraud Score: {$score} - {$recommendation}", [
                'order' => $orderData['order_number'],
            ]);

            // Bloquear se score baixo ou recomendação DECLINE
            if ($recommendation === 'DECLINE' || $score < 75) {
                return response()->json([
                    'message' => 'Pedido em análise anti-fraude. Entraremos em contato.',
                    'fraud_score' => $score,
                ], 403);
            }

        } catch (\Exception $e) {
            Log::error('Erro na análise anti-fraude: ' . $e->getMessage());
            // Em caso de erro, permitir prosseguir (fail-open)
        }

        return $next($request);
    }
}
