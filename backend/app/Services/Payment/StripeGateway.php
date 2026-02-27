<?php

namespace App\Services\Payment;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class StripeGateway implements PaymentGatewayInterface
{
    private $apiKey;
    private $webhookSecret;

    public function __construct()
    {
        $this->apiKey = config('services.stripe.secret_key');
        $this->webhookSecret = config('services.stripe.webhook_secret');
    }

    public function process(Order $order, array $data, $user)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->post('https://api.stripe.com/v1/payment_intents', [
            'amount' => (int) ($order->total * 100),
            'currency' => 'brl',
            'payment_method_types' => ['card'],
            'customer_email' => $user->email,
            'metadata' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ],
        ]);

        $paymentData = $response->json();

        return [
            'type' => 'credit_card',
            'client_secret' => $paymentData['client_secret'],
            'payment_intent_id' => $paymentData['id'],
            'status' => 'requires_confirmation',
        ];
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $sigHeader = $request->header('Stripe-Signature');
        $payload = $request->getContent();

        try {
            \Stripe\Webhook::constructEvent($payload, $sigHeader, $this->webhookSecret);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function handleWebhook(array $payload): array
    {
        $eventType = $payload['type'];

        if ($eventType === 'payment_intent.succeeded') {
            $orderId = $payload['data']['object']['metadata']['order_id'];
            $paymentId = $payload['data']['object']['id'];

            $order = Order::find($orderId);
            if ($order) {
                $order->markAsPaid($paymentId, $payload['data']['object']);
            }

            return ['status' => 'success', 'order_id' => $orderId];
        }

        return ['status' => 'ignored'];
    }
}
