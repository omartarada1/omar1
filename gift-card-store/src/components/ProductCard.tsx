'use client';

import { useState } from 'react';
import Link from 'next/link';
import Image from 'next/image';
import { Product } from '@/types';
import { useStore } from '@/store/useStore';
import { PlusIcon, ShoppingCartIcon } from '@heroicons/react/24/outline';
import toast from 'react-hot-toast';

interface ProductCardProps {
  product: Product;
}

export default function ProductCard({ product }: ProductCardProps) {
  const [selectedDenomination, setSelectedDenomination] = useState(
    product.denominations?.[0] || product.price
  );
  const { addToCart } = useStore();

  const handleAddToCart = (e: React.MouseEvent) => {
    e.preventDefault(); // Prevent navigation when clicking the button
    addToCart(product, 1, selectedDenomination);
    toast.success(`${product.name} added to cart!`);
  };

  const getCategoryColor = (category: string) => {
    switch (category) {
      case 'gaming':
        return 'bg-purple-100 text-purple-800';
      case 'entertainment':
        return 'bg-red-100 text-red-800';
      case 'shopping':
        return 'bg-green-100 text-green-800';
      case 'mobile':
        return 'bg-blue-100 text-blue-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  const fallbackImage = `data:image/svg+xml;base64,${btoa(`
    <svg width="300" height="200" xmlns="http://www.w3.org/2000/svg">
      <rect width="100%" height="100%" fill="#f3f4f6"/>
      <text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="#9ca3af" font-family="system-ui" font-size="18">
        ${product.brand}
      </text>
    </svg>
  `)}`;

  return (
    <div className="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
      <Link href={`/products/${product.id}`}>
        <div className="relative h-48 bg-gray-100">
          <Image
            src={product.image}
            alt={product.name}
            fill
            className="object-cover"
            onError={(e) => {
              const target = e.target as HTMLImageElement;
              target.src = fallbackImage;
            }}
          />
          {!product.inStock && (
            <div className="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
              <span className="text-white font-semibold">Out of Stock</span>
            </div>
          )}
        </div>
      </Link>

      <div className="p-4">
        <div className="flex items-start justify-between mb-2">
          <Link href={`/products/${product.id}`}>
            <h3 className="text-lg font-semibold text-gray-900 hover:text-blue-600 transition-colors">
              {product.name}
            </h3>
          </Link>
          <span className={`px-2 py-1 text-xs rounded-full ${getCategoryColor(product.category)}`}>
            {product.category}
          </span>
        </div>

        <p className="text-sm text-gray-600 mb-3 line-clamp-2">
          {product.description}
        </p>

        {/* Denomination Selection */}
        {product.denominations && product.denominations.length > 1 && (
          <div className="mb-3">
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Amount:
            </label>
            <select
              value={selectedDenomination}
              onChange={(e) => setSelectedDenomination(Number(e.target.value))}
              className="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
            >
              {product.denominations.map((amount) => (
                <option key={amount} value={amount}>
                  ${amount}
                </option>
              ))}
            </select>
          </div>
        )}

        <div className="flex items-center justify-between">
          <div className="text-xl font-bold text-gray-900">
            ${selectedDenomination}
          </div>
          
          <button
            onClick={handleAddToCart}
            disabled={!product.inStock}
            className={`flex items-center space-x-2 px-4 py-2 rounded-md text-sm font-medium transition-colors ${
              product.inStock
                ? 'bg-blue-600 text-white hover:bg-blue-700'
                : 'bg-gray-300 text-gray-500 cursor-not-allowed'
            }`}
          >
            <ShoppingCartIcon className="h-4 w-4" />
            <span>{product.inStock ? 'Add to Cart' : 'Out of Stock'}</span>
          </button>
        </div>
      </div>
    </div>
  );
}