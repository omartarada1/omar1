export interface Product {
  id: string;
  name: string;
  brand: string;
  price: number;
  image: string;
  description: string;
  category: 'gaming' | 'entertainment' | 'shopping' | 'mobile';
  inStock: boolean;
  denominations?: number[];
}

export interface CartItem {
  product: Product;
  quantity: number;
  selectedDenomination?: number;
}

export interface User {
  id: string;
  email: string;
  name: string;
  isAdmin: boolean;
}

export interface Order {
  id: string;
  userId: string;
  items: CartItem[];
  total: number;
  status: 'pending' | 'processing' | 'completed' | 'cancelled';
  paymentStatus: 'pending' | 'paid' | 'failed';
  paymentMethod: 'usdt';
  usdtAddress?: string;
  usdtAmount?: number;
  createdAt: Date;
  updatedAt: Date;
}

export interface PaymentDetails {
  amount: number;
  currency: 'USDT';
  network: 'TRC20';
  walletAddress: string;
  transactionId?: string;
}