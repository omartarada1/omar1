'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useStore } from '@/store/useStore';
import { Order } from '@/types';
import toast from 'react-hot-toast';
import { ClipboardDocumentIcon, CheckIcon, ClockIcon } from '@heroicons/react/24/outline';

export default function CheckoutPage() {
  const { cart, user, getCartTotal, addOrder, clearCart } = useStore();
  const [paymentStep, setPaymentStep] = useState<'details' | 'payment' | 'processing' | 'success'>('details');
  const [copiedAddress, setCopiedAddress] = useState(false);
  const [transactionId, setTransactionId] = useState('');
  const [orderId, setOrderId] = useState('');
  const router = useRouter();

  // Mock USDT TRC20 wallet address
  const usdtWalletAddress = 'TQrZ8tKfjpras94wXQBMFrwTrwT8WyGgFz';
  const totalAmount = getCartTotal();

  useEffect(() => {
    if (!user) {
      router.push('/auth/login');
    }
    if (cart.length === 0) {
      router.push('/cart');
    }
  }, [user, cart, router]);

  const handleCopyAddress = async () => {
    try {
      await navigator.clipboard.writeText(usdtWalletAddress);
      setCopiedAddress(true);
      toast.success('Wallet address copied to clipboard');
      setTimeout(() => setCopiedAddress(false), 2000);
    } catch (error) {
      toast.error('Failed to copy address');
    }
  };

  const handleProceedToPayment = () => {
    setPaymentStep('payment');
  };

  const handleConfirmPayment = () => {
    if (!transactionId.trim()) {
      toast.error('Please enter your transaction ID');
      return;
    }

    setPaymentStep('processing');
    
    // Simulate payment processing
    setTimeout(() => {
      const newOrderId = `ORDER-${Date.now()}`;
      setOrderId(newOrderId);
      
      const order: Order = {
        id: newOrderId,
        userId: user!.id,
        items: cart,
        total: totalAmount,
        status: 'pending',
        paymentStatus: 'paid',
        paymentMethod: 'usdt',
        usdtAddress: usdtWalletAddress,
        usdtAmount: totalAmount,
        createdAt: new Date(),
        updatedAt: new Date()
      };

      addOrder(order);
      clearCart();
      setPaymentStep('success');
      toast.success('Payment confirmed! Your gift cards will be delivered shortly.');
    }, 3000);
  };

  if (!user || cart.length === 0) {
    return <div>Loading...</div>;
  }

  const generateQRCode = (address: string, amount: number) => {
    // This would generate a QR code for USDT payment
    // For demo purposes, we'll use a placeholder QR code
    return `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(`tron:${address}?amount=${amount}`)}`;
  };

  return (
    <div className="max-w-4xl mx-auto">
      <h1 className="text-3xl font-bold text-gray-900 mb-8">Checkout</h1>

      {/* Progress Bar */}
      <div className="mb-8">
        <div className="flex items-center justify-between">
          <div className={`flex items-center ${paymentStep === 'details' ? 'text-blue-600' : 'text-green-600'}`}>
            <div className={`w-8 h-8 rounded-full border-2 flex items-center justify-center ${
              paymentStep === 'details' ? 'border-blue-600 bg-blue-100' : 'border-green-600 bg-green-100'
            }`}>
              {paymentStep === 'details' ? '1' : <CheckIcon className="w-4 h-4" />}
            </div>
            <span className="ml-2 font-medium">Order Details</span>
          </div>
          
          <div className={`flex items-center ${
            paymentStep === 'payment' ? 'text-blue-600' : 
            ['processing', 'success'].includes(paymentStep) ? 'text-green-600' : 'text-gray-400'
          }`}>
            <div className={`w-8 h-8 rounded-full border-2 flex items-center justify-center ${
              paymentStep === 'payment' ? 'border-blue-600 bg-blue-100' :
              ['processing', 'success'].includes(paymentStep) ? 'border-green-600 bg-green-100' :
              'border-gray-300 bg-gray-100'
            }`}>
              {['processing', 'success'].includes(paymentStep) ? <CheckIcon className="w-4 h-4" /> : '2'}
            </div>
            <span className="ml-2 font-medium">Payment</span>
          </div>
          
          <div className={`flex items-center ${paymentStep === 'success' ? 'text-green-600' : 'text-gray-400'}`}>
            <div className={`w-8 h-8 rounded-full border-2 flex items-center justify-center ${
              paymentStep === 'success' ? 'border-green-600 bg-green-100' : 'border-gray-300 bg-gray-100'
            }`}>
              {paymentStep === 'success' ? <CheckIcon className="w-4 h-4" /> : '3'}
            </div>
            <span className="ml-2 font-medium">Complete</span>
          </div>
        </div>
      </div>

      {/* Order Details Step */}
      {paymentStep === 'details' && (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <div className="bg-white rounded-lg shadow-md p-6">
            <h2 className="text-xl font-semibold mb-4">Order Summary</h2>
            <div className="space-y-4">
              {cart.map((item) => (
                <div key={`${item.product.id}-${item.selectedDenomination}`} className="flex justify-between items-center">
                  <div>
                    <h3 className="font-medium">{item.product.name}</h3>
                    <p className="text-sm text-gray-600">
                      ${item.selectedDenomination} × {item.quantity}
                    </p>
                  </div>
                  <p className="font-semibold">
                    ${((item.selectedDenomination || item.product.price) * item.quantity).toFixed(2)}
                  </p>
                </div>
              ))}
              <div className="border-t pt-4">
                <div className="flex justify-between items-center text-lg font-bold">
                  <span>Total</span>
                  <span>${totalAmount.toFixed(2)} USDT</span>
                </div>
              </div>
            </div>
          </div>

          <div className="bg-white rounded-lg shadow-md p-6">
            <h2 className="text-xl font-semibold mb-4">Payment Method</h2>
            <div className="border-2 border-blue-200 rounded-lg p-4 bg-blue-50">
              <div className="flex items-center mb-2">
                <div className="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center text-white font-bold text-sm mr-3">
                  ₮
                </div>
                <div>
                  <h3 className="font-semibold">USDT (TRC20)</h3>
                  <p className="text-sm text-gray-600">Tether on TRON Network</p>
                </div>
              </div>
              <p className="text-sm text-gray-700 mb-4">
                Fast, secure, and low-cost payments on the TRON blockchain.
              </p>
              <div className="bg-white p-3 rounded border">
                <p className="text-xs text-gray-600 mb-1">Amount to pay:</p>
                <p className="text-lg font-bold text-green-600">{totalAmount.toFixed(2)} USDT</p>
              </div>
            </div>

            <button
              onClick={handleProceedToPayment}
              className="w-full mt-6 bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 transition-colors"
            >
              Proceed to Payment
            </button>
          </div>
        </div>
      )}

      {/* Payment Step */}
      {paymentStep === 'payment' && (
        <div className="bg-white rounded-lg shadow-md p-6 max-w-2xl mx-auto">
          <h2 className="text-2xl font-semibold mb-6 text-center">USDT Payment</h2>
          
          <div className="text-center mb-6">
            <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
              <p className="text-yellow-800 font-medium">⚠️ Send EXACTLY {totalAmount.toFixed(2)} USDT</p>
              <p className="text-sm text-yellow-700 mt-1">To the TRC20 address below</p>
            </div>
          </div>

          <div className="flex justify-center mb-6">
            <img
              src={generateQRCode(usdtWalletAddress, totalAmount)}
              alt="USDT Payment QR Code"
              className="w-48 h-48 border border-gray-300 rounded-lg"
            />
          </div>

          <div className="mb-6">
            <label className="block text-sm font-medium text-gray-700 mb-2">
              TRC20 Wallet Address:
            </label>
            <div className="flex items-center space-x-2">
              <input
                type="text"
                value={usdtWalletAddress}
                readOnly
                className="flex-1 p-3 border border-gray-300 rounded-lg bg-gray-50 font-mono text-sm"
              />
              <button
                onClick={handleCopyAddress}
                className="p-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
              >
                {copiedAddress ? (
                  <CheckIcon className="w-5 h-5 text-green-600" />
                ) : (
                  <ClipboardDocumentIcon className="w-5 h-5 text-gray-600" />
                )}
              </button>
            </div>
          </div>

          <div className="mb-6">
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Transaction ID (after sending USDT):
            </label>
            <input
              type="text"
              value={transactionId}
              onChange={(e) => setTransactionId(e.target.value)}
              placeholder="Enter your USDT transaction ID"
              className="w-full p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
            />
            <p className="text-xs text-gray-500 mt-1">
              You can find this in your wallet after sending the payment
            </p>
          </div>

          <button
            onClick={handleConfirmPayment}
            disabled={!transactionId.trim()}
            className="w-full bg-green-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Confirm Payment
          </button>
        </div>
      )}

      {/* Processing Step */}
      {paymentStep === 'processing' && (
        <div className="bg-white rounded-lg shadow-md p-8 max-w-md mx-auto text-center">
          <div className="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-600 mx-auto mb-4"></div>
          <h2 className="text-xl font-semibold mb-2">Processing Payment</h2>
          <p className="text-gray-600">
            We're verifying your USDT transaction on the TRON network. This usually takes 1-3 minutes.
          </p>
        </div>
      )}

      {/* Success Step */}
      {paymentStep === 'success' && (
        <div className="bg-white rounded-lg shadow-md p-8 max-w-md mx-auto text-center">
          <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <CheckIcon className="w-8 h-8 text-green-600" />
          </div>
          <h2 className="text-2xl font-semibold text-green-600 mb-2">Payment Successful!</h2>
          <p className="text-gray-600 mb-4">
            Your order #{orderId} has been confirmed. Your digital gift cards will be delivered to your email shortly.
          </p>
          <div className="space-y-3">
            <button
              onClick={() => router.push('/orders')}
              className="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 transition-colors"
            >
              View Order Details
            </button>
            <button
              onClick={() => router.push('/products')}
              className="w-full border border-gray-300 text-gray-700 py-3 px-4 rounded-lg font-medium hover:bg-gray-50 transition-colors"
            >
              Continue Shopping
            </button>
          </div>
        </div>
      )}
    </div>
  );
}