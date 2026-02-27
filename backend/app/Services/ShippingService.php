<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;

class ShippingService
{
    protected $clientId;
    protected $clientSecret;
    protected $accessToken;
    protected $cepOrigem;

    public function __construct()
    {
        $this->clientId = config('services.melhor_envio.client_id');
        $this->clientSecret = config('services.melhor_envio.client_secret');
        $this->accessToken = config('services.melhor_envio.access_token');
        $this->cepOrigem = config('services.melhor_envio.cep_origem');
    }

    public function calculate(string $cepDestino, array $items, float $totalWeight = 0.5)
    {
        // Primeiro, obter token via OAuth (fazer uma vez e salvar)
        $tokenResponse = Http::asForm()->post('https://api.melhorenvio.com.br/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        $token = $tokenResponse->json()['access_token'] ?? $this->accessToken;

        // Calcular frete
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
            'User-Agent' => 'RoupasBR/1.0',
        ])->post('https://api.melhorenvio.com.br/api/shipment/calculate', [
            'from' => $this->cepOrigem,
            'to' => $cepDestino,
            'products' => collect($items)->map(function ($item) {
                return [
                    'name' => $item['product_name'] ?? 'Produto',
                    'width' => 30,
                    'height' => 20,
                    'length' => 40,
                    'weight' => $item['weight'] ?? 0.5,
                    'value' => $item['unit_price'] ?? 0,
                    'quantity' => $item['quantity'] ?? 1,
                ];
            })->toArray(),
        ]);

        if ($response->successful()) {
            return $response->json()['services'] ?? [];
        }

        // Fallback: valor fixo
        return [
            ['name' => 'Frete Padrão', 'price' => 25.90, 'delivery_time' => 7],
        ];
    }

    public function getCheapest(array $options)
    {
        if (empty($options)) {
            return 25.90;
        }

        $cheapest = collect($options)->sortBy('price')->first();
        return $cheapest['price'] ?? 25.90;
    }
}
