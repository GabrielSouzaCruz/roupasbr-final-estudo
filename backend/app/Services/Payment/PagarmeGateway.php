<?php

namespace App\Services\Payment;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PagarmeGateway implements PaymentGatewayInterface
{
    private $apiKey;
    private $webhookSecret;

    public function __construct()
    {
        $this->apiKey = config('services.pagarme.api_key');
        $this->webhookSecret = config('services.pagarme.webhook_secret');
    }

    public function process(Order $order, array $data, $user)
    {
        $method = $data['type'] ?? 'pix';

        if ($method === 'pix') {
            return $this->processPix($order, $data, $user);
        } elseif ($method === 'boleto') {
            return $this->processBoleto($order, $data, $user);
        }

        return $this->processPix($order, $data, $user);
    }

    private function processPix(Order $order, array $data, $user)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':'),
            'Content-Type' => 'application/json',
        ])->post('https://api.pagar.me/core/v5/payments', [
            'amount' => (int) ($order->total * 100),
            'currency' => 'BRL',
            'payment_method' => 'pix',
            'customer' => [
                'name' => $user->name,
                'email' => $user->email,
                'document' => $user->cpf,
            ],
            'metadata' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ],
        ]);

        $paymentData = $response->json();

        return [
            'type' => 'pix',
            'qr_code' => $paymentData['pix_qr_code'] ?? null,
            'qr_code_base64' => $paymentData['pix_qr_code_base64'] ?? null,
            'expires_at' => $paymentData['expires_at'] ?? null,
            'payment_id' => $paymentData['id'],
        ];
    }

    private function processBoleto(Order $order, array $data, $user)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':'),
            'Content-Type' => 'application/json',
        ])->post('https://api.pagar.me/core/v5/payments', [
            'amount' => (int) ($order->total * 100),
            'currency' => 'BRL',
            'payment_method' => 'bank_transfer',
            'customer' => [
                'name' => $user->name,
                'email' => $user->email,
                'document' => $user->cpf,
            ],
            'metadata' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ],
        ]);

        $paymentData = $response->json();

        return [
            'type' => 'boleto',
            'barcode' => $paymentData['boleto']['barcode'] ?? null,
            'boleto_url' => $paymentData['boleto']['url'] ?? null,
            'expires_at' => $paymentData['boleto']['expires_at'] ?? null,
            'payment_id' => $paymentData['id'],
        ];
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature');
        $payload = $request->getContent();
        $expected = 'sha1=' . hash_hmac('sha1', $payload, $this->webhookSecret);

        return hash_equals($expected, $signature ?? '');
    }

    public function handleWebhook(array $payload): array
    {
        $status = $payload['status'] ?? null;

        if ($status === 'paid') {
            $orderId = $payload['metadata']['order_id'] ?? null;
            $paymentId = $payload['id'] ?? null;

            $order = Order::find($orderId);
            if ($order) {
                $order->markAsPaid($paymentId, $payload);
            }

            return ['status' => 'success', 'order_id' => $orderId];
        }

        return ['status' => 'ignored'];
    }
}
