# 🤖 Telegram Trading Signals Bot

A comprehensive automated trading signals system with Telegram bot integration, admin dashboard, and technical analysis capabilities.

## 🚀 Features

### 🤖 Telegram Bot
- **User Registration**: Automatic user registration with trial subscriptions
- **Subscription Management**: Free trial, monthly, quarterly, and yearly plans
- **Signal Delivery**: Real-time trading signals to channel and subscribers
- **Commands**: `/start`, `/help`, `/subscribe`, `/status`, `/signals`
- **Notifications**: Automated expiry warnings and system updates

### 📊 Admin Dashboard
- **Modern UI**: Beautiful Bootstrap-based interface
- **Signal Management**: Create, edit, delete, and track signals
- **User Management**: View subscribers, extend subscriptions, search users
- **Analytics**: Performance charts, success rates, profit tracking
- **Broadcast System**: Send announcements to all subscribers
- **Settings**: Configure bot parameters and technical analysis

### 📈 Technical Analysis
- **Automated Signals**: RSI, MACD, Bollinger Bands, EMA Crossover
- **Multiple Assets**: Crypto, Forex, Stocks (BTC-USD, EURUSD=X, AAPL, etc.)
- **Real-time Data**: Yahoo Finance integration via yfinance
- **Configurable Strategies**: Adjustable parameters for all indicators
- **Signal Strength**: Strong, medium, weak signal classification

### 💾 Database
- **User Management**: Subscriber profiles and subscription tracking
- **Signal History**: Complete trading signal database
- **Performance Analytics**: Win/loss tracking and success rates
- **Notifications**: Message history and delivery tracking

## 🛠️ Installation

### Prerequisites
- Python 3.8+
- Telegram Bot Token (from @BotFather)
- Telegram Channel ID

### Setup

1. **Clone the repository**
```bash
git clone <repository-url>
cd trading-signals-bot
```

2. **Install dependencies**
```bash
pip install -r requirements.txt
```

3. **Configure environment**
```bash
cp env_example.txt .env
# Edit .env with your configuration
```

4. **Set up Telegram Bot**
   - Message @BotFather on Telegram
   - Create a new bot with `/newbot`
   - Get your bot token
   - Add bot to your channel as admin
   - Get channel ID

5. **Configure .env file**
```env
TELEGRAM_BOT_TOKEN=your_bot_token_here
TELEGRAM_CHANNEL_ID=your_channel_id_here
ADMIN_USER_ID=your_admin_user_id_here
DATABASE_URL=sqlite:///trading_bot.db
SECRET_KEY=your-secret-key-here
```

6. **Run the application**
```bash
python app.py
```

7. **Access admin dashboard**
   - Open http://localhost:5000
   - Login with: admin / admin123

## 📋 Configuration

### Bot Settings
- `TRIAL_DAYS`: Free trial duration (default: 3)
- `MONTHLY_SUBSCRIPTION_PRICE`: Monthly plan price (default: $29.99)
- `SIGNAL_INTERVAL`: Analysis frequency in minutes (default: 60)

### Technical Analysis
- `RSI_PERIOD`: RSI calculation period (default: 14)
- `RSI_OVERBOUGHT`: RSI overbought threshold (default: 70)
- `RSI_OVERSOLD`: RSI oversold threshold (default: 30)
- `MACD_FAST`: MACD fast period (default: 12)
- `MACD_SLOW`: MACD slow period (default: 26)
- `MACD_SIGNAL`: MACD signal period (default: 9)

### Monitored Assets
Default assets monitored for signals:
- **Crypto**: BTC-USD, ETH-USD
- **Stocks**: AAPL, GOOGL, MSFT
- **Forex**: EURUSD=X, GBPUSD=X, USDJPY=X

## 🎯 Usage

### For Users
1. **Start the bot**: Send `/start` to your bot
2. **Get trial**: Automatically receive 3-day free trial
3. **Subscribe**: Use `/subscribe` to upgrade to premium
4. **View signals**: Use `/signals` to see recent signals
5. **Check status**: Use `/status` to view subscription details

### For Admins
1. **Access dashboard**: Login at http://localhost:5000
2. **Create signals**: Manual signal creation with full details
3. **Manage users**: View, search, and extend subscriptions
4. **View analytics**: Performance charts and statistics
5. **Send broadcasts**: Announcements to all subscribers
6. **Configure settings**: Adjust bot parameters and strategies

## 📊 Features in Detail

### Automated Technical Analysis
- **RSI Strategy**: Oversold (<30) and overbought (>70) signals
- **MACD Strategy**: Bullish and bearish crossover signals
- **Bollinger Bands**: Price touching upper/lower bands
- **EMA Crossover**: 20/50 EMA crossover signals
- **Signal Strength**: Prioritized by strength (strong > medium > weak)

### Subscription System
- **Free Trial**: 3-day automatic trial for new users
- **Monthly Plan**: $29.99/month with all features
- **Quarterly Plan**: $79.99 (save $10)
- **Yearly Plan**: $299.99 (save $60)
- **Auto-renewal**: Automatic subscription management

### Admin Dashboard Features
- **Real-time Statistics**: Users, subscriptions, signals count
- **Performance Charts**: Success rates and profit tracking
- **User Management**: Search, extend, suspend users
- **Signal Management**: Create, edit, delete signals
- **Broadcast System**: Send messages to all subscribers
- **Settings Panel**: Configure all bot parameters

## 🔧 Technical Stack

- **Backend**: Python Flask
- **Database**: SQLAlchemy (SQLite/PostgreSQL/MySQL)
- **Telegram Bot**: python-telegram-bot
- **Technical Analysis**: yfinance, ta, pandas
- **Charts**: Plotly, Chart.js
- **Frontend**: Bootstrap 5, Font Awesome
- **Authentication**: Flask-Login

## 📈 Performance Tracking

The system automatically tracks:
- **Signal Success Rate**: Win/loss ratio
- **Profit/Loss**: Per trade and total
- **Strategy Performance**: Success rates by strategy
- **User Engagement**: Active subscriptions and usage

## 🔒 Security Features

- **Admin Authentication**: Secure login system
- **User Data Protection**: Encrypted sensitive information
- **Rate Limiting**: Prevents spam and abuse
- **Input Validation**: Secure form handling

## 🚀 Deployment

### Production Setup
1. **Use production database**: PostgreSQL or MySQL
2. **Set secure SECRET_KEY**: Generate strong secret key
3. **Configure HTTPS**: Use SSL certificate
4. **Set up monitoring**: Log monitoring and alerts
5. **Backup strategy**: Regular database backups

### Docker Deployment
```bash
# Build image
docker build -t trading-bot .

# Run container
docker run -p 5000:5000 trading-bot
```

## 🤝 Contributing

1. Fork the repository
2. Create feature branch
3. Make changes
4. Test thoroughly
5. Submit pull request

## 📄 License

This project is licensed under the MIT License.

## 🆘 Support

For support and questions:
- Create an issue on GitHub
- Contact the development team
- Check the documentation

## 🔮 Future Enhancements

- **Payment Integration**: Stripe/PayPal integration
- **Multi-language**: Arabic/English support
- **Advanced Strategies**: More technical indicators
- **Mobile App**: Native mobile application
- **API Integration**: TradingView, Binance APIs
- **Machine Learning**: AI-powered signal generation

---

**Disclaimer**: This is for educational purposes. Always do your own research before trading. Past performance doesn't guarantee future results. 