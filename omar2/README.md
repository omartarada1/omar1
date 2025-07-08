# iCloud Unlock Pro - Professional iCloud Unlocking Service Website

A complete, professional website for iCloud unlocking services with integrated payment processing, admin dashboard, and email notifications.

## 🚀 Features

### 🔵 **Public Website (User-facing)**
- **Landing Page**: Professional design explaining the service and supported devices
- **Request Form**: Complete form with device type, IMEI/Serial number, email, and description
- **Dynamic Pricing**: Device-based pricing loaded from admin panel
- **Multiple Payment Options**:
  - Credit Card payments via Stripe
  - PayPal payments
  - USDT (TRC20/ERC20) with editable wallet addresses
- **Email Notifications**: Automatic emails to both admin and customer
- **Responsive Design**: Mobile and desktop optimized
- **WhatsApp Support**: Floating support button

### 🔵 **Admin Dashboard**
- **Secure Login System**: Username/password authentication
- **Dashboard Analytics**: Statistics and recent requests overview
- **Request Management**: View and update all unlock requests
- **Pricing Management**: Add/edit device types and pricing
- **Wallet Management**: Update USDT wallet addresses
- **Payment Status Management**: Track paid/pending/failed payments

### 🔵 **Technical Features**
- **Modern Tech Stack**: HTML5, CSS3, JavaScript, PHP, MySQL
- **Payment Processing**: Stripe and PayPal integration
- **Email System**: Professional HTML email templates
- **Security**: Input validation, SQL injection protection, XSS prevention
- **Database**: Comprehensive MySQL database structure

## 📁 Project Structure

```
icloud-unlock-service/
├── index.html              # Main landing page
├── setup.php              # Database initialization script
├── css/
│   └── style.css          # Main stylesheet
├── js/
│   └── script.js          # Frontend JavaScript
├── php/
│   ├── process_request.php # Main form processing
│   ├── payment_handler.php # Payment processing
│   ├── email_handler.php   # Email functionality
│   ├── get_pricing.php     # Pricing API endpoint
│   └── get_wallets.php     # Wallet addresses API
├── config/
│   └── database.php        # Database configuration
├── admin/
│   ├── index.php          # Admin login
│   ├── dashboard.php      # Admin dashboard
│   └── logout.php         # Admin logout
└── assets/
    └── images/            # Image assets
```

## 🛠️ Installation & Setup

### Prerequisites
- Web server (Apache/Nginx)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer (for Stripe PHP library)

### Step 1: Database Configuration

1. Edit `config/database.php` and update your database credentials:
```php
private $host = 'localhost';
private $db_name = 'icloud_unlock_db';
private $username = 'your_db_username';
private $password = 'your_db_password';
```

### Step 2: Install Dependencies

Install Stripe PHP library via Composer:
```bash
cd icloud-unlock-service/php
composer require stripe/stripe-php
```

### Step 3: Database Setup

1. Visit `http://yourwebsite.com/setup.php` in your browser
2. The script will create the database and all necessary tables
3. Default admin credentials will be created:
   - Username: `admin`
   - Password: `admin123`
4. **Important**: Change the default password immediately!
5. Delete `setup.php` after successful setup

### Step 4: Payment Gateway Configuration

#### Stripe Setup
1. Get your Stripe API keys from [Stripe Dashboard](https://dashboard.stripe.com/apikeys)
2. Update the following in your database `settings` table:
   - `stripe_public_key`: Your publishable key
   - `stripe_secret_key`: Your secret key
3. Update the JavaScript in `index.html`:
```javascript
stripe = Stripe('your_actual_publishable_key');
```

#### PayPal Setup
1. Get your PayPal client ID from [PayPal Developer](https://developer.paypal.com/)
2. Update in `index.html`:
```javascript
script src="https://www.paypal.com/sdk/js?client-id=YOUR_ACTUAL_CLIENT_ID&currency=USD"
```
3. Update the database `settings` table with your PayPal credentials

### Step 5: Email Configuration

Update SMTP settings in the database `settings` table:
- `smtp_host`: Your SMTP server (e.g., smtp.gmail.com)
- `smtp_port`: SMTP port (usually 587 for TLS)
- `smtp_username`: Your email username
- `smtp_password`: Your email password
- `smtp_encryption`: Encryption type (tls/ssl)
- `admin_email`: Email to receive notifications

### Step 6: Final Configuration

1. Update WhatsApp number in `index.html` and database settings
2. Update USDT wallet addresses in admin panel
3. Test all functionality

## 🎛️ Admin Panel Usage

### Login
1. Go to `http://yourwebsite.com/admin/`
2. Use admin credentials (change default password!)

### Dashboard Features
- **Dashboard**: Overview statistics and recent requests
- **Unlock Requests**: View and manage all requests
- **Pricing Management**: Update device unlock prices
- **Wallet Addresses**: Manage USDT payment addresses
- **Settings**: System configuration

### Managing Requests
1. View all requests in the "Unlock Requests" section
2. Click "Edit" to update request status
3. Change payment status (pending/paid/failed)
4. Update request status (pending/processing/completed/cancelled)

## 🔧 Customization

### Styling
- Edit `css/style.css` for design changes
- Update colors, fonts, and layout as needed
- Responsive design is built-in

### Content
- Update text content in `index.html`
- Modify email templates in `php/email_handler.php`
- Add/remove device types in database

### Features
- Add new payment methods in `php/payment_handler.php`
- Extend admin panel functionality in `admin/dashboard.php`
- Add custom fields to request form

## 🔒 Security Features

- **Input Validation**: All user inputs are validated and sanitized
- **SQL Injection Protection**: Prepared statements used throughout
- **XSS Prevention**: All outputs are properly escaped
- **Session Management**: Secure admin session handling
- **Password Hashing**: Bcrypt password hashing
- **CSRF Protection**: Can be added for enhanced security

## 📧 Email Templates

Professional HTML email templates are included for:
- Customer order confirmation
- Admin new request notification

Templates are fully customizable in `php/email_handler.php`

## 🎨 Design Features

- **Modern UI**: Clean, professional design
- **Responsive**: Works on all devices
- **Animations**: Smooth transitions and hover effects
- **Icons**: Font Awesome icons throughout
- **Typography**: Inter font for modern look

## 🚀 Going Live

### Production Checklist
1. ✅ Update database credentials
2. ✅ Configure payment gateways
3. ✅ Set up email SMTP
4. ✅ Change default admin password
5. ✅ Delete setup.php
6. ✅ Test all functionality
7. ✅ Enable SSL certificate
8. ✅ Set up backups

### Recommended Hosting
- VPS with PHP 7.4+
- MySQL database
- SSL certificate
- Email service (SMTP)

## 💳 Payment Processing

### Supported Payment Methods
1. **Credit Cards** (via Stripe)
   - Visa, MasterCard, American Express
   - Secure tokenization
   - PCI compliant

2. **PayPal**
   - PayPal account payments
   - PayPal checkout integration

3. **USDT Cryptocurrency**
   - TRC20 (Tron network)
   - ERC20 (Ethereum network)
   - Manual verification required

## 📊 Database Schema

### Tables Created
- `unlock_requests`: Customer requests and order data
- `admin_users`: Admin login credentials
- `settings`: System configuration
- `pricing`: Device pricing information

## 🐛 Troubleshooting

### Common Issues

**Database Connection Failed**
- Check database credentials in `config/database.php`
- Ensure MySQL server is running
- Verify database permissions

**Email Not Sending**
- Check SMTP settings in database
- Verify email credentials
- Test with a simple email client

**Payment Issues**
- Verify API keys are correct
- Check if in test/live mode
- Review payment gateway logs

**Admin Login Issues**
- Verify admin credentials
- Check session configuration
- Clear browser cache

## 📱 Mobile Optimization

The website is fully responsive and optimized for:
- iPhone and Android devices
- Tablets and iPads
- Desktop computers
- Various screen sizes

## 🔄 Updates & Maintenance

### Regular Maintenance
- Monitor database size
- Check email delivery
- Update payment gateway settings
- Review security logs
- Backup database regularly

### Feature Updates
- Payment gateway API updates
- Security patches
- New device type additions
- UI/UX improvements

## 📞 Support

For technical support or customization requests:
- Review this documentation
- Check the troubleshooting section
- Verify all configuration steps

## 📄 License

This project is provided as-is for educational and commercial use.

---

**⚠️ Important Security Notes:**
- Always use HTTPS in production
- Regularly update PHP and dependencies
- Monitor for security vulnerabilities
- Implement additional security measures as needed
- Keep backup of database and files

**🎯 Business Notes:**
- Ensure compliance with local laws
- Verify iCloud unlocking regulations in your jurisdiction
- Implement proper customer support channels
- Consider adding terms of service and privacy policy