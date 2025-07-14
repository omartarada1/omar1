from flask import Flask, render_template, request, redirect, url_for, flash, jsonify
from flask_login import LoginManager, login_user, logout_user, login_required, current_user
from werkzeug.security import generate_password_hash, check_password_hash
import plotly.graph_objs as go
import plotly.utils
import json
from datetime import datetime, timedelta
import asyncio
import threading
import schedule
import time

from models import db, User, Subscription, Signal, Performance, Notification, Strategy, SubscriptionStatus, SignalType, TradeOutcome
from config import Config
from telegram_bot import TradingBot
from technical_analysis import TechnicalAnalyzer

app = Flask(__name__)
app.config.from_object(Config)
app.config['SQLALCHEMY_DATABASE_URI'] = Config.DATABASE_URL
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False

db.init_app(app)

# Flask-Login setup
login_manager = LoginManager()
login_manager.init_app(app)
login_manager.login_view = 'login'

# Global bot instance
bot = None
analyzer = TechnicalAnalyzer(Config)

@login_manager.user_loader
def load_user(user_id):
    return User.query.get(int(user_id))

def create_admin_user():
    """Create admin user if it doesn't exist"""
    admin = User.query.filter_by(is_admin=True).first()
    if not admin:
        admin = User(
            telegram_id='admin',
            username='admin',
            first_name='Admin',
            last_name='User',
            is_admin=True
        )
        db.session.add(admin)
        db.session.commit()

def init_database():
    """Initialize database and create tables"""
    with app.app_context():
        db.create_all()
        create_admin_user()

def start_bot():
    """Start the Telegram bot in a separate thread"""
    global bot
    bot = TradingBot(Config)
    
    def run_bot():
        loop = asyncio.new_event_loop()
        asyncio.set_event_loop(loop)
        loop.run_until_complete(bot.start())
    
    bot_thread = threading.Thread(target=run_bot, daemon=True)
    bot_thread.start()

def run_scheduled_tasks():
    """Run scheduled tasks for automated analysis and notifications"""
    def check_expiry():
        if bot:
            asyncio.run(bot.check_subscription_expiry())
    
    def run_analysis():
        if bot:
            asyncio.run(bot.run_automated_analysis())
    
    # Schedule tasks
    schedule.every().hour.do(run_analysis)
    schedule.every().day.at("09:00").do(check_expiry)
    
    while True:
        schedule.run_pending()
        time.sleep(60)

# Routes
@app.route('/')
def index():
    return redirect(url_for('login'))

@app.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        username = request.form['username']
        password = request.form['password']
        
        # For demo purposes, use simple admin credentials
        if username == 'admin' and password == 'admin123':
            admin = User.query.filter_by(is_admin=True).first()
            login_user(admin)
            return redirect(url_for('dashboard'))
        else:
            flash('Invalid credentials', 'error')
    
    return render_template('login.html')

@app.route('/logout')
@login_required
def logout():
    logout_user()
    return redirect(url_for('login'))

@app.route('/dashboard')
@login_required
def dashboard():
    # Get statistics
    total_users = User.query.count()
    active_subscriptions = Subscription.query.filter(
        Subscription.status.in_([SubscriptionStatus.TRIAL, SubscriptionStatus.ACTIVE])
    ).count()
    total_signals = Signal.query.count()
    
    # Get recent performance
    recent_performance = Performance.query.order_by(Performance.date.desc()).limit(7).all()
    
    # Create performance chart
    dates = [p.date.strftime('%Y-%m-%d') for p in recent_performance]
    success_rates = [p.success_rate for p in recent_performance]
    
    performance_chart = go.Figure(data=[
        go.Scatter(x=dates, y=success_rates, mode='lines+markers', name='Success Rate')
    ])
    performance_chart.update_layout(
        title='Success Rate (Last 7 Days)',
        xaxis_title='Date',
        yaxis_title='Success Rate (%)'
    )
    
    return render_template('dashboard.html',
                         total_users=total_users,
                         active_subscriptions=active_subscriptions,
                         total_signals=total_signals,
                         performance_chart=json.dumps(performance_chart, cls=plotly.utils.PlotlyJSONEncoder))

@app.route('/signals')
@login_required
def signals():
    page = request.args.get('page', 1, type=int)
    signals = Signal.query.order_by(Signal.timestamp.desc()).paginate(
        page=page, per_page=20, error_out=False
    )
    return render_template('signals.html', signals=signals)

@app.route('/signals/new', methods=['GET', 'POST'])
@login_required
def new_signal():
    if request.method == 'POST':
        signal = Signal(
            asset_symbol=request.form['asset_symbol'],
            signal_type=SignalType(request.form['signal_type']),
            entry_price=float(request.form['entry_price']),
            target_price=float(request.form['target_price']),
            stop_loss=float(request.form['stop_loss']),
            content=request.form['content'],
            strategy_used=request.form['strategy_used']
        )
        db.session.add(signal)
        db.session.commit()
        
        # Send signal via bot if available
        if bot:
            signal_data = {
                'symbol': signal.asset_symbol,
                'type': signal.signal_type.value.upper(),
                'price': signal.entry_price,
                'strategy': signal.strategy_used,
                'reason': signal.content,
                'strength': 'medium'
            }
            asyncio.run(bot.send_signal_to_channel(signal_data))
            asyncio.run(bot.send_signal_to_subscribers(signal_data))
        
        flash('Signal created successfully!', 'success')
        return redirect(url_for('signals'))
    
    return render_template('new_signal.html')

@app.route('/signals/<int:signal_id>/edit', methods=['GET', 'POST'])
@login_required
def edit_signal(signal_id):
    signal = Signal.query.get_or_404(signal_id)
    
    if request.method == 'POST':
        signal.asset_symbol = request.form['asset_symbol']
        signal.signal_type = SignalType(request.form['signal_type'])
        signal.entry_price = float(request.form['entry_price'])
        signal.target_price = float(request.form['target_price'])
        signal.stop_loss = float(request.form['stop_loss'])
        signal.content = request.form['content']
        signal.strategy_used = request.form['strategy_used']
        
        db.session.commit()
        flash('Signal updated successfully!', 'success')
        return redirect(url_for('signals'))
    
    return render_template('edit_signal.html', signal=signal)

@app.route('/signals/<int:signal_id>/delete', methods=['POST'])
@login_required
def delete_signal(signal_id):
    signal = Signal.query.get_or_404(signal_id)
    db.session.delete(signal)
    db.session.commit()
    flash('Signal deleted successfully!', 'success')
    return redirect(url_for('signals'))

@app.route('/subscribers')
@login_required
def subscribers():
    page = request.args.get('page', 1, type=int)
    search = request.args.get('search', '')
    
    query = User.query
    if search:
        query = query.filter(
            (User.username.contains(search)) |
            (User.first_name.contains(search)) |
            (User.last_name.contains(search))
        )
    
    users = query.order_by(User.join_date.desc()).paginate(
        page=page, per_page=20, error_out=False
    )
    
    return render_template('subscribers.html', users=users, search=search)

@app.route('/subscribers/<int:user_id>')
@login_required
def subscriber_detail(user_id):
    user = User.query.get_or_404(user_id)
    return render_template('subscriber_detail.html', user=user)

@app.route('/subscribers/<int:user_id>/extend', methods=['POST'])
@login_required
def extend_subscription(user_id):
    user = User.query.get_or_404(user_id)
    days = int(request.form['days'])
    
    if not user.subscription:
        user.subscription = Subscription(user_id=user.id)
        db.session.add(user.subscription)
    
    if user.subscription.end_date and user.subscription.end_date > datetime.utcnow():
        user.subscription.end_date += timedelta(days=days)
    else:
        user.subscription.end_date = datetime.utcnow() + timedelta(days=days)
    
    user.subscription.status = SubscriptionStatus.ACTIVE
    db.session.commit()
    
    flash(f'Subscription extended by {days} days', 'success')
    return redirect(url_for('subscriber_detail', user_id=user_id))

@app.route('/analytics')
@login_required
def analytics():
    # Get performance data
    performance_data = Performance.query.order_by(Performance.date.desc()).limit(30).all()
    
    # Create charts
    dates = [p.date.strftime('%Y-%m-%d') for p in performance_data]
    success_rates = [p.success_rate for p in performance_data]
    total_signals = [p.total_signals for p in performance_data]
    winning_signals = [p.winning_signals for p in performance_data]
    
    # Success rate chart
    success_chart = go.Figure(data=[
        go.Scatter(x=dates, y=success_rates, mode='lines+markers', name='Success Rate')
    ])
    success_chart.update_layout(
        title='Success Rate Over Time',
        xaxis_title='Date',
        yaxis_title='Success Rate (%)'
    )
    
    # Signals chart
    signals_chart = go.Figure(data=[
        go.Bar(x=dates, y=total_signals, name='Total Signals'),
        go.Bar(x=dates, y=winning_signals, name='Winning Signals')
    ])
    signals_chart.update_layout(
        title='Signals Performance',
        xaxis_title='Date',
        yaxis_title='Number of Signals',
        barmode='group'
    )
    
    return render_template('analytics.html',
                         success_chart=json.dumps(success_chart, cls=plotly.utils.PlotlyJSONEncoder),
                         signals_chart=json.dumps(signals_chart, cls=plotly.utils.PlotlyJSONEncoder))

@app.route('/settings')
@login_required
def settings():
    return render_template('settings.html')

@app.route('/broadcast', methods=['GET', 'POST'])
@login_required
def broadcast():
    if request.method == 'POST':
        message = request.form['message']
        
        # Send to all active subscribers
        active_subscriptions = Subscription.query.filter(
            Subscription.status.in_([SubscriptionStatus.TRIAL, SubscriptionStatus.ACTIVE])
        ).all()
        
        sent_count = 0
        for subscription in active_subscriptions:
            if subscription.is_active():
                user = subscription.user
                try:
                    if bot:
                        asyncio.run(bot.send_notification(
                            user.telegram_id,
                            f"📢 **Broadcast Message**\n\n{message}",
                            "broadcast"
                        ))
                        sent_count += 1
                except Exception as e:
                    print(f"Error sending to {user.telegram_id}: {e}")
        
        flash(f'Message sent to {sent_count} subscribers', 'success')
        return redirect(url_for('broadcast'))
    
    return render_template('broadcast.html')

@app.route('/api/run-analysis')
@login_required
def run_analysis():
    """API endpoint to manually trigger analysis"""
    try:
        if bot:
            asyncio.run(bot.run_automated_analysis())
        flash('Analysis completed successfully!', 'success')
    except Exception as e:
        flash(f'Error running analysis: {e}', 'error')
    
    return redirect(url_for('dashboard'))

if __name__ == '__main__':
    init_database()
    start_bot()
    
    # Start scheduled tasks in background
    task_thread = threading.Thread(target=run_scheduled_tasks, daemon=True)
    task_thread.start()
    
    app.run(debug=True, host='0.0.0.0', port=5000) 