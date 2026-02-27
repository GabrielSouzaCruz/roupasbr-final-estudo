<?php

namespace App\Console\Commands;

use App\Models\AbandonedCart;
use App\Jobs\SendAbandonedCartEmail;
use Illuminate\Console\Command;

class SendAbandonedCartReminders extends Command
{
    protected $signature = 'carts:remind';
    protected $description = 'Envia emails de recuperação de carrinho abandonado';

    public function handle(): void
    {
        $abandoned = AbandonedCart::where('abandoned_at', '<', now()->subHours(1))
            ->where('recovered', false)
            ->where('abandoned_at', '>', now()->subDays(2))
            ->whereNull('email_sent_at')
            ->get();

        foreach ($abandoned as $cart) {
            SendAbandonedCartEmail::dispatch($cart);
            $this->info("Email enviado para {$cart->user->email}");
        }

        $this->info("{$abandoned->count()} emails de carrinho abandonado enviados.");
    }
}
