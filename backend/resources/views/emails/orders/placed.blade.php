@component('mail::message')
# Pedido Confirmado!

Olá {{ $order->user->name }},

Seu pedido **#{{ $order->order_number }}** foi confirmado!

## Resumo do Pedido

@foreach($order->items as $item)
- {{ $item->product_name }} ({{ $item->quantity }}x) - R$ {{ number_format($item->total, 2, ',', '.') }}
@endforeach

**Total: R$ {{ number_format($order->total, 2, ',', '.') }}**

@component('mail::button', ['url' => url('/pedido/' . $order->order_number)])
Ver Pedido
@endcomponent

Obrigado por comprar na RoupasBR!

@endcomponent
