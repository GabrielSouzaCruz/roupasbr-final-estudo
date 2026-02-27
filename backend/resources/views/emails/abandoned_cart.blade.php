@component('mail::message')
# Olá {{ $cart->user->name }},

Parece que você deixou alguns itens no carrinho! Não se preocupe, eles ainda estão reservados para você.

## Seu Carrinho

@foreach($cart->cart_items as $item)
- **{{ $item['name'] }}** ({{ $item['quantity'] }}x) - R$ {{ number_format($item['price'] * $item['quantity'], 2, ',', '.') }}
@endforeach

**Total: R$ {{ number_format($cart->total, 2, ',', '.') }}**

@component('mail::button', ['url' => url('/carrinho?recover=1')])
Recuperar Meu Carrinho
@endcomponent

⚠️ Os itens são reservados por tempo limitado.

Obrigado por comprar na RoupasBR!

@endcomponent
