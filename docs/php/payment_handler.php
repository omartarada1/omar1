<?php
require_once '../config/database.php';

class PaymentHandler {
    private $db;
    private $settings;

    public function __construct($database) {
        $this->db = $database;
        $this->loadSettings();
    }

    private function loadSettings() {
        $stmt = $this->db->prepare("SELECT setting_key, setting_value FROM settings");
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        $this->settings = [];
        foreach ($results as $row) {
            $this->settings[$row['setting_key']] = $row['setting_value'];
        }
    }

    public function processStripePayment($token, $amount, $email) {
        try {
            // Check if Stripe keys are configured
            if (empty($this->settings['stripe_secret_key'])) {
                throw new Exception('Stripe not configured');
            }

            // Initialize Stripe
            require_once 'vendor/stripe/stripe-php/init.php';
            \Stripe\Stripe::setApiKey($this->settings['stripe_secret_key']);

            // Create charge
            $charge = \Stripe\Charge::create([
                'amount' => $amount * 100, // Convert to cents
                'currency' => 'usd',
                'source' => $token,
                'description' => 'iCloud Unlock Service',
                'receipt_email' => $email
            ]);

            if ($charge->paid) {
                return [
                    'success' => true,
                    'transaction_id' => $charge->id,
                    'amount' => $amount,
                    'currency' => 'USD',
                    'method' => 'stripe'
                ];
            } else {
                throw new Exception('Payment failed');
            }

        } catch (Exception $e) {
            error_log("Stripe payment error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'method' => 'stripe'
            ];
        }
    }

    public function processPayPalPayment($paymentData, $amount) {
        try {
            // Validate PayPal payment data
            if (empty($paymentData['paypal_order_id'])) {
                throw new Exception('Missing PayPal order ID');
            }

            // For enhanced security, you should verify the payment with PayPal API
            // This is a simplified version - implement full PayPal verification
            
            return [
                'success' => true,
                'transaction_id' => $paymentData['paypal_order_id'],
                'amount' => $amount,
                'currency' => 'USD',
                'payer_email' => $paymentData['payer_email'] ?? '',
                'payer_name' => $paymentData['payer_name'] ?? '',
                'method' => 'paypal'
            ];

        } catch (Exception $e) {
            error_log("PayPal payment error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'method' => 'paypal'
            ];
        }
    }

    public function processUSDTPayment($txHash, $amount) {
        try {
            // Validate transaction hash format
            if (!preg_match('/^[a-fA-F0-9]{64}$/', $txHash)) {
                throw new Exception('Invalid transaction hash format');
            }

            // For production, you should verify the transaction on blockchain
            // This is a simplified version - implement blockchain verification
            
            // Check if transaction hash already exists
            $stmt = $this->db->prepare("
                SELECT id FROM unlock_requests 
                WHERE JSON_EXTRACT(payment_data, '$.tx_hash') = ?
            ");
            $stmt->execute([$txHash]);
            
            if ($stmt->fetch()) {
                throw new Exception('Transaction hash already used');
            }

            // In production, verify the transaction on blockchain
            // For now, we'll mark it as pending verification
            return [
                'success' => false, // Set to false until manual verification
                'transaction_id' => $txHash,
                'amount' => $amount,
                'currency' => 'USDT',
                'method' => 'usdt',
                'status' => 'pending_verification'
            ];

        } catch (Exception $e) {
            error_log("USDT payment error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'method' => 'usdt'
            ];
        }
    }

    // Helper method to verify blockchain transaction (implement as needed)
    private function verifyBlockchainTransaction($txHash, $expectedAmount, $network = 'tron') {
        // Implement blockchain verification logic here
        // This would involve calling blockchain API to verify the transaction
        
        // For TRC20 (Tron) transactions, you can use Tron API
        // For ERC20 (Ethereum) transactions, you can use Ethereum API
        
        return false; // Placeholder
    }

    // Update payment status
    public function updatePaymentStatus($requestId, $status, $additionalData = []) {
        try {
            $stmt = $this->db->prepare("
                UPDATE unlock_requests 
                SET payment_status = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            
            $stmt->execute([$status, $requestId]);
            
            return $stmt->rowCount() > 0;
            
        } catch (Exception $e) {
            error_log("Payment status update error: " . $e->getMessage());
            return false;
        }
    }
}
?>