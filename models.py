from datetime import datetime, timedelta
from flask_sqlalchemy import SQLAlchemy
from flask_login import UserMixin
from sqlalchemy import Enum
import enum

db = SQLAlchemy()

class SubscriptionStatus(enum.Enum):
    TRIAL = "trial"
    ACTIVE = "active"
    EXPIRED = "expired"
    SUSPENDED = "suspended"

class SignalType(enum.Enum):
    BUY = "buy"
    SELL = "sell"

class TradeOutcome(enum.Enum):
    WIN = "win"
    LOSS = "loss"
    PENDING = "pending"

class User(UserMixin, db.Model):
    __tablename__ = 'users'
    
    id = db.Column(db.Integer, primary_key=True)
    telegram_id = db.Column(db.String(50), unique=True, nullable=False)
    username = db.Column(db.String(100))
    first_name = db.Column(db.String(100))
    last_name = db.Column(db.String(100))
    join_date = db.Column(db.DateTime, default=datetime.utcnow)
    is_admin = db.Column(db.Boolean, default=False)
    
    # Relationship
    subscription = db.relationship('Subscription', backref='user', uselist=False)
    
    def __repr__(self):
        return f'<User {self.username}>'

class Subscription(db.Model):
    __tablename__ = 'subscriptions'
    
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('users.id'), nullable=False)
    status = db.Column(Enum(SubscriptionStatus), default=SubscriptionStatus.TRIAL)
    start_date = db.Column(db.DateTime, default=datetime.utcnow)
    end_date = db.Column(db.DateTime)
    payment_amount = db.Column(db.Float, default=0.0)
    payment_method = db.Column(db.String(50))
    
    def is_active(self):
        if self.status == SubscriptionStatus.EXPIRED or self.status == SubscriptionStatus.SUSPENDED:
            return False
        if self.end_date and datetime.utcnow() > self.end_date:
            self.status = SubscriptionStatus.EXPIRED
            db.session.commit()
            return False
        return True
    
    def days_remaining(self):
        if not self.end_date:
            return 0
        remaining = self.end_date - datetime.utcnow()
        return max(0, remaining.days)
    
    def __repr__(self):
        return f'<Subscription {self.user_id} - {self.status.value}>'

class Signal(db.Model):
    __tablename__ = 'signals'
    
    id = db.Column(db.Integer, primary_key=True)
    asset_symbol = db.Column(db.String(20), nullable=False)
    signal_type = db.Column(Enum(SignalType), nullable=False)
    entry_price = db.Column(db.Float)
    target_price = db.Column(db.Float)
    stop_loss = db.Column(db.Float)
    content = db.Column(db.Text, nullable=False)
    strategy_used = db.Column(db.String(100))
    timestamp = db.Column(db.DateTime, default=datetime.utcnow)
    outcome = db.Column(Enum(TradeOutcome), default=TradeOutcome.PENDING)
    profit_loss = db.Column(db.Float)
    closed_at = db.Column(db.DateTime)
    
    def __repr__(self):
        return f'<Signal {self.asset_symbol} {self.signal_type.value}>'

class Performance(db.Model):
    __tablename__ = 'performance'
    
    id = db.Column(db.Integer, primary_key=True)
    date = db.Column(db.Date, default=datetime.utcnow().date)
    total_signals = db.Column(db.Integer, default=0)
    winning_signals = db.Column(db.Integer, default=0)
    losing_signals = db.Column(db.Integer, default=0)
    total_profit = db.Column(db.Float, default=0.0)
    success_rate = db.Column(db.Float, default=0.0)
    
    def calculate_success_rate(self):
        if self.total_signals > 0:
            self.success_rate = (self.winning_signals / self.total_signals) * 100
        return self.success_rate
    
    def __repr__(self):
        return f'<Performance {self.date} - {self.success_rate}%>'

class Notification(db.Model):
    __tablename__ = 'notifications'
    
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('users.id'))
    message = db.Column(db.Text, nullable=False)
    notification_type = db.Column(db.String(50))  # subscription, signal, system
    sent_at = db.Column(db.DateTime, default=datetime.utcnow)
    is_read = db.Column(db.Boolean, default=False)
    
    def __repr__(self):
        return f'<Notification {self.notification_type}>'

class Strategy(db.Model):
    __tablename__ = 'strategies'
    
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(100), nullable=False)
    description = db.Column(db.Text)
    is_active = db.Column(db.Boolean, default=True)
    parameters = db.Column(db.JSON)  # Store strategy parameters as JSON
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    def __repr__(self):
        return f'<Strategy {self.name}>' 