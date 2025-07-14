import os
from dotenv import load_dotenv

load_dotenv()

class Config:
    # Telegram Bot Configuration
    TELEGRAM_BOT_TOKEN = os.getenv('TELEGRAM_BOT_TOKEN')
    TELEGRAM_CHANNEL_ID = os.getenv('TELEGRAM_CHANNEL_ID')
    ADMIN_USER_ID = os.getenv('ADMIN_USER_ID')
    
    # Database Configuration
    DATABASE_URL = os.getenv('DATABASE_URL', 'sqlite:///trading_bot.db')
    
    # Flask Configuration
    SECRET_KEY = os.getenv('SECRET_KEY', 'your-secret-key-change-this')
    FLASK_ENV = os.getenv('FLASK_ENV', 'development')
    
    # Trading Configuration
    TRIAL_DAYS = int(os.getenv('TRIAL_DAYS', 3))
    MONTHLY_SUBSCRIPTION_PRICE = float(os.getenv('MONTHLY_SUBSCRIPTION_PRICE', 29.99))
    
    # Technical Analysis Configuration
    RSI_PERIOD = int(os.getenv('RSI_PERIOD', 14))
    RSI_OVERBOUGHT = int(os.getenv('RSI_OVERBOUGHT', 70))
    RSI_OVERSOLD = int(os.getenv('RSI_OVERSOLD', 30))
    MACD_FAST = int(os.getenv('MACD_FAST', 12))
    MACD_SLOW = int(os.getenv('MACD_SLOW', 26))
    MACD_SIGNAL = int(os.getenv('MACD_SIGNAL', 9))
    
    # Assets to monitor
    MONITORED_ASSETS = [
        'BTC-USD', 'ETH-USD', 'AAPL', 'GOOGL', 'MSFT',
        'EURUSD=X', 'GBPUSD=X', 'USDJPY=X'
    ]
    
    # Signal intervals (in minutes)
    SIGNAL_INTERVAL = int(os.getenv('SIGNAL_INTERVAL', 60))
    
    # Notification settings
    ENABLE_NOTIFICATIONS = os.getenv('ENABLE_NOTIFICATIONS', 'True').lower() == 'true'
    SUBSCRIPTION_WARNING_DAYS = int(os.getenv('SUBSCRIPTION_WARNING_DAYS', 1)) 