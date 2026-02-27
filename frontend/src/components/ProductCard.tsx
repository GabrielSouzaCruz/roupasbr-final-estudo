'use client';

import Link from 'next/link';
import Image from 'next/image';
import { useCartStore } from '@/store/cartStore';
import toast from 'react-hot-toast';

interface ProductCardProps {
  product: {
    id: number;
    name: string;
    slug: string;
    price: number;
    compare_at_price?: number;
    main_image: string;
    discount_percentage?: number;
  };
}

export default function ProductCard({ product }: ProductCardProps) {
  const addItem = useCartStore((state) => state.addItem);

  const handleAddToCart = (e: React.MouseEvent) => {
    e.preventDefault();
    addItem({
      product_id: product.id,
      name: product.name,
      slug: product.slug,
      price: product.price,
      image: product.main_image,
      quantity: 1,
    });
    toast.success('Produto adicionado ao carrinho!');
  };

  return (
    <Link href={`/produto/${product.slug}`} className="group">
      <div className="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition">
        <div className="relative h-64 bg-gray-100">
          <Image src={product.main_image} alt={product.name} fill className="object-cover group-hover:scale-105 transition" />
          {product.discount_percentage && (
            <span className="absolute top-2 left-2 bg-red-500 text-white px-2 py-1 rounded text-sm">
              -{product.discount_percentage}%
            </span>
          )}
        </div>
        <div className="p-4">
          <h3 className="font-semibold text-gray-800 mb-2 line-clamp-2">{product.name}</h3>
          <div className="flex items-center gap-2">
            <span className="text-lg font-bold text-purple-600">
              R$ {product.price.toFixed(2).replace('.', ',')}
            </span>
            {product.compare_at_price && (
              <span className="text-sm text-gray-400 line-through">
                R$ {product.compare_at_price.toFixed(2).replace('.', ',')}
              </span>
            )}
          </div>
          <button onClick={handleAddToCart} className="mt-4 w-full bg-purple-600 text-white py-2 rounded-lg hover:bg-purple-700 transition">
            Adicionar ao Carrinho
          </button>
        </div>
      </div>
    </Link>
  );
}
