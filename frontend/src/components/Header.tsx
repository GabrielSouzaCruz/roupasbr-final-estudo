'use client';

import Link from 'next/link';
import { ShoppingCart, User, Search, Menu, X } from 'lucide-react';
import { useCartStore } from '@/store/cartStore';
import { useAuthStore } from '@/store/authStore';
import { useState } from 'react';

export default function Header() {
  const itemCount = useCartStore((state) => state.itemCount());
  const { isAuthenticated, logout } = useAuthStore();
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  return (
    <header className="bg-white shadow-md sticky top-0 z-50">
      <div className="container mx-auto px-4">
        <div className="flex items-center justify-between h-16">
          <Link href="/" className="text-2xl font-bold text-purple-600">
            ðŸ›ï¸ RoupasBR
          </Link>

          <nav className="hidden md:flex items-center gap-8">
            <Link href="/produtos" className="hover:text-purple-600">Produtos</Link>
            <Link href="/categorias" className="hover:text-purple-600">Categorias</Link>
            <Link href="/sobre" className="hover:text-purple-600">Sobre</Link>
            <Link href="/contato" className="hover:text-purple-600">Contato</Link>
          </nav>

          <div className="flex items-center gap-4">
            <Link href="/busca" className="p-2 hover:bg-gray-100 rounded-full">
              <Search className="w-5 h-5" />
            </Link>

            <Link href="/carrinho" className="p-2 hover:bg-gray-100 rounded-full relative">
              <ShoppingCart className="w-5 h-5" />
              {itemCount > 0 && (
                <span className="absolute -top-1 -right-1 bg-purple-600 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center">
                  {itemCount}
                </span>
              )}
            </Link>

            {isAuthenticated ? (
              <div className="relative group">
                <button className="p-2 hover:bg-gray-100 rounded-full">
                  <User className="w-5 h-5" />
                </button>
                <div className="absolute right-0 mt-2 w-48 bg-white shadow-lg rounded-lg py-2 hidden group-hover:block">
                  <Link href="/conta" className="block px-4 py-2 hover:bg-gray-100">Minha Conta</Link>
                  <Link href="/pedidos" className="block px-4 py-2 hover:bg-gray-100">Meus Pedidos</Link>
                  <button onClick={logout} className="block w-full text-left px-4 py-2 hover:bg-gray-100">Sair</button>
                </div>
              </div>
            ) : (
              <Link href="/login" className="p-2 hover:bg-gray-100 rounded-full">
                <User className="w-5 h-5" />
              </Link>
            )}

            <button className="md:hidden p-2 hover:bg-gray-100 rounded-full" onClick={() => setMobileMenuOpen(!mobileMenuOpen)}>
              {mobileMenuOpen ? <X /> : <Menu />}
            </button>
          </div>
        </div>

        {mobileMenuOpen && (
          <nav className="md:hidden py-4 border-t">
            <Link href="/produtos" className="block py-2 hover:bg-gray-100 px-2">Produtos</Link>
            <Link href="/categorias" className="block py-2 hover:bg-gray-100 px-2">Categorias</Link>
            <Link href="/sobre" className="block py-2 hover:bg-gray-100 px-2">Sobre</Link>
            <Link href="/contato" className="block py-2 hover:bg-gray-100 px-2">Contato</Link>
          </nav>
        )}
      </div>
    </header>
  );
}
