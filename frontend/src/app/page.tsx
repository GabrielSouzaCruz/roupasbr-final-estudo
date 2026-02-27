'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { productsApi } from '@/lib/api';
import { useCartStore } from '@/store/cartStore';
import Header from '@/components/Header';
import ProductCard from '@/components/ProductCard';
import toast, { Toaster } from 'react-hot-toast';

export default function HomePage() {
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const itemCount = useCartStore((state) => state.itemCount());

  useEffect(() => {
    productsApi.getFeatured()
      .then((res) => setProducts(res.data))
      .finally(() => setLoading(false));
  }, []);

  return (
    <div className="min-h-screen bg-gray-50">
      <Toaster />
      <Header />

      <section className="bg-gradient-to-r from-purple-600 to-pink-600 text-white py-20">
        <div className="container mx-auto px-4 text-center">
          <h1 className="text-5xl font-bold mb-4">Nova ColeÃ§Ã£o 2026</h1>
          <p className="text-xl mb-8">Estilo e conforto para vocÃª se sentir incrÃ­vel</p>
          <Link href="/produtos" className="bg-white text-purple-600 px-8 py-3 rounded-full font-semibold hover:bg-gray-100 transition">
            Ver ColeÃ§Ã£o
          </Link>
        </div>
      </section>

      <section className="py-16">
        <div className="container mx-auto px-4">
          <h2 className="text-3xl font-bold text-center mb-12">Categorias</h2>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
            {['Camisetas', 'CalÃ§as', 'Vestidos', 'AcessÃ³rios'].map((cat) => (
              <Link key={cat} href={`/produtos?category=${cat}`} className="bg-white rounded-xl shadow-md p-6 text-center hover:shadow-lg transition">
                <div className="w-20 h-20 bg-gray-200 rounded-full mx-auto mb-4" />
                <h3 className="font-semibold text-lg">{cat}</h3>
              </Link>
            ))}
          </div>
        </div>
      </section>

      <section className="py-16 bg-white">
        <div className="container mx-auto px-4">
          <h2 className="text-3xl font-bold text-center mb-12">Produtos em Destaque</h2>
          {loading ? (
            <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
              {[...Array(8)].map((_, i) => (
                <div key={i} className="animate-pulse">
                  <div className="bg-gray-200 h-64 rounded-lg mb-4" />
                  <div className="bg-gray-200 h-4 w-3/4 rounded mb-2" />
                  <div className="bg-gray-200 h-4 w-1/2 rounded" />
                </div>
              ))}
            </div>
          ) : (
            <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
              {products.map((product: any) => (
                <ProductCard key={product.id} product={product} />
              ))}
            </div>
          )}
        </div>
      </section>

      <footer className="bg-gray-800 text-gray-300 py-12">
        <div className="container mx-auto px-4 text-center">
          <p>&copy; 2026 RoupasBR. Todos os direitos reservados.</p>
        </div>
      </footer>
    </div>
  );
}
