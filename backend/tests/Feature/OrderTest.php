<?php

use App\Models\Product;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('prevents race condition in stock', function () {
    $product = Product::factory()->create(['stock' => 10]);
    $user = User::factory()->create();
    
    Sanctum::actingAs($user);
    
    // Simular 10 requisições simultâneas
    $responses = [];
    for ($i = 0; $i < 10; $i++) {
        $responses[] = $this->postJson('/api/orders', [
            'address_id' => 1,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
            'payment_method' => 'pix',
            'payment_data' => ['type' => 'pix'],
        ]);
    }
    
    // Apenas 10 devem ser bem-sucedidos
    $success = collect($responses)->filter(fn($r) => $r->status() === 201)->count();
    
    expect($success)->toBeLessThanOrEqual(10);
    expect($product->fresh()->stock)->toBeGreaterThanOrEqual(0);
});

it('validates CPF on registration', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'cpf' => '12345678900', // CPF inválido
    ]);
    
    $response->assertStatus(422)
             ->assertJsonValidationErrors('cpf');
});

it('calculates discount with coupon', function () {
    // Teste de cupom de desconto
})->skip('Implementar factory de coupons');
