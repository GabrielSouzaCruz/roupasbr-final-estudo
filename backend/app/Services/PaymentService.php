<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class PaymentService
{
    public function processPayment(Order $order, string $method, array $data, User $user)
    {
        return match ($method) {
            'credit_card' => $this->processCreditCard($order, $data, $user),
            'pix' => $this->processPix($order, $data, $user),
            'boleto' => $this->processBoleto($order, $data, $user),
            default => throw new \Exception('MÃ©todo de pagamento invÃ¡lido'),
        };
    }

    private function processCreditCard(Order $order, array $data, User $user)
    {
        return [
            'type' => 'credit_card',
            'status' => 'requires_confirmation',
        ];
    }

    private function processPix(Order $order, array $data, User $user)
    {
        return [
            'type' => 'pix',
            'qr_code' => 'pix_qr_code_example',
            'expires_at' => now()->addHour(),
        ];
    }

    private function processBoleto(Order $order, array $data, User $user)
    {
        return [
            'type' => 'boleto',
            'barcode' => '1234567890123456',
            'expires_at' => now()->addDays(3),
        ];
    }
}
