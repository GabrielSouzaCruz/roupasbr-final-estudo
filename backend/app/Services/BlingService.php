<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BlingService
{
    protected $apiKey;
    protected $storeId;

    public function __construct()
    {
        $this->apiKey = config('services.bling.api_key');
        $this->storeId = config('services.bling.store_id');
    }

    public function createOrder(Order $order): ?array
    {
        try {
            $customer = $order->user;
            $address = $order->address;

            $data = [
                'numeroLoja' => $order->id,
                'data' => $order->created_at->format('Y-m-d'),
                'dataSaida' => $order->created_at->format('Y-m-d'),
                'tipoRecorrencia' => '0',
                'contato' => [
                    'nome' => $customer->name,
                    'tipoPessoa' => $customer->cpf ? 'F' : 'J',
                    'numeroDocumento' => $customer->cpf ?? '',
                    'email' => $customer->email,
                    'telefone' => $customer->phone ?? '',
                ],
                'enderecoEntrega' => [
                    'endereco' => $address->street,
                    'numero' => $address->number,
                    'complemento' => $address->complemento ?? '',
                    'bairro' => $address->neighborhood,
                    'cidade' => $address->city,
                    'uf' => $address->state,
                    'cep' => preg_replace('/[^0-9]/', '', $address->cep),
                ],
                'itens' => $order->items->map(fn($item) => [
                    'codigo' => $item->product_id,
                    'unidade' => 'UN',
                    'quantidade' => $item->quantity,
                    'desconto' => 0,
                    'valor' => $item->unit_price,
                    'aliquotaIPI' => 0,
                ])->toArray(),
                'transporte' => [
                    'fretePorConta' => '0',
                    'frete' => $order->shipping_cost,
                    'quantidadeVolumes' => 1,
                    'pesoBruto' => 1,
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post('https://api.bling.com.br/Api/v3/pedidos/vendas', $data);

            if ($response->successful()) {
                $result = $response->json();
                $order->update([
                    'bling_order_id' => $result['data']['id'] ?? null,
                ]);
                
                Log::info("Pedido enviado ao Bling: {$order->order_number}", [
                    'bling_id' => $result['data']['id'] ?? null,
                ]);

                return $result['data'];
            }

            Log::error("Erro ao enviar pedido ao Bling: {$order->order_number}", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error("Exceção ao enviar pedido ao Bling: " . $e->getMessage());
            return null;
        }
    }

    public function syncProducts(): void
    {
        // Implementar sincronização de produtos
    }

    public function updateStock(int $productId, int $quantity): void
    {
        // Implementar atualização de estoque
    }
}
