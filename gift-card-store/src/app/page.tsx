'use client';

import { useStore } from '@/store/useStore';
import ProductCard from '@/components/ProductCard';
import Link from 'next/link';
import { ShoppingBagIcon, CreditCardIcon, ShieldCheckIcon, GlobeAltIcon } from '@heroicons/react/24/outline';

export default function Home() {
  const { products } = useStore();
  const featuredProducts = products.slice(0, 6);

  const features = [
    {
      icon: <ShoppingBagIcon className="h-8 w-8" />,
      title: 'Wide Selection',
      description: 'Choose from hundreds of digital gift cards for gaming, entertainment, and shopping.'
    },
    {
      icon: <CreditCardIcon className="h-8 w-8" />,
      title: 'USDT Payment',
      description: 'Pay safely with USDT on the TRC20 network for fast and secure transactions.'
    },
    {
      icon: <ShieldCheckIcon className="h-8 w-8" />,
      title: 'Instant Delivery',
      description: 'Receive your digital gift cards instantly after successful payment.'
    },
    {
      icon: <GlobeAltIcon className="h-8 w-8" />,
      title: 'Global Access',
      description: 'Access your purchases from anywhere in the world, 24/7.'
    }
  ];

  const categories = [
    { name: 'Gaming', count: products.filter(p => p.category === 'gaming').length, color: 'bg-purple-500' },
    { name: 'Entertainment', count: products.filter(p => p.category === 'entertainment').length, color: 'bg-red-500' },
    { name: 'Shopping', count: products.filter(p => p.category === 'shopping').length, color: 'bg-green-500' },
    { name: 'Mobile', count: products.filter(p => p.category === 'mobile').length, color: 'bg-blue-500' }
  ];

  return (
    <div>
      {/* Hero Section */}
      <section className="bg-gradient-to-r from-blue-600 to-purple-700 text-white rounded-lg mb-12">
        <div className="px-8 py-16 text-center">
          <h1 className="text-4xl md:text-6xl font-bold mb-6">
            Digital Gift Cards
          </h1>
          <p className="text-xl md:text-2xl mb-8 opacity-90">
            Pay with USDT (TRC20) • Instant Delivery • Secure & Safe
          </p>
          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <Link
              href="/products"
              className="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors"
            >
              Browse Products
            </Link>
            <Link
              href="/auth/register"
              className="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-blue-600 transition-colors"
            >
              Sign Up Now
            </Link>
          </div>
        </div>
      </section>

      {/* Features Section */}
      <section className="mb-12">
        <h2 className="text-3xl font-bold text-center mb-8">Why Choose Us?</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          {features.map((feature, index) => (
            <div key={index} className="bg-white p-6 rounded-lg shadow-md text-center">
              <div className="text-blue-600 mb-4 flex justify-center">
                {feature.icon}
              </div>
              <h3 className="text-xl font-semibold mb-2">{feature.title}</h3>
              <p className="text-gray-600">{feature.description}</p>
            </div>
          ))}
        </div>
      </section>

      {/* Categories Section */}
      <section className="mb-12">
        <h2 className="text-3xl font-bold text-center mb-8">Shop by Category</h2>
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          {categories.map((category) => (
            <Link
              key={category.name}
              href={`/categories?filter=${category.name.toLowerCase()}`}
              className="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow text-center group"
            >
              <div className={`w-16 h-16 ${category.color} rounded-full mx-auto mb-4 flex items-center justify-center group-hover:scale-110 transition-transform`}>
                <span className="text-white font-bold text-xl">{category.count}</span>
              </div>
              <h3 className="text-lg font-semibold">{category.name}</h3>
              <p className="text-gray-600 text-sm">{category.count} products</p>
            </Link>
          ))}
        </div>
      </section>

      {/* Featured Products */}
      <section className="mb-12">
        <div className="flex justify-between items-center mb-8">
          <h2 className="text-3xl font-bold">Featured Products</h2>
          <Link
            href="/products"
            className="text-blue-600 hover:text-blue-800 font-medium"
          >
            View All →
          </Link>
        </div>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {featuredProducts.map((product) => (
            <ProductCard key={product.id} product={product} />
          ))}
        </div>
      </section>

      {/* CTA Section */}
      <section className="bg-gray-900 text-white rounded-lg p-8 text-center">
        <h2 className="text-3xl font-bold mb-4">Ready to Get Started?</h2>
        <p className="text-xl mb-6 opacity-90">
          Join thousands of customers who trust us for their digital gift card needs.
        </p>
        <Link
          href="/auth/register"
          className="bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors inline-block"
        >
          Create Account
        </Link>
      </section>
    </div>
  );
}
