import yfinance as yf
import pandas as pd
import numpy as np
import ta
from datetime import datetime, timedelta
from typing import Dict, List, Tuple, Optional
import logging

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

class TechnicalAnalyzer:
    def __init__(self, config):
        self.config = config
        self.rsi_period = config.RSI_PERIOD
        self.rsi_overbought = config.RSI_OVERBOUGHT
        self.rsi_oversold = config.RSI_OVERSOLD
        self.macd_fast = config.MACD_FAST
        self.macd_slow = config.MACD_SLOW
        self.macd_signal = config.MACD_SIGNAL
        
    def get_market_data(self, symbol: str, period: str = "1mo") -> pd.DataFrame:
        """Fetch market data for a given symbol"""
        try:
            ticker = yf.Ticker(symbol)
            data = ticker.history(period=period)
            if data.empty:
                logger.warning(f"No data found for {symbol}")
                return pd.DataFrame()
            return data
        except Exception as e:
            logger.error(f"Error fetching data for {symbol}: {e}")
            return pd.DataFrame()
    
    def calculate_rsi(self, data: pd.DataFrame, period: int = None) -> pd.Series:
        """Calculate RSI indicator"""
        if period is None:
            period = self.rsi_period
        return ta.momentum.RSIIndicator(data['Close'], window=period).rsi()
    
    def calculate_macd(self, data: pd.DataFrame) -> Tuple[pd.Series, pd.Series, pd.Series]:
        """Calculate MACD indicator"""
        macd = ta.trend.MACD(
            data['Close'], 
            window_fast=self.macd_fast, 
            window_slow=self.macd_slow, 
            window_sign=self.macd_signal
        )
        return macd.macd(), macd.macd_signal(), macd.macd_diff()
    
    def calculate_bollinger_bands(self, data: pd.DataFrame, period: int = 20) -> Tuple[pd.Series, pd.Series, pd.Series]:
        """Calculate Bollinger Bands"""
        bb = ta.volatility.BollingerBands(data['Close'], window=period)
        return bb.bollinger_hband(), bb.bollinger_lband(), bb.bollinger_mavg()
    
    def calculate_ema(self, data: pd.DataFrame, period: int = 20) -> pd.Series:
        """Calculate Exponential Moving Average"""
        return ta.trend.EMAIndicator(data['Close'], window=period).ema_indicator()
    
    def analyze_asset(self, symbol: str) -> Dict:
        """Complete technical analysis of an asset"""
        data = self.get_market_data(symbol)
        if data.empty:
            return {}
        
        # Calculate indicators
        rsi = self.calculate_rsi(data)
        macd, macd_signal, macd_diff = self.calculate_macd(data)
        bb_upper, bb_lower, bb_middle = self.calculate_bollinger_bands(data)
        ema_20 = self.calculate_ema(data, 20)
        ema_50 = self.calculate_ema(data, 50)
        
        # Get latest values
        latest = {
            'symbol': symbol,
            'close': data['Close'].iloc[-1],
            'volume': data['Volume'].iloc[-1],
            'rsi': rsi.iloc[-1] if not pd.isna(rsi.iloc[-1]) else None,
            'macd': macd.iloc[-1] if not pd.isna(macd.iloc[-1]) else None,
            'macd_signal': macd_signal.iloc[-1] if not pd.isna(macd_signal.iloc[-1]) else None,
            'macd_diff': macd_diff.iloc[-1] if not pd.isna(macd_diff.iloc[-1]) else None,
            'bb_upper': bb_upper.iloc[-1] if not pd.isna(bb_upper.iloc[-1]) else None,
            'bb_lower': bb_lower.iloc[-1] if not pd.isna(bb_lower.iloc[-1]) else None,
            'bb_middle': bb_middle.iloc[-1] if not pd.isna(bb_middle.iloc[-1]) else None,
            'ema_20': ema_20.iloc[-1] if not pd.isna(ema_20.iloc[-1]) else None,
            'ema_50': ema_50.iloc[-1] if not pd.isna(ema_50.iloc[-1]) else None,
            'timestamp': datetime.utcnow()
        }
        
        return latest
    
    def generate_signals(self, symbol: str) -> List[Dict]:
        """Generate trading signals based on technical analysis"""
        analysis = self.analyze_asset(symbol)
        if not analysis:
            return []
        
        signals = []
        
        # RSI Signals
        if analysis['rsi'] is not None:
            if analysis['rsi'] < self.rsi_oversold:
                signals.append({
                    'type': 'BUY',
                    'strategy': 'RSI Oversold',
                    'reason': f'RSI ({analysis["rsi"]:.2f}) below oversold threshold ({self.rsi_oversold})',
                    'strength': 'strong' if analysis['rsi'] < 20 else 'medium',
                    'price': analysis['close']
                })
            elif analysis['rsi'] > self.rsi_overbought:
                signals.append({
                    'type': 'SELL',
                    'strategy': 'RSI Overbought',
                    'reason': f'RSI ({analysis["rsi"]:.2f}) above overbought threshold ({self.rsi_overbought})',
                    'strength': 'strong' if analysis['rsi'] > 80 else 'medium',
                    'price': analysis['close']
                })
        
        # MACD Signals
        if analysis['macd'] is not None and analysis['macd_signal'] is not None:
            if analysis['macd'] > analysis['macd_signal'] and analysis['macd_diff'] > 0:
                signals.append({
                    'type': 'BUY',
                    'strategy': 'MACD Bullish',
                    'reason': f'MACD ({analysis["macd"]:.4f}) above signal line ({analysis["macd_signal"]:.4f})',
                    'strength': 'medium',
                    'price': analysis['close']
                })
            elif analysis['macd'] < analysis['macd_signal'] and analysis['macd_diff'] < 0:
                signals.append({
                    'type': 'SELL',
                    'strategy': 'MACD Bearish',
                    'reason': f'MACD ({analysis["macd"]:.4f}) below signal line ({analysis["macd_signal"]:.4f})',
                    'strength': 'medium',
                    'price': analysis['close']
                })
        
        # Bollinger Bands Signals
        if analysis['bb_upper'] is not None and analysis['bb_lower'] is not None:
            if analysis['close'] <= analysis['bb_lower']:
                signals.append({
                    'type': 'BUY',
                    'strategy': 'Bollinger Bands',
                    'reason': f'Price ({analysis["close"]:.2f}) at or below lower Bollinger Band ({analysis["bb_lower"]:.2f})',
                    'strength': 'medium',
                    'price': analysis['close']
                })
            elif analysis['close'] >= analysis['bb_upper']:
                signals.append({
                    'type': 'SELL',
                    'strategy': 'Bollinger Bands',
                    'reason': f'Price ({analysis["close"]:.2f}) at or above upper Bollinger Band ({analysis["bb_upper"]:.2f})',
                    'strength': 'medium',
                    'price': analysis['close']
                })
        
        # EMA Crossover Signals
        if analysis['ema_20'] is not None and analysis['ema_50'] is not None:
            if analysis['ema_20'] > analysis['ema_50']:
                signals.append({
                    'type': 'BUY',
                    'strategy': 'EMA Crossover',
                    'reason': f'EMA 20 ({analysis["ema_20"]:.2f}) above EMA 50 ({analysis["ema_50"]:.2f})',
                    'strength': 'weak',
                    'price': analysis['close']
                })
            elif analysis['ema_20'] < analysis['ema_50']:
                signals.append({
                    'type': 'SELL',
                    'strategy': 'EMA Crossover',
                    'reason': f'EMA 20 ({analysis["ema_20"]:.2f}) below EMA 50 ({analysis["ema_50"]:.2f})',
                    'strength': 'weak',
                    'price': analysis['close']
                })
        
        return signals
    
    def get_strongest_signal(self, symbol: str) -> Optional[Dict]:
        """Get the strongest signal for an asset"""
        signals = self.generate_signals(symbol)
        if not signals:
            return None
        
        # Prioritize by strength: strong > medium > weak
        strength_order = {'strong': 3, 'medium': 2, 'weak': 1}
        strongest = max(signals, key=lambda x: strength_order.get(x['strength'], 0))
        
        return strongest
    
    def analyze_all_assets(self) -> List[Dict]:
        """Analyze all monitored assets and return signals"""
        all_signals = []
        
        for symbol in self.config.MONITORED_ASSETS:
            try:
                strongest_signal = self.get_strongest_signal(symbol)
                if strongest_signal:
                    strongest_signal['symbol'] = symbol
                    all_signals.append(strongest_signal)
            except Exception as e:
                logger.error(f"Error analyzing {symbol}: {e}")
                continue
        
        return all_signals 