# setup-frontend-corrigido.ps1 - Frontend Next.js com Segurança

Write-Host "🔒 Criando Frontend Next.js - Production Ready..." -ForegroundColor Green
Write-Host "========================================================" -ForegroundColor Green

# ==================== API LIB COM CREDENTIALS ====================
@'
import axios from 'axios';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';

export const api = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
  },
  withCredentials: true, // Cookie HttpOnly automático
});

export const authApi = {
  register: (data: any) => api.post('/auth/register', data),
  login: (data: any) => api.post('/auth/login', data),
  logout: () => api.post('/auth/logout'),
  getUser: () => api.get('/auth/user'),
};

export const productsApi = {
  getAll: (params?: any) => api.get('/products', { params }),
  getFeatured: () => api.get('/products/featured'),
  getCategories: () => api.get('/products/categories'),
  getBySlug: (slug: string) => api.get(`/products/${slug}`),
};

export const ordersApi = {
  getAll: () => api.get('/orders'),
  getByNumber: (number: string) => api.get(`/orders/${number}`),
  create: (data: any) => api.post('/orders', data, {
    headers: {
      'Idempotency-Key': crypto.randomUUID(), // Idempotência
    },
  }),
};

export const addressesApi = {
  getAll: () => api.get('/addresses'),
  create: (data: any) => api.post('/addresses', data),
  update: (id: number, data: any) => api.put(`/addresses/${id}`, data),
  delete: (id: number) => api.delete(`/addresses/${id}`),
};

// Types
export interface Product {
  id: number;
  name: string;
  slug: string;
  description: string;
  price: number;
  compare_at_price?: number;
  stock: number;
  status: string;
  category: string;
  sizes?: string[];
  colors?: string[];
  main_image: string;
  images?: string[];
  discount_percentage?: number;
}

export interface Address {
  id: number;
  cep: string;
  street: string;
  number: string;
  complement?: string;
  neighborhood: string;
  city: string;
  state: string;
  is_default: boolean;
}

export interface OrderItem {
  product_id: number;
  quantity: number;
  size?: string;
  color?: string;
}

export interface CreateOrderData {
  address_id: number;
  items: OrderItem[];
  payment_method: 'credit_card' | 'pix' | 'boleto';
  payment_data: any;
}
'@ | Out-File -FilePath "frontend/src/lib/api.ts" -Encoding UTF8

Write-Host "✅ src/lib/api.ts com cookies e idempotência criado" -ForegroundColor Green

# ==================== AUTH STORE (SEM TOKEN) ====================
@'
import { create } from 'zustand';
import { authApi } from '@/lib/api';

interface User {
  id: number;
  name: string;
  email: string;
  role: string;
}

interface AuthState {
  user: User | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  login: (email: string, password: string) => Promise<void>;
  register: (data: any) => Promise<void>;
  logout: () => Promise<void>;
  fetchUser: () => Promise<void>;
}

export const useAuthStore = create<AuthState>((set, get) => ({
  user: null,
  isAuthenticated: false,
  isLoading: true,

  login: async (email, password) => {
    const response = await authApi.login({ email, password });
    const { user } = response.data;
    // Token via cookie HttpOnly - não armazenamos no client
    set({ user, isAuthenticated: true, isLoading: false });
  },

  register: async (data) => {
    const response = await authApi.register(data);
    const { user } = response.data;
    set({ user, isAuthenticated: true, isLoading: false });
  },

  logout: async () => {
    try {
      await authApi.logout();
    } finally {
      set({ user: null, isAuthenticated: false, isLoading: false });
    }
  },

  fetchUser: async () => {
    try {
      const response = await authApi.getUser();
      set({ user: response.data, isAuthenticated: true, isLoading: false });
    } catch {
      set({ user: null, isAuthenticated: false, isLoading: false });
    }
  },
}));
'@ | Out-File -FilePath "frontend/src/store/authStore.ts" -Encoding UTF8

Write-Host "✅ src/store/authStore.ts (sem token no client) criado" -ForegroundColor Green

# ==================== CHECKOUT PAGE COM ENDEREÇOS ====================
New-Item -ItemType Directory -Force -Path "frontend/src/app/checkout" | Out-Null

@'
'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useCartStore } from '@/store/cartStore';
import { useAuthStore } from '@/store/authStore';
import { ordersApi, addressesApi, Address } from '@/lib/api';
import Header from '@/components/Header';
import toast from 'react-hot-toast';

export default function CheckoutPage() {
  const router = useRouter();
  const { items, total, clearCart } = useCartStore();
  const { isAuthenticated } = useAuthStore();
  const [addresses, setAddresses] = useState<Address[]>([]);
  const [selectedAddress, setSelectedAddress] = useState<number | null>(null);
  const [paymentMethod, setPaymentMethod] = useState<'credit_card' | 'pix' | 'boleto'>('pix');
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (isAuthenticated) {
      addressesApi.getAll().then((res) => {
        setAddresses(res.data);
        if (res.data.length > 0) {
          setSelectedAddress(res.data.find((a: Address) => a.is_default)?.id || res.data[0].id);
        }
      });
    }
  }, [isAuthenticated]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!selectedAddress) {
      toast.error('Selecione um endereço');
      return;
    }

    setLoading(true);

    try {
      const response = await ordersApi.create({
        address_id: selectedAddress,
        items: items.map((item) => ({
          product_id: item.product_id,
          quantity: item.quantity,
          size: item.size,
          color: item.color,
        })),
        payment_method: paymentMethod,
        payment_data: { type: paymentMethod },
      });

      clearCart();
      toast.success('Pedido criado com sucesso!');
      router.push(`/pedido/${response.data.order.order_number}`);
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Erro ao criar pedido');
    } finally {
      setLoading(false);
    }
  };

  const shippingCost = total() > 299 ? 0 : 25.90;
  const finalTotal = total() + shippingCost;

  if (items.length === 0) {
    return (
      <div className="min-h-screen bg-gray-50">
        <Header />
        <div className="container mx-auto px-4 py-16 text-center">
          <h1 className="text-3xl font-bold mb-4">Carrinho vazio</h1>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <Header />
      <div className="container mx-auto px-4 py-8">
        <h1 className="text-3xl font-bold mb-8">Finalizar Compra</h1>

        <form onSubmit={handleSubmit} className="grid md:grid-cols-3 gap-8">
          <div className="md:col-span-2 space-y-6">
            {/* Endereços */}
            <div className="bg-white rounded-xl p-6 shadow-md">
              <h2 className="text-xl font-bold mb-4">Endereço de Entrega</h2>
              {addresses.length === 0 ? (
                <p className="text-gray-500">Nenhum endereço cadastrado</p>
              ) : (
                <div className="space-y-3">
                  {addresses.map((addr) => (
                    <label key={addr.id} className="flex items-center gap-3 cursor-pointer p-3 border rounded-lg hover:bg-gray-50">
                      <input
                        type="radio"
                        name="address"
                        value={addr.id}
                        checked={selectedAddress === addr.id}
                        onChange={() => setSelectedAddress(addr.id)}
                        className="w-4 h-4"
                      />
                      <span>
                        {addr.street}, {addr.number} - {addr.neighborhood}, {addr.city}/{addr.state}
                        {addr.is_default && <span className="ml-2 text-green-600 text-sm">(Padrão)</span>}
                      </span>
                    </label>
                  ))}
                </div>
              )}
            </div>

            {/* Pagamento */}
            <div className="bg-white rounded-xl p-6 shadow-md">
              <h2 className="text-xl font-bold mb-4">Pagamento</h2>
              <div className="space-y-3">
                <label className="flex items-center gap-3 cursor-pointer">
                  <input
                    type="radio"
                    name="payment"
                    value="credit_card"
                    checked={paymentMethod === 'credit_card'}
                    onChange={() => setPaymentMethod('credit_card')}
                    className="w-4 h-4"
                  />
                  <span>Cartão de Crédito</span>
                </label>
                <label className="flex items-center gap-3 cursor-pointer">
                  <input
                    type="radio"
                    name="payment"
                    value="pix"
                    checked={paymentMethod === 'pix'}
                    onChange={() => setPaymentMethod('pix')}
                    className="w-4 h-4"
                  />
                  <span>Pix (Aprovação imediata)</span>
                </label>
                <label className="flex items-center gap-3 cursor-pointer">
                  <input
                    type="radio"
                    name="payment"
                    value="boleto"
                    checked={paymentMethod === 'boleto'}
                    onChange={() => setPaymentMethod('boleto')}
                    className="w-4 h-4"
                  />
                  <span>Boleto Bancário</span>
                </label>
              </div>
            </div>
          </div>

          {/* Resumo */}
          <div className="bg-white rounded-xl p-6 shadow-md h-fit">
            <h2 className="text-xl font-bold mb-4">Resumo</h2>
            <div className="space-y-2">
              <div className="flex justify-between">
                <span>Subtotal</span>
                <span>R$ {total().toFixed(2).replace('.', ',')}</span>
              </div>
              <div className="flex justify-between">
                <span>Frete</span>
                <span>{shippingCost === 0 ? 'Grátis' : `R$ ${shippingCost.toFixed(2).replace('.', ',')}`}</span>
              </div>
              <div className="border-t pt-2 flex justify-between font-bold">
                <span>Total</span>
                <span className="text-purple-600">R$ {finalTotal.toFixed(2).replace('.', ',')}</span>
              </div>
            </div>

            <button
              type="submit"
              disabled={loading || !selectedAddress}
              className="w-full mt-6 bg-purple-600 text-white py-4 rounded-lg font-semibold hover:bg-purple-700 disabled:bg-gray-400"
            >
              {loading ? 'Processando...' : `Pagar R$ ${finalTotal.toFixed(2).replace('.', ',')}`}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
'@ | Out-File -FilePath "frontend/src/app/checkout/page.tsx" -Encoding UTF8

Write-Host "✅ src/app/checkout/page.tsx com endereços criado" -ForegroundColor Green

# ==================== PRODUTO PAGE COM VALIDAÇÃO ====================
@'
'use client';

import { useEffect, useState } from 'react';
import { useParams } from 'next/navigation';
import Image from 'next/image';
import { productsApi } from '@/lib/api';
import { useCartStore } from '@/store/cartStore';
import toast from 'react-hot-toast';
import Header from '@/components/Header';

export default function ProductPage() {
  const params = useParams();
  const [product, setProduct] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [selectedSize, setSelectedSize] = useState<string>('');
  const [selectedColor, setSelectedColor] = useState<string>('');
  const [quantity, setQuantity] = useState(1);
  const addItem = useCartStore((state) => state.addItem);

  useEffect(() => {
    if (params.slug) {
      productsApi.getBySlug(params.slug as string)
        .then((res) => setProduct(res.data))
        .finally(() => setLoading(false));
    }
  }, [params.slug]);

  const handleAddToCart = () => {
    if (!product) return;

    // Validação obrigatória
    if (product.sizes && !selectedSize) {
      toast.error('Selecione um tamanho');
      return;
    }
    if (product.colors && !selectedColor) {
      toast.error('Selecione uma cor');
      return;
    }

    addItem({
      product_id: product.id,
      name: product.name,
      slug: product.slug,
      price: product.price,
      image: product.main_image,
      quantity,
      size: selectedSize || undefined,
      color: selectedColor || undefined,
    });

    toast.success('Produto adicionado ao carrinho!');
  };

  if (loading) {
    return (
      <div className="min-h-screen">
        <Header />
        <div className="container mx-auto px-4 py-8">
          <div className="animate-pulse">
            <div className="bg-gray-200 h-96 rounded-lg" />
          </div>
        </div>
      </div>
    );
  }

  if (!product) {
    return <div>Produto não encontrado</div>;
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <Header />
      
      <div className="container mx-auto px-4 py-8">
        <div className="grid md:grid-cols-2 gap-8">
          <div className="space-y-4">
            <div className="relative h-96 bg-white rounded-xl overflow-hidden">
              <Image src={product.main_image} alt={product.name} fill className="object-cover" />
            </div>
          </div>

          <div className="bg-white rounded-xl p-6 shadow-md">
            <h1 className="text-3xl font-bold mb-4">{product.name}</h1>
            
            <div className="flex items-center gap-4 mb-6">
              <span className="text-3xl font-bold text-purple-600">
                R$ {product.price.toFixed(2).replace('.', ',')}
              </span>
              {product.compare_at_price && (
                <span className="text-xl text-gray-400 line-through">
                  R$ {product.compare_at_price.toFixed(2).replace('.', ',')}
                </span>
              )}
            </div>

            <p className="text-gray-600 mb-6">{product.description}</p>

            {product.sizes && (
              <div className="mb-6">
                <label className="block font-semibold mb-2">Tamanho *</label>
                <div className="flex gap-2">
                  {product.sizes.map((size: string) => (
                    <button
                      key={size}
                      onClick={() => setSelectedSize(size)}
                      className={`px-4 py-2 border rounded-lg ${
                        selectedSize === size
                          ? 'bg-purple-600 text-white border-purple-600'
                          : 'hover:border-purple-600'
                      }`}
                    >
                      {size}
                    </button>
                  ))}
                </div>
              </div>
            )}

            {product.colors && (
              <div className="mb-6">
                <label className="block font-semibold mb-2">Cor *</label>
                <div className="flex gap-2">
                  {product.colors.map((color: string) => (
                    <button
                      key={color}
                      onClick={() => setSelectedColor(color)}
                      className={`px-4 py-2 border rounded-lg capitalize ${
                        selectedColor === color
                          ? 'bg-purple-600 text-white border-purple-600'
                          : 'hover:border-purple-600'
                      }`}
                    >
                      {color}
                    </button>
                  ))}
                </div>
              </div>
            )}

            <div className="mb-6">
              <label className="block font-semibold mb-2">Quantidade</label>
              <div className="flex items-center gap-4">
                <button onClick={() => setQuantity(Math.max(1, quantity - 1))} className="w-10 h-10 border rounded-lg hover:bg-gray-100">-</button>
                <span className="w-10 text-center">{quantity}</span>
                <button onClick={() => setQuantity(quantity + 1)} className="w-10 h-10 border rounded-lg hover:bg-gray-100">+</button>
              </div>
            </div>

            <p className="text-sm text-gray-500 mb-6">
              {product.stock > 0 ? `✅ ${product.stock} unidades em estoque` : '❌ Produto esgotado'}
            </p>

            <button
              onClick={handleAddToCart}
              disabled={product.stock === 0}
              className="w-full bg-purple-600 text-white py-4 rounded-lg font-semibold hover:bg-purple-700 transition disabled:bg-gray-400"
            >
              {product.stock > 0 ? 'Adicionar ao Carrinho' : 'Esgotado'}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
'@ | Out-File -FilePath "frontend/src/app/produto/[slug]/page.tsx" -Encoding UTF8

Write-Host "✅ src/app/produto/[slug]/page.tsx com validação criado" -ForegroundColor Green

Write-Host ""
Write-Host "========================================================" -ForegroundColor Green
Write-Host "✅ Frontend corrigido criado com sucesso!" -ForegroundColor Green
Write-Host "========================================================" -ForegroundColor Green
Write-Host ""
Write-Host "🔒 Melhorias de Segurança Incluídas:" -ForegroundColor Yellow
Write-Host "   ✅ Cookie HttpOnly (sem token no client)" -ForegroundColor Cyan
Write-Host "   ✅ withCredentials no Axios" -ForegroundColor Cyan
Write-Host "   ✅ Validação de tamanho/cor obrigatória" -ForegroundColor Cyan
Write-Host "   ✅ Idempotência no checkout" -ForegroundColor Cyan
Write-Host "   ✅ Seleção de endereços" -ForegroundColor Cyan
Write-Host "   ✅ Typescript interfaces completas" -ForegroundColor Cyan
Write-Host ""
Write-Host "🚀 Para iniciar:" -ForegroundColor Yellow
Write-Host "   docker-compose up -d --build" -ForegroundColor White
Write-Host "   docker exec roupasbr_backend composer install" -ForegroundColor White
Write-Host "   docker exec roupasbr_backend php artisan key:generate" -ForegroundColor White
Write-Host "   docker exec roupasbr_backend php artisan migrate --seed" -ForegroundColor White
Write-Host ""
Write-Host "🌐 Acessar: http://localhost:3000" -ForegroundColor Cyan
Write-Host ""