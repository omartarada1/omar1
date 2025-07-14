import asyncio
import logging
from datetime import datetime, timedelta
from telegram import Update, InlineKeyboardButton, InlineKeyboardMarkup
from telegram.ext import Application, CommandHandler, CallbackQueryHandler, MessageHandler, filters, ContextTypes
from telegram.constants import ParseMode
import json

from models import db, User, Subscription, Signal, Notification, SubscriptionStatus
from config import Config
from technical_analysis import TechnicalAnalyzer

logging.basicConfig(
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    level=logging.INFO
)
logger = logging.getLogger(__name__)

class TradingBot:
    def __init__(self, config: Config):
        self.config = config
        self.analyzer = TechnicalAnalyzer(config)
        self.application = None
        
    async def start(self):
        """Initialize and start the bot"""
        self.application = Application.builder().token(self.config.TELEGRAM_BOT_TOKEN).build()
        
        # Add handlers
        self.application.add_handler(CommandHandler("start", self.start_command))
        self.application.add_handler(CommandHandler("help", self.help_command))
        self.application.add_handler(CommandHandler("subscribe", self.subscribe_command))
        self.application.add_handler(CommandHandler("status", self.status_command))
        self.application.add_handler(CommandHandler("signals", self.signals_command))
        self.application.add_handler(CallbackQueryHandler(self.button_callback))
        
        # Start the bot
        await self.application.initialize()
        await self.application.start()
        await self.application.updater.start_polling()
        
    async def start_command(self, update: Update, context: ContextTypes.DEFAULT_TYPE):
        """Handle /start command"""
        user = update.effective_user
        
        # Check if user exists in database
        db_user = User.query.filter_by(telegram_id=str(user.id)).first()
        
        if not db_user:
            # Create new user
            db_user = User(
                telegram_id=str(user.id),
                username=user.username,
                first_name=user.first_name,
                last_name=user.last_name
            )
            db.session.add(db_user)
            db.session.commit()
            
            # Create trial subscription
            trial_end = datetime.utcnow() + timedelta(days=self.config.TRIAL_DAYS)
            subscription = Subscription(
                user_id=db_user.id,
                status=SubscriptionStatus.TRIAL,
                end_date=trial_end
            )
            db.session.add(subscription)
            db.session.commit()
            
            welcome_message = f"""
🎉 Welcome to our Trading Signals Bot!

You've been given a {self.config.TRIAL_DAYS}-day free trial to test our premium trading signals.

During your trial, you'll receive:
✅ Real-time trading signals
✅ Technical analysis alerts
✅ Market insights

Use /subscribe to upgrade to premium when your trial ends.

Happy trading! 📈
            """
        else:
            subscription = db_user.subscription
            if subscription and subscription.is_active():
                welcome_message = f"""
Welcome back, {user.first_name}! 👋

Your subscription is active with {subscription.days_remaining()} days remaining.

Use /signals to view recent signals or /status to check your subscription.
                """
            else:
                welcome_message = f"""
Welcome back, {user.first_name}! 👋

Your subscription has expired. Use /subscribe to renew and continue receiving premium signals.
                """
        
        keyboard = [
            [InlineKeyboardButton("📊 View Signals", callback_data="view_signals")],
            [InlineKeyboardButton("💳 Subscribe", callback_data="subscribe")],
            [InlineKeyboardButton("ℹ️ Help", callback_data="help")]
        ]
        reply_markup = InlineKeyboardMarkup(keyboard)
        
        await update.message.reply_text(
            welcome_message.strip(),
            reply_markup=reply_markup,
            parse_mode=ParseMode.MARKDOWN
        )
    
    async def help_command(self, update: Update, context: ContextTypes.DEFAULT_TYPE):
        """Handle /help command"""
        help_text = """
🤖 **Trading Signals Bot Commands**

**Basic Commands:**
/start - Start the bot and check subscription
/help - Show this help message
/status - Check your subscription status
/subscribe - Subscribe to premium signals
/signals - View recent trading signals

**Subscription Options:**
• Free Trial: {trial_days} days
• Monthly Premium: ${monthly_price}
• Automatic renewal available

**Features:**
✅ Real-time trading signals
✅ Technical analysis (RSI, MACD, Bollinger Bands)
✅ Multiple assets (Crypto, Forex, Stocks)
✅ Performance tracking
✅ Custom alerts

**Support:**
Contact @admin for support or questions.
        """.format(
            trial_days=self.config.TRIAL_DAYS,
            monthly_price=self.config.MONTHLY_SUBSCRIPTION_PRICE
        )
        
        await update.message.reply_text(help_text, parse_mode=ParseMode.MARKDOWN)
    
    async def subscribe_command(self, update: Update, context: ContextTypes.DEFAULT_TYPE):
        """Handle /subscribe command"""
        user = update.effective_user
        db_user = User.query.filter_by(telegram_id=str(user.id)).first()
        
        if not db_user:
            await update.message.reply_text("Please use /start first to register.")
            return
        
        subscription = db_user.subscription
        
        if subscription and subscription.is_active():
            days_left = subscription.days_remaining()
            message = f"""
Your subscription is already active! ✅

Days remaining: {days_left}
Status: {subscription.status.value.title()}

You can continue receiving signals until your subscription expires.
            """
        else:
            keyboard = [
                [InlineKeyboardButton("💳 Monthly ($29.99)", callback_data="subscribe_monthly")],
                [InlineKeyboardButton("📅 3 Months ($79.99)", callback_data="subscribe_quarterly")],
                [InlineKeyboardButton("📅 1 Year ($299.99)", callback_data="subscribe_yearly")]
            ]
            reply_markup = InlineKeyboardMarkup(keyboard)
            
            message = f"""
💳 **Subscription Plans**

Choose your preferred plan:

**Monthly Plan:**
• ${monthly_price}/month
• All premium signals
• Technical analysis
• Performance tracking

**3-Month Plan:**
• $79.99 (Save $10)
• All monthly features
• Priority support

**Yearly Plan:**
• $299.99 (Save $60)
• All features
• VIP support
• Custom alerts

Select your plan below:
            """.format(monthly_price=self.config.MONTHLY_SUBSCRIPTION_PRICE)
            
            await update.message.reply_text(
                message.strip(),
                reply_markup=reply_markup,
                parse_mode=ParseMode.MARKDOWN
            )
            return
        
        await update.message.reply_text(message.strip())
    
    async def status_command(self, update: Update, context: ContextTypes.DEFAULT_TYPE):
        """Handle /status command"""
        user = update.effective_user
        db_user = User.query.filter_by(telegram_id=str(user.id)).first()
        
        if not db_user:
            await update.message.reply_text("Please use /start first to register.")
            return
        
        subscription = db_user.subscription
        
        if not subscription:
            await update.message.reply_text("No subscription found. Use /subscribe to get started.")
            return
        
        status_emoji = {
            SubscriptionStatus.TRIAL: "⏳",
            SubscriptionStatus.ACTIVE: "✅",
            SubscriptionStatus.EXPIRED: "❌",
            SubscriptionStatus.SUSPENDED: "⏸️"
        }
        
        status_text = f"""
📊 **Subscription Status**

**User:** {db_user.first_name} {db_user.last_name or ''}
**Username:** @{db_user.username or 'N/A'}
**Status:** {status_emoji[subscription.status]} {subscription.status.value.title()}
**Join Date:** {db_user.join_date.strftime('%Y-%m-%d')}

**Subscription Details:**
• Start Date: {subscription.start_date.strftime('%Y-%m-%d')}
• End Date: {subscription.end_date.strftime('%Y-%m-%d') if subscription.end_date else 'N/A'}
• Days Remaining: {subscription.days_remaining()}
• Payment Amount: ${subscription.payment_amount}

**Features:**
✅ Real-time signals
✅ Technical analysis
✅ Performance tracking
✅ Custom alerts
        """
        
        await update.message.reply_text(status_text.strip(), parse_mode=ParseMode.MARKDOWN)
    
    async def signals_command(self, update: Update, context: ContextTypes.DEFAULT_TYPE):
        """Handle /signals command"""
        user = update.effective_user
        db_user = User.query.filter_by(telegram_id=str(user.id)).first()
        
        if not db_user:
            await update.message.reply_text("Please use /start first to register.")
            return
        
        subscription = db_user.subscription
        
        if not subscription or not subscription.is_active():
            await update.message.reply_text(
                "Your subscription has expired. Use /subscribe to renew and view signals."
            )
            return
        
        # Get recent signals
        recent_signals = Signal.query.order_by(Signal.timestamp.desc()).limit(5).all()
        
        if not recent_signals:
            await update.message.reply_text("No recent signals available.")
            return
        
        signals_text = "📊 **Recent Trading Signals**\n\n"
        
        for signal in recent_signals:
            outcome_emoji = {
                'win': '✅',
                'loss': '❌',
                'pending': '⏳'
            }
            
            signals_text += f"""
{outcome_emoji[signal.outcome.value]} **{signal.asset_symbol} {signal.signal_type.value.upper()}**
💰 Entry: ${signal.entry_price}
🎯 Target: ${signal.target_price}
🛑 Stop Loss: ${signal.stop_loss}
📅 {signal.timestamp.strftime('%Y-%m-%d %H:%M')}
📝 {signal.content[:100]}...

---
            """
        
        await update.message.reply_text(signals_text.strip(), parse_mode=ParseMode.MARKDOWN)
    
    async def button_callback(self, update: Update, context: ContextTypes.DEFAULT_TYPE):
        """Handle button callbacks"""
        query = update.callback_query
        await query.answer()
        
        if query.data == "subscribe":
            await self.subscribe_command(update, context)
        elif query.data == "help":
            await self.help_command(update, context)
        elif query.data == "view_signals":
            await self.signals_command(update, context)
        elif query.data.startswith("subscribe_"):
            await self.handle_subscription_payment(query, context)
    
    async def handle_subscription_payment(self, query, context):
        """Handle subscription payment callbacks"""
        plan = query.data.replace("subscribe_", "")
        
        plans = {
            "monthly": {"price": 29.99, "days": 30},
            "quarterly": {"price": 79.99, "days": 90},
            "yearly": {"price": 299.99, "days": 365}
        }
        
        if plan not in plans:
            await query.edit_message_text("Invalid plan selected.")
            return
        
        selected_plan = plans[plan]
        
        # For now, simulate successful payment
        # In production, integrate with payment gateway
        user = User.query.filter_by(telegram_id=str(query.from_user.id)).first()
        
        if user:
            subscription = user.subscription
            if not subscription:
                subscription = Subscription(user_id=user.id)
                db.session.add(subscription)
            
            subscription.status = SubscriptionStatus.ACTIVE
            subscription.start_date = datetime.utcnow()
            subscription.end_date = datetime.utcnow() + timedelta(days=selected_plan["days"])
            subscription.payment_amount = selected_plan["price"]
            subscription.payment_method = "manual"
            
            db.session.commit()
            
            success_message = f"""
✅ **Subscription Successful!**

Plan: {plan.title()}
Amount: ${selected_plan['price']}
Duration: {selected_plan['days']} days
Expires: {subscription.end_date.strftime('%Y-%m-%d')}

You now have access to all premium features!
Use /signals to view recent signals.
            """
            
            await query.edit_message_text(success_message.strip(), parse_mode=ParseMode.MARKDOWN)
    
    async def send_signal_to_channel(self, signal_data: dict):
        """Send signal to the channel"""
        if not self.config.TELEGRAM_CHANNEL_ID:
            logger.warning("Channel ID not configured")
            return
        
        signal_type_emoji = {"BUY": "🟢", "SELL": "🔴"}
        
        message = f"""
{signal_type_emoji[signal_data['type']]} **{signal_data['type']} SIGNAL**

**Asset:** {signal_data['symbol']}
**Strategy:** {signal_data['strategy']}
**Price:** ${signal_data['price']:.2f}
**Strength:** {signal_data['strength'].title()}

**Reason:**
{signal_data['reason']}

⏰ {datetime.utcnow().strftime('%Y-%m-%d %H:%M UTC')}
        """
        
        try:
            await self.application.bot.send_message(
                chat_id=self.config.TELEGRAM_CHANNEL_ID,
                text=message.strip(),
                parse_mode=ParseMode.MARKDOWN
            )
        except Exception as e:
            logger.error(f"Error sending signal to channel: {e}")
    
    async def send_signal_to_subscribers(self, signal_data: dict):
        """Send signal to all active subscribers"""
        active_subscriptions = Subscription.query.filter(
            Subscription.status.in_([SubscriptionStatus.TRIAL, SubscriptionStatus.ACTIVE])
        ).all()
        
        signal_type_emoji = {"BUY": "🟢", "SELL": "🔴"}
        
        message = f"""
{signal_type_emoji[signal_data['type']]} **NEW SIGNAL**

**Asset:** {signal_data['symbol']}
**Strategy:** {signal_data['strategy']}
**Price:** ${signal_data['price']:.2f}
**Strength:** {signal_data['strength'].title()}

**Reason:**
{signal_data['reason']}

⏰ {datetime.utcnow().strftime('%Y-%m-%d %H:%M UTC')}
        """
        
        for subscription in active_subscriptions:
            if subscription.is_active():
                try:
                    user = subscription.user
                    await self.application.bot.send_message(
                        chat_id=user.telegram_id,
                        text=message.strip(),
                        parse_mode=ParseMode.MARKDOWN
                    )
                except Exception as e:
                    logger.error(f"Error sending signal to user {user.telegram_id}: {e}")
    
    async def send_notification(self, user_id: str, message: str, notification_type: str = "system"):
        """Send notification to a specific user"""
        try:
            await self.application.bot.send_message(
                chat_id=user_id,
                text=message,
                parse_mode=ParseMode.MARKDOWN
            )
            
            # Store notification in database
            db_user = User.query.filter_by(telegram_id=user_id).first()
            if db_user:
                notification = Notification(
                    user_id=db_user.id,
                    message=message,
                    notification_type=notification_type
                )
                db.session.add(notification)
                db.session.commit()
                
        except Exception as e:
            logger.error(f"Error sending notification to {user_id}: {e}")
    
    async def check_subscription_expiry(self):
        """Check for expiring subscriptions and send warnings"""
        warning_date = datetime.utcnow() + timedelta(days=self.config.SUBSCRIPTION_WARNING_DAYS)
        
        expiring_subscriptions = Subscription.query.filter(
            Subscription.end_date <= warning_date,
            Subscription.status.in_([SubscriptionStatus.TRIAL, SubscriptionStatus.ACTIVE])
        ).all()
        
        for subscription in expiring_subscriptions:
            if subscription.is_active():
                user = subscription.user
                days_left = subscription.days_remaining()
                
                if days_left <= self.config.SUBSCRIPTION_WARNING_DAYS:
                    warning_message = f"""
⚠️ **Subscription Expiring Soon**

Your subscription will expire in {days_left} day(s).

To continue receiving premium signals, please renew your subscription using /subscribe.

Thank you for using our service!
                    """
                    
                    await self.send_notification(
                        user.telegram_id,
                        warning_message.strip(),
                        "subscription"
                    )
    
    async def run_automated_analysis(self):
        """Run automated technical analysis and send signals"""
        try:
            signals = self.analyzer.analyze_all_assets()
            
            for signal_data in signals:
                # Store signal in database
                signal = Signal(
                    asset_symbol=signal_data['symbol'],
                    signal_type=signal_data['type'],
                    entry_price=signal_data['price'],
                    content=signal_data['reason'],
                    strategy_used=signal_data['strategy']
                )
                db.session.add(signal)
                db.session.commit()
                
                # Send to channel and subscribers
                await self.send_signal_to_channel(signal_data)
                await self.send_signal_to_subscribers(signal_data)
                
                # Wait between signals to avoid rate limiting
                await asyncio.sleep(2)
                
        except Exception as e:
            logger.error(f"Error in automated analysis: {e}")
    
    async def stop(self):
        """Stop the bot"""
        if self.application:
            await self.application.updater.stop()
            await self.application.stop()
            await self.application.shutdown() 