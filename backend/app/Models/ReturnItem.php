<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReturnItem extends Model
{
    use HasFactory;

    protected $table = 'returns';

    protected $fillable = [
        'order_id',
        'order_item_id',
        'user_id',
        'status',
        'reason',
        'images',
        'qr_code_url',
        'tracking_code',
        'refund_amount',
        'approved_at',
        'received_at',
        'refunded_at',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'images' => 'array',
        'approved_at' => 'datetime',
        'received_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function item()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function request(): void
    {
        $this->update(['status' => 'requested']);
    }

    public function approve(): void
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    public function markAsShippedBack(string $trackingCode): void
    {
        $this->update([
            'status' => 'shipped_back',
            'tracking_code' => $trackingCode,
        ]);
    }

    public function markAsReceived(): void
    {
        $this->update([
            'status' => 'received',
            'received_at' => now(),
        ]);
    }

    public function refund(): void
    {
        $this->update([
            'status' => 'refunded',
            'refunded_at' => now(),
        ]);
    }

    public function reject(): void
    {
        $this->update(['status' => 'rejected']);
    }

    public function generateQRCode(): string
    {
        // Integrar com Correios/Melhor Envio para gerar etiqueta
        $this->update([
            'qr_code_url' => 'https://melhorenvio.com.br/etiqueta/' . uniqid(),
        ]);
        
        return $this->qr_code_url;
    }
}
