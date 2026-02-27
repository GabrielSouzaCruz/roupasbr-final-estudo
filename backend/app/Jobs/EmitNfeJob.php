<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class EmitNfeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function handle(): void
    {
        $token = config('services.focus_nfe.token');
        $environment = config('services.focus_nfe.environment', 'homologacao');

        $baseUrl = $environment === 'production'
            ? 'https://api.focusnfe.com.br'
            : 'https://hom.focusnfe.com.br';

        $user = $this->order->user;
        $address = $this->order->address;

        $data = [
            'natureza_operacao' => 'Venda de mercadoria',
            'data_emissao' => now()->format('Y-m-d\TH:i:s-03:00'),
            'data_saida_entrada' => now()->format('Y-m-d\TH:i:s-03:00'),
            'tipo_documento' => '1',
            'tipo_impressao' => '4',
            'finalidade_emissao' => '1',
            'cliente' => [
                'cpf_cnpj' => $user->cpf ?? '00000000000',
                'nome' => $user->name,
                'email' => $user->email,
                'endereco' => $address->street,
                'numero' => $address->number,
                'complemento' => $address->complemento ?? '',
                'bairro' => $address->neighborhood,
                'cep' => preg_replace('/[^0-9]/', '', $address->cep),
                'municipio' => $address->city,
                'uf' => $address->state,
            ],
            'itens' => $this->order->items->map(function ($item) {
                return [
                    'numero_item' => $item->id,
                    'produto_codigo' => $item->product_id,
                    'produto_nome' => $item->product_name,
                    'produto_ncm' => '61091000',
                    'produto_cfop' => '5102',
                    'produto_cest' => '0804700',
                    'quantidade_comercial' => $item->quantity,
                    'valor_unitario_comercial' => number_format($item->unit_price, 2, '.', ''),
                    'valor_total_bruto' => number_format($item->total, 2, '.', ''),
                ];
            })->toArray(),
            'total_icms' => number_format($this->order->total * 0.18, 2, '.', ''),
            'total_nfe' => number_format($this->order->total, 2, '.', ''),
        ];

        $response = Http::withBasicAuth($token, '')
            ->post("$baseUrl/v2/nfe", $data);

        if ($response->successful()) {
            $result = $response->json();
            $this->order->update([
                'nfe_number' => $result['chave_acesso'] ?? null,
                'nfe_status' => 'emitida',
            ]);
        }
    }
}
