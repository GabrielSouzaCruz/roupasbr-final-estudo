'use client';

import { useEffect, useState } from 'react';
import { useAuthStore } from '@/store/authStore';
import { ordersApi } from '@/lib/api';
import Header from '@/components/Header';
import Link from 'next/link';
import { Package, User, MapPin, CreditCard } from 'lucide-react';

export default function ContaPage() {
  const { user } = useAuthStore();
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    ordersApi.getAll()
      .then((res) => setOrders(res.data.data || []))
      .finally(() => setLoading(false));
  }, []);

  return (
    <div className="min-h-screen bg-gray-50">
      <Header />
      
      <div className="container mx-auto px-4 py-8">
        <h1 className="text-3xl font-bold mb-8">Minha Conta</h1>

        <div className="grid md:grid-cols-4 gap-6">
          {/* Menu Lateral */}
          <aside className="md:col-span-1">
            <nav className="bg-white rounded-xl shadow-md p-4 space-y-2">
              <Link href="/conta" className="flex items-center gap-3 p-3 rounded-lg bg-purple-50 text-purple-600">
                <User className="w-5 h-5" />
                <span>Visão Geral</span>
              </Link>
              <Link href="/conta/pedidos" className="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50">
                <Package className="w-5 h-5" />
                <span>Meus Pedidos</span>
              </Link>
              <Link href="/conta/enderecos" className="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50">
                <MapPin className="w-5 h-5" />
                <span>Endereços</span>
              </Link>
            </nav>
          </aside>

          {/* Conteúdo Principal */}
          <main className="md:col-span-3 space-y-6">
            {/* Dados do Usuário */}
            <div className="bg-white rounded-xl shadow-md p-6">
              <h2 className="text-xl font-bold mb-4">Meus Dados</h2>
              <div className="grid md:grid-cols-2 gap-4">
                <div>
                  <label className="text-sm text-gray-500">Nome</label>
                  <p className="font-medium">{user?.name}</p>
                </div>
                <div>
                  <label className="text-sm text-gray-500">E-mail</label>
                  <p className="font-medium">{user?.email}</p>
                </div>
                <div>
                  <label className="text-sm text-gray-500">CPF</label>
                  <p className="font-medium">{user?.cpf || 'Não informado'}</p>
                </div>
                <div>
                  <label className="text-sm text-gray-500">Telefone</label>
                  <p className="font-medium">{user?.phone || 'Não informado'}</p>
                </div>
              </div>
            </div>

            {/* Últimos Pedidos */}
            <div className="bg-white rounded-xl shadow-md p-6">
              <div className="flex justify-between items-center mb-4">
                <h2 className="text-xl font-bold">Últimos Pedidos</h2>
                <Link href="/conta/pedidos" className="text-purple-600 hover:underline">
                  Ver todos
                </Link>
              </div>

              {loading ? (
                <div className="space-y-4">
                  {[...Array(3)].map((_, i) => (
                    <div key={i} className="animate-pulse">
                      <div className="bg-gray-200 h-16 rounded-lg"></div>
                    </div>
                  ))}
                </div>
              ) : orders.length === 0 ? (
                <p className="text-gray-500">Você ainda não fez nenhum pedido.</p>
              ) : (
                <div className="space-y-4">
                  {orders.slice(0, 3).map((order: any) => (
                    <Link
                      key={order.id}
                      href={`/conta/pedidos/${order.order_number}`}
                      className="block p-4 border rounded-lg hover:border-purple-600 transition"
                    >
                      <div className="flex justify-between items-center">
                        <div>
                          <p className="font-semibold">{order.order_number}</p>
                          <p className="text-sm text-gray-500">
                            {new Date(order.created_at).toLocaleDateString('pt-BR')}
                          </p>
                        </div>
                        <div className="text-right">
                          <p className="font-bold text-purple-600">
                            R$ {order.total.toFixed(2).replace('.', ',')}
                          </p>
                          <span className={`text-xs px-2 py-1 rounded ${
                            order.status === 'delivered' ? 'bg-green-100 text-green-700' :
                            order.status === 'shipped' ? 'bg-blue-100 text-blue-700' :
                            order.status === 'cancelled' ? 'bg-red-100 text-red-700' :
                            'bg-yellow-100 text-yellow-700'
                          }`}>
                            {order.status === 'pending' && 'Pendente'}
                            {order.status === 'paid' && 'Pago'}
                            {order.status === 'processing' && 'Processando'}
                            {order.status === 'shipped' && 'Enviado'}
                            {order.status === 'delivered' && 'Entregue'}
                            {order.status === 'cancelled' && 'Cancelado'}
                          </span>
                        </div>
                      </div>
                    </Link>
                  ))}
                </div>
              )}
            </div>
          </main>
        </div>
      </div>
    </div>
  );
}
