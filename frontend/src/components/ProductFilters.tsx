'use client';

import { useState, useEffect } from 'react';
import { api } from '@/lib/api';

interface Filters {
  sizes: string[];
  colors: string[];
  categories: string[];
  price_range: { min: number; max: number };
}

interface ProductFiltersProps {
  onFilterChange: (filters: any) => void;
}

export default function ProductFilters({ onFilterChange }: ProductFiltersProps) {
  const [filters, setFilters] = useState<Filters | null>(null);
  const [selectedFilters, setSelectedFilters] = useState({
    category: '',
    min_price: '',
    max_price: '',
    size: '',
    color: '',
    sort: 'created_at',
  });

  useEffect(() => {
    api.get('/products/filters').then((res) => setFilters(res.data));
  }, []);

  const handleChange = (key: string, value: string) => {
    const newFilters = { ...selectedFilters, [key]: value };
    setSelectedFilters(newFilters);
    onFilterChange(newFilters);
  };

  if (!filters) {
    return <div className="animate-pulse h-64 bg-gray-200 rounded-lg" />;
  }

  return (
    <div className="bg-white rounded-xl shadow-md p-6 space-y-6">
      {/* Categoria */}
      <div>
        <label className="block font-semibold mb-2">Categoria</label>
        <select
          value={selectedFilters.category}
          onChange={(e) => handleChange('category', e.target.value)}
          className="w-full border rounded-lg p-2"
        >
          <option value="">Todas</option>
          {filters.categories.map((cat) => (
            <option key={cat} value={cat}>{cat}</option>
          ))}
        </select>
      </div>

      {/* Preço */}
      <div>
        <label className="block font-semibold mb-2">Faixa de Preço</label>
        <div className="flex gap-2">
          <input
            type="number"
            placeholder="Mín"
            value={selectedFilters.min_price}
            onChange={(e) => handleChange('min_price', e.target.value)}
            className="w-full border rounded-lg p-2"
          />
          <input
            type="number"
            placeholder="Máx"
            value={selectedFilters.max_price}
            onChange={(e) => handleChange('max_price', e.target.value)}
            className="w-full border rounded-lg p-2"
          />
        </div>
        <p className="text-xs text-gray-500 mt-1">
          R$ {filters.price_range.min} - R$ {filters.price_range.max}
        </p>
      </div>

      {/* Tamanhos */}
      <div>
        <label className="block font-semibold mb-2">Tamanho</label>
        <select
          value={selectedFilters.size}
          onChange={(e) => handleChange('size', e.target.value)}
          className="w-full border rounded-lg p-2"
        >
          <option value="">Todos</option>
          {filters.sizes.map((size) => (
            <option key={size} value={size}>{size}</option>
          ))}
        </select>
      </div>

      {/* Cores */}
      <div>
        <label className="block font-semibold mb-2">Cor</label>
        <select
          value={selectedFilters.color}
          onChange={(e) => handleChange('color', e.target.value)}
          className="w-full border rounded-lg p-2"
        >
          <option value="">Todas</option>
          {filters.colors.map((color) => (
            <option key={color} value={color} className="capitalize">{color}</option>
          ))}
        </select>
      </div>

      {/* Ordenação */}
      <div>
        <label className="block font-semibold mb-2">Ordenar Por</label>
        <select
          value={selectedFilters.sort}
          onChange={(e) => handleChange('sort', e.target.value)}
          className="w-full border rounded-lg p-2"
        >
          <option value="created_at">Mais Recentes</option>
          <option value="price_asc">Preço: Menor para Maior</option>
          <option value="price_desc">Preço: Maior para Menor</option>
          <option value="name">Nome (A-Z)</option>
          <option value="rating">Melhor Avaliados</option>
        </select>
      </div>

      {/* Limpar Filtros */}
      <button
        onClick={() => {
          setSelectedFilters({
            category: '',
            min_price: '',
            max_price: '',
            size: '',
            color: '',
            sort: 'created_at',
          });
          onFilterChange({});
        }}
        className="w-full border border-purple-600 text-purple-600 py-2 rounded-lg hover:bg-purple-50"
      >
        Limpar Filtros
      </button>
    </div>
  );
}
