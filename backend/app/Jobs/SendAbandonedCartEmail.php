<?php

namespace App\Jobs;

use App\Models\AbandonedCart;
use App\Mail\AbandonedCartMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendAbandonedCartEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $cart;

    public function __construct(AbandonedCart $cart)
    {
        $this->cart = $cart;
    }

    public function handle(): void
    {
        if ($this->cart->email_sent_at) {
            return;
        }

        Mail::to($this->cart->user->email)->queue(new AbandonedCartMail($this->cart));
        
        $this->cart->markEmailAsSent();
    }
}
