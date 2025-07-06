# 🎮 GiftCardStore - Digital Gift Cards with USDT Payment

A modern e-commerce website for selling digital gift cards with USDT (TRC20) payment simulation. Built with Next.js, TypeScript, and Tailwind CSS.

## ✨ Features

### 🛍️ Customer Features
- **Product Catalog**: Browse digital gift cards for gaming, entertainment, shopping, and mobile platforms
- **Search & Filter**: Advanced filtering by category, search functionality, and sorting options
- **Shopping Cart**: Add items to cart with quantity management and denomination selection
- **USDT Payment**: Simulated USDT (TRC20) payment system with QR codes and wallet integration
- **User Authentication**: Secure login and registration system
- **Order Management**: Track orders and payment status
- **Responsive Design**: Mobile-first design that works on all devices

### 🔧 Admin Features
- **Product Management**: Add, edit, and delete gift card products
- **Inventory Control**: Manage stock status and availability
- **Dashboard Analytics**: View statistics about products, stock, and categories
- **Category Management**: Organize products by different categories

### 💰 Payment System
- **USDT (TRC20) Support**: Simulated cryptocurrency payment processing
- **QR Code Generation**: Automatic QR code generation for payments
- **Transaction Tracking**: Enter transaction IDs for payment verification
- **Payment Status**: Real-time payment status updates

## 🚀 Technologies Used

- **Frontend**: Next.js 14 with TypeScript
- **Styling**: Tailwind CSS for modern UI design
- **State Management**: Zustand with persistence
- **Icons**: Heroicons for consistent iconography
- **Notifications**: React Hot Toast for user feedback
- **Payment Simulation**: Mock USDT payment system

## 📦 Installation & Setup

1. **Clone the repository**:
   ```bash
   git clone <repository-url>
   cd gift-card-store
   ```

2. **Install dependencies**:
   ```bash
   npm install
   ```

3. **Start the development server**:
   ```bash
   npm run dev
   ```

4. **Open your browser**:
   Navigate to `http://localhost:3000`

## 🎯 Demo Accounts

### Admin Account
- **Email**: `admin@example.com`
- **Password**: `admin`
- **Features**: Full admin panel access, product management

### User Account
- **Email**: `user@example.com`
- **Password**: `user`
- **Features**: Shopping, cart management, checkout

## 🛒 How to Use

### For Customers

1. **Browse Products**: Visit the homepage or products page to view available gift cards
2. **Search & Filter**: Use the search bar and category filters to find specific products
3. **Add to Cart**: Select denominations and add products to your shopping cart
4. **Checkout**: Sign in and proceed to checkout
5. **Pay with USDT**: 
   - View the USDT (TRC20) wallet address and QR code
   - Send the exact amount to the provided address
   - Enter your transaction ID to confirm payment
6. **Order Confirmation**: Receive instant order confirmation and tracking

### For Admins

1. **Login**: Use admin credentials to access the admin panel
2. **Manage Products**: 
   - Add new gift card products
   - Edit existing product details
   - Update stock status
   - Delete products
3. **View Analytics**: Monitor product statistics and inventory

## 🏗️ Project Structure

```
gift-card-store/
├── src/
│   ├── app/                    # Next.js app router pages
│   │   ├── admin/             # Admin panel
│   │   ├── auth/              # Authentication pages
│   │   ├── cart/              # Shopping cart
│   │   ├── checkout/          # Payment process
│   │   ├── products/          # Product listings
│   │   └── layout.tsx         # Root layout
│   ├── components/            # React components
│   │   ├── Header.tsx         # Navigation header
│   │   └── ProductCard.tsx    # Product display card
│   ├── store/                 # State management
│   │   └── useStore.ts        # Zustand store
│   └── types/                 # TypeScript definitions
│       └── index.ts           # Type definitions
├── public/                    # Static assets
└── README.md                  # This file
```

## 🔧 Key Components

### State Management (Zustand)
- **Products**: Manage gift card inventory
- **Cart**: Shopping cart functionality
- **User**: Authentication and user management
- **Orders**: Order tracking and history

### Payment Flow
1. **Order Review**: Review cart items and total
2. **Payment Method**: Select USDT (TRC20) payment
3. **Payment Details**: Display wallet address and QR code
4. **Transaction Entry**: User enters transaction ID
5. **Verification**: Simulated payment verification
6. **Confirmation**: Order completion and receipt

### Product Categories
- **Gaming**: Steam, PlayStation, Xbox gift cards
- **Entertainment**: Netflix, Spotify streaming services
- **Shopping**: Amazon, retail gift cards
- **Mobile**: Google Play, App Store credits

## 🛡️ Security Features

- **Input Validation**: Form validation for all user inputs
- **Admin Authorization**: Protected admin routes
- **State Persistence**: Secure local storage of cart and user data
- **Error Handling**: Comprehensive error handling and user feedback

## 🎨 Design Features

- **Modern UI**: Clean, professional design
- **Responsive Layout**: Mobile-first approach
- **Loading States**: Smooth loading indicators
- **Toast Notifications**: Real-time user feedback
- **Accessibility**: WCAG compliant design

## 🔮 Future Enhancements

- **Real Blockchain Integration**: Connect to actual TRON network
- **Email Notifications**: Automated order confirmations
- **Multi-language Support**: Internationalization
- **Advanced Analytics**: Detailed sales reporting
- **Loyalty Program**: Customer rewards system
- **Live Chat Support**: Customer service integration

## 📱 Mobile Experience

The application is fully responsive and optimized for mobile devices:
- **Touch-friendly Interface**: Large buttons and touch targets
- **Mobile Navigation**: Collapsible menu for small screens
- **Optimized Forms**: Mobile-friendly input fields
- **Fast Loading**: Optimized images and code splitting

## 🐛 Known Limitations

- **Payment Simulation**: This is a demo with simulated USDT payments
- **No Real Transactions**: No actual cryptocurrency transactions occur
- **Demo Data**: Uses mock data for products and orders
- **No Email System**: Email notifications are simulated

## 📄 License

This project is created for demonstration purposes. Feel free to use it as a starting point for your own e-commerce projects.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📞 Support

For questions or support, please create an issue in the repository.

---

**Note**: This is a demonstration project with simulated payment functionality. Do not use for actual cryptocurrency transactions without proper security audits and real blockchain integration.
