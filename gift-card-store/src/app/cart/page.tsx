'use client';

import { useStore } from '@/store/useStore';
import Link from 'next/link';
import Image from 'next/image';
import { MinusIcon, PlusIcon, TrashIcon } from '@heroicons/react/24/outline';
import toast from 'react-hot-toast';

export default function CartPage() {
  const { cart, updateCartItem, removeFromCart, getCartTotal, user } = useStore();

  const handleQuantityChange = (productId: string, newQuantity: number) => {
    if (newQuantity < 1) {
      removeFromCart(productId);
      toast.success('Item removed from cart');
    } else {
      updateCartItem(productId, newQuantity);
    }
  };

  const handleRemoveItem = (productId: string) => {
    removeFromCart(productId);
    toast.success('Item removed from cart');
  };

  const fallbackImage = (brand: string) => `data:image/svg+xml;base64,${btoa(`
    <svg width="300" height="200" xmlns="http://www.w3.org/2000/svg">
      <rect width="100%" height="100%" fill="#f3f4f6"/>
      <text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="#9ca3af" font-family="system-ui" font-size="18">
        ${brand}
      </text>
    </svg>
  `)}`;

  if (cart.length === 0) {
    return (
      <div className="text-center py-16">
        <div className="max-w-md mx-auto">
          <div className="bg-gray-100 rounded-full w-24 h-24 mx-auto mb-4 flex items-center justify-center">
            <svg className="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 3h2l.4 2M7 13h10l4-8H5.4m-.4-2L3 3m4 10v6a1 1 0 001 1h8a1 1 0 001-1v-6M9 19v2m6-2v2" />
            </svg>
          </div>
          <h2 className="text-2xl font-bold text-gray-900 mb-2">Your cart is empty</h2>
          <p className="text-gray-600 mb-6">
            Start shopping to add some digital gift cards to your cart.
          </p>
          <Link
            href="/products"
            className="bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 transition-colors inline-block"
          >
            Browse Products
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="max-w-4xl mx-auto">
      <h1 className="text-3xl font-bold text-gray-900 mb-8">Shopping Cart</h1>
      
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {/* Cart Items */}
        <div className="lg:col-span-2">
          <div className="bg-white rounded-lg shadow-md">
            <div className="p-6">
              <h2 className="text-xl font-semibold mb-4">
                Cart Items ({cart.reduce((sum, item) => sum + item.quantity, 0)})
              </h2>
              
              <div className="space-y-4">
                {cart.map((item) => (
                  <div key={`${item.product.id}-${item.selectedDenomination}`} className="flex items-center space-x-4 p-4 border border-gray-200 rounded-lg">
                    <div className="relative w-20 h-20 flex-shrink-0">
                      <Image
                        src={item.product.image}
                        alt={item.product.name}
                        fill
                        className="object-cover rounded-md"
                        onError={(e) => {
                          const target = e.target as HTMLImageElement;
                          target.src = fallbackImage(item.product.brand);
                        }}
                      />
                    </div>
                    
                    <div className="flex-grow">
                      <h3 className="font-semibold text-gray-900">{item.product.name}</h3>
                      <p className="text-sm text-gray-600">{item.product.brand}</p>
                      <p className="text-lg font-bold text-blue-600">
                        ${item.selectedDenomination || item.product.price}
                      </p>
                    </div>
                    
                    <div className="flex items-center space-x-2">
                      <button
                        onClick={() => handleQuantityChange(item.product.id, item.quantity - 1)}
                        className="p-1 text-gray-500 hover:text-gray-700"
                      >
                        <MinusIcon className="h-4 w-4" />
                      </button>
                      <span className="w-8 text-center font-medium">{item.quantity}</span>
                      <button
                        onClick={() => handleQuantityChange(item.product.id, item.quantity + 1)}
                        className="p-1 text-gray-500 hover:text-gray-700"
                      >
                        <PlusIcon className="h-4 w-4" />
                      </button>
                    </div>
                    
                    <div className="text-right">
                      <p className="font-semibold text-gray-900">
                        ${((item.selectedDenomination || item.product.price) * item.quantity).toFixed(2)}
                      </p>
                      <button
                        onClick={() => handleRemoveItem(item.product.id)}
                        className="text-red-500 hover:text-red-700 mt-1"
                      >
                        <TrashIcon className="h-4 w-4" />
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
        
        {/* Order Summary */}
        <div className="lg:col-span-1">
          <div className="bg-white rounded-lg shadow-md p-6 sticky top-4">
            <h2 className="text-xl font-semibold mb-4">Order Summary</h2>
            
            <div className="space-y-3 mb-4">
              <div className="flex justify-between">
                <span>Subtotal</span>
                <span>${getCartTotal().toFixed(2)}</span>
              </div>
              <div className="flex justify-between">
                <span>Processing Fee</span>
                <span>$0.00</span>
              </div>
              <div className="border-t pt-3">
                <div className="flex justify-between text-lg font-semibold">
                  <span>Total</span>
                  <span>${getCartTotal().toFixed(2)}</span>
                </div>
              </div>
            </div>
            
            <div className="space-y-3">
              {user ? (
                <Link
                  href="/checkout"
                  className="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 transition-colors text-center block"
                >
                  Proceed to Checkout
                </Link>
              ) : (
                <div className="text-center">
                  <p className="text-sm text-gray-600 mb-3">
                    Please sign in to continue
                  </p>
                  <Link
                    href="/auth/login"
                    className="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 transition-colors text-center block"
                  >
                    Sign In to Checkout
                  </Link>
                </div>
              )}
              
              <Link
                href="/products"
                className="w-full border border-gray-300 text-gray-700 py-3 px-4 rounded-lg font-medium hover:bg-gray-50 transition-colors text-center block"
              >
                Continue Shopping
              </Link>
            </div>
            
            <div className="mt-6 p-4 bg-green-50 rounded-lg">
              <div className="flex items-center">
                <svg className="w-5 h-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                </svg>
                <span className="text-sm text-green-800 font-medium">
                  Instant Digital Delivery
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}