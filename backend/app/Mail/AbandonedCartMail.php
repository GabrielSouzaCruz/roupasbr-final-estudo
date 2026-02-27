<?php

namespace App\Mail;

use App\Models\AbandonedCart;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AbandonedCartMail extends Mailable
{
    use Queueable, SerializesModels;

    public $cart;

    public function __construct(AbandonedCart $cart)
    {
        $this->cart = $cart;
    }

    public function build()
    {
        return $this->subject("Você esqueceu algo no carrinho? 🛒")
                    ->markdown('emails.abandoned_cart');
    }
}
