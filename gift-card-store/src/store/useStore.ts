import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import { Product, CartItem, User, Order } from '@/types';

interface Store {
  // Products
  products: Product[];
  setProducts: (products: Product[]) => void;
  addProduct: (product: Product) => void;
  updateProduct: (id: string, product: Partial<Product>) => void;
  deleteProduct: (id: string) => void;
  
  // Cart
  cart: CartItem[];
  addToCart: (product: Product, quantity?: number, denomination?: number) => void;
  removeFromCart: (productId: string) => void;
  updateCartItem: (productId: string, quantity: number) => void;
  clearCart: () => void;
  getCartTotal: () => number;
  
  // User
  user: User | null;
  setUser: (user: User | null) => void;
  login: (email: string, password: string) => Promise<boolean>;
  register: (email: string, password: string, name: string) => Promise<boolean>;
  logout: () => void;
  
  // Orders
  orders: Order[];
  addOrder: (order: Order) => void;
  updateOrderStatus: (orderId: string, status: Order['status']) => void;
}

// Sample products data
const sampleProducts: Product[] = [
  {
    id: '1',
    name: 'Steam Gift Card',
    brand: 'Steam',
    price: 10,
    image: '/images/steam-card.jpg',
    description: 'Steam digital gift card for gaming purchases',
    category: 'gaming',
    inStock: true,
    denominations: [10, 25, 50, 100]
  },
  {
    id: '2',
    name: 'Google Play Gift Card',
    brand: 'Google',
    price: 15,
    image: '/images/google-play-card.jpg',
    description: 'Google Play gift card for apps, games, and digital content',
    category: 'mobile',
    inStock: true,
    denominations: [15, 25, 50, 100]
  },
  {
    id: '3',
    name: 'Apple App Store Gift Card',
    brand: 'Apple',
    price: 20,
    image: '/images/apple-card.jpg',
    description: 'Apple App Store gift card for iOS apps and content',
    category: 'mobile',
    inStock: true,
    denominations: [20, 50, 100]
  },
  {
    id: '4',
    name: 'Netflix Gift Card',
    brand: 'Netflix',
    price: 25,
    image: '/images/netflix-card.jpg',
    description: 'Netflix gift card for streaming subscription',
    category: 'entertainment',
    inStock: true,
    denominations: [25, 50, 100]
  },
  {
    id: '5',
    name: 'Amazon Gift Card',
    brand: 'Amazon',
    price: 30,
    image: '/images/amazon-card.jpg',
    description: 'Amazon gift card for online shopping',
    category: 'shopping',
    inStock: true,
    denominations: [25, 50, 100, 200]
  },
  {
    id: '6',
    name: 'PlayStation Store Gift Card',
    brand: 'PlayStation',
    price: 20,
    image: '/images/playstation-card.jpg',
    description: 'PlayStation Store gift card for PS4/PS5 games and content',
    category: 'gaming',
    inStock: true,
    denominations: [20, 50, 100]
  }
];

export const useStore = create<Store>()(
  persist(
    (set, get) => ({
      // Products
      products: sampleProducts,
      setProducts: (products) => set({ products }),
      addProduct: (product) => set((state) => ({ 
        products: [...state.products, product] 
      })),
      updateProduct: (id, updatedProduct) => set((state) => ({
        products: state.products.map(p => 
          p.id === id ? { ...p, ...updatedProduct } : p
        )
      })),
      deleteProduct: (id) => set((state) => ({
        products: state.products.filter(p => p.id !== id)
      })),
      
      // Cart
      cart: [],
      addToCart: (product, quantity = 1, denomination) => set((state) => {
        const existingItem = state.cart.find(item => 
          item.product.id === product.id && 
          item.selectedDenomination === denomination
        );
        
        if (existingItem) {
          return {
            cart: state.cart.map(item =>
              item.product.id === product.id && item.selectedDenomination === denomination
                ? { ...item, quantity: item.quantity + quantity }
                : item
            )
          };
        }
        
        return {
          cart: [...state.cart, { 
            product, 
            quantity, 
            selectedDenomination: denomination || product.price 
          }]
        };
      }),
      removeFromCart: (productId) => set((state) => ({
        cart: state.cart.filter(item => item.product.id !== productId)
      })),
      updateCartItem: (productId, quantity) => set((state) => ({
        cart: state.cart.map(item =>
          item.product.id === productId
            ? { ...item, quantity }
            : item
        )
      })),
      clearCart: () => set({ cart: [] }),
      getCartTotal: () => {
        const { cart } = get();
        return cart.reduce((total, item) => 
          total + (item.selectedDenomination || item.product.price) * item.quantity, 0
        );
      },
      
      // User
      user: null,
      setUser: (user) => set({ user }),
      login: async (email, password) => {
        // Mock login - in real app, this would call an API
        if (email === 'admin@example.com' && password === 'admin') {
          set({ user: { id: '1', email, name: 'Admin User', isAdmin: true } });
          return true;
        } else if (email === 'user@example.com' && password === 'user') {
          set({ user: { id: '2', email, name: 'Regular User', isAdmin: false } });
          return true;
        }
        return false;
      },
      register: async (email, password, name) => {
        // Mock register - in real app, this would call an API
        set({ user: { id: Date.now().toString(), email, name, isAdmin: false } });
        return true;
      },
      logout: () => set({ user: null }),
      
      // Orders
      orders: [],
      addOrder: (order) => set((state) => ({ 
        orders: [...state.orders, order] 
      })),
      updateOrderStatus: (orderId, status) => set((state) => ({
        orders: state.orders.map(order =>
          order.id === orderId ? { ...order, status } : order
        )
      }))
    }),
    {
      name: 'gift-card-store',
      partialize: (state) => ({
        cart: state.cart,
        user: state.user,
        orders: state.orders
      })
    }
  )
);