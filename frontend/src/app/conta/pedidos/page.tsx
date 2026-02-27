'use client';

import { useEffect, useState } from 'react';
import { ordersApi } from '@/lib/api';
import Header from '@/components/Header';
import Link from 'next/link';

export default function PedidosPage() {
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
        <h1 className="text-3xl font-bold mb-8">Meus Pedidos</h1>

        {loading ? (
          <div className="space-y-4">
            {[...Array(5)].map((_, i) => (
              <div key={i} className="animate-pulse">
                <div className="bg-white h-20 rounded-xl"></div>
              </div>
            ))}
          </div>
        ) : orders.length === 0 ? (
          <div className="bg-white rounded-xl shadow-md p-8 text-center">
            <p className="text-gray-500 mb-4">Você ainda não fez nenhum pedido.</p>
            <Link href="/produtos" className="text-purple-600 hover:underline">
              Começar a comprar
            </Link>
          </div>
        ) : (
          <div className="space-y-4">
            {orders.map((order: any) => (
              <Link
                key={order.id}
                href={`/conta/pedidos/${order.order_number}`}
                className="block bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition"
              >
                <div className="flex justify-between items-center">
                  <div>
                    <p className="font-bold text-lg">{order.order_number}</p>
                    <p className="text-sm text-gray-500">
                      {new Date(order.created_at).toLocaleDateString('pt-BR', {
                        day: '2-digit',
                        month: 'long',
                        year: 'numeric',
                      })}
                    </p>
                    <p className="text-sm text-gray-500 mt-1">
                      {order.items?.length || 0} {order.items?.length === 1 ? 'produto' : 'produtos'}
                    </p>
                  </div>
                  <div className="text-right">
                    <p className="font-bold text-xl text-purple-600">
                      R$ {order.total.toFixed(2).replace('.', ',')}
                    </p>
                    <span className={`inline-block mt-2 text-xs px-3 py-1 rounded-full ${
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
    </div>
  );
}
