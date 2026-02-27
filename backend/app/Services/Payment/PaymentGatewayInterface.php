<?php

namespace App\Services\Payment;

use App\Models\Order;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    public function process(Order $order, array $data, $user);
    public function verifyWebhookSignature(Request $request): bool;
    public function handleWebhook(array $payload): array;
}
