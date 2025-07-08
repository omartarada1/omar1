<?php
require_once '../config/database.php';

// If you prefer to use PHPMailer instead of mail(), uncomment the line below
// require_once 'vendor/phpmailer/phpmailer/src/PHPMailer.php';

class EmailHandler {
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

    public function sendCustomerConfirmation($data) {
        try {
            $to = $data['email'];
            $subject = "iCloud Unlock Request Confirmation - Order #" . $data['request_id'];
            
            $message = $this->getCustomerEmailTemplate($data);
            $headers = $this->getEmailHeaders();

            // Send email
            $sent = mail($to, $subject, $message, $headers);
            
            if (!$sent) {
                error_log("Failed to send customer confirmation email to: " . $to);
            }
            
            return $sent;

        } catch (Exception $e) {
            error_log("Customer email error: " . $e->getMessage());
            return false;
        }
    }

    public function sendAdminNotification($data) {
        try {
            $to = $this->settings['admin_email'] ?? 'admin@icloudunlockpro.com';
            $subject = "New iCloud Unlock Request - Order #" . $data['request_id'];
            
            $message = $this->getAdminEmailTemplate($data);
            $headers = $this->getEmailHeaders();

            // Send email
            $sent = mail($to, $subject, $message, $headers);
            
            if (!$sent) {
                error_log("Failed to send admin notification email to: " . $to);
            }
            
            return $sent;

        } catch (Exception $e) {
            error_log("Admin email error: " . $e->getMessage());
            return false;
        }
    }

    private function getEmailHeaders() {
        $site_name = $this->settings['site_name'] ?? 'iCloud Unlock Pro';
        $admin_email = $this->settings['admin_email'] ?? 'admin@icloudunlockpro.com';
        
        return "MIME-Version: 1.0\r\n" .
               "Content-Type: text/html; charset=UTF-8\r\n" .
               "From: {$site_name} <{$admin_email}>\r\n" .
               "Reply-To: {$admin_email}\r\n" .
               "X-Mailer: PHP/" . phpversion();
    }

    private function getCustomerEmailTemplate($data) {
        $device_type_display = ucfirst($data['device_type']);
        $payment_status_display = ucfirst(str_replace('_', ' ', $data['payment_status']));
        $amount_formatted = number_format($data['amount'], 2);
        
        $payment_method_display = [
            'card' => 'Credit Card',
            'paypal' => 'PayPal',
            'usdt' => 'USDT (Cryptocurrency)'
        ][$data['payment_method']] ?? $data['payment_method'];

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Order Confirmation</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #007aff; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px; background: #f9f9f9; }
                .order-details { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
                .status-paid { color: #28a745; font-weight: bold; }
                .status-pending { color: #ffc107; font-weight: bold; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔓 iCloud Unlock Pro</h1>
                    <p>Order Confirmation</p>
                </div>
                
                <div class='content'>
                    <h2>Thank you for your order!</h2>
                    <p>We have received your iCloud unlock request and will begin processing it immediately.</p>
                    
                    <div class='order-details'>
                        <h3>Order Details</h3>
                        <div class='detail-row'>
                            <span><strong>Order ID:</strong></span>
                            <span>#{$data['request_id']}</span>
                        </div>
                        <div class='detail-row'>
                            <span><strong>Device Type:</strong></span>
                            <span>{$device_type_display}</span>
                        </div>
                        <div class='detail-row'>
                            <span><strong>IMEI/Serial:</strong></span>
                            <span>{$data['imei_serial']}</span>
                        </div>
                        <div class='detail-row'>
                            <span><strong>Amount:</strong></span>
                            <span>\${$amount_formatted} USD</span>
                        </div>
                        <div class='detail-row'>
                            <span><strong>Payment Method:</strong></span>
                            <span>{$payment_method_display}</span>
                        </div>
                        <div class='detail-row'>
                            <span><strong>Payment Status:</strong></span>
                            <span class='status-{$data['payment_status']}'>{$payment_status_display}</span>
                        </div>
                    </div>
                    
                    <h3>What happens next?</h3>
                    <ol>
                        <li>Our team will verify your payment (if applicable)</li>
                        <li>We will begin processing your device unlock within 24 hours</li>
                        <li>You will receive detailed instructions via email once complete</li>
                        <li>The process typically takes 24-72 hours depending on device type</li>
                    </ol>
                    
                    <p><strong>Need help?</strong> Contact our support team:</p>
                    <ul>
                        <li>📧 Email: support@icloudunlockpro.com</li>
                        <li>📱 WhatsApp: {$this->settings['whatsapp_number']}</li>
                    </ul>
                </div>
                
                <div class='footer'>
                    <p>© 2024 iCloud Unlock Pro. All rights reserved.</p>
                    <p>This is an automated email. Please do not reply to this message.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    private function getAdminEmailTemplate($data) {
        $device_type_display = ucfirst($data['device_type']);
        $payment_status_display = ucfirst(str_replace('_', ' ', $data['payment_status']));
        $amount_formatted = number_format($data['amount'], 2);
        
        $payment_method_display = [
            'card' => 'Credit Card',
            'paypal' => 'PayPal',
            'usdt' => 'USDT (Cryptocurrency)'
        ][$data['payment_method']] ?? $data['payment_method'];

        $payment_info = '';
        if (!empty($data['payment_data'])) {
            $payment_info = "<pre>" . json_encode($data['payment_data'], JSON_PRETTY_PRINT) . "</pre>";
        }

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>New Unlock Request</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 700px; margin: 0 auto; padding: 20px; }
                .header { background: #dc3545; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px; background: #f9f9f9; }
                .request-details { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
                .payment-info { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .urgent { color: #dc3545; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🚨 New Unlock Request</h1>
                    <p>Admin Notification</p>
                </div>
                
                <div class='content'>
                    <h2>New iCloud unlock request received</h2>
                    <p class='urgent'>Action required: Process this request immediately</p>
                    
                    <div class='request-details'>
                        <h3>Request Details</h3>
                        <div class='detail-row'>
                            <span><strong>Request ID:</strong></span>
                            <span>#{$data['request_id']}</span>
                        </div>
                        <div class='detail-row'>
                            <span><strong>Customer Email:</strong></span>
                            <span>{$data['customer_email']}</span>
                        </div>
                        <div class='detail-row'>
                            <span><strong>Device Type:</strong></span>
                            <span>{$device_type_display}</span>
                        </div>
                        <div class='detail-row'>
                            <span><strong>IMEI/Serial:</strong></span>
                            <span>{$data['imei_serial']}</span>
                        </div>
                        <div class='detail-row'>
                            <span><strong>Description:</strong></span>
                            <span>" . ($data['description'] ?: 'None provided') . "</span>
                        </div>
                        <div class='detail-row'>
                            <span><strong>Amount:</strong></span>
                            <span>\${$amount_formatted} USD</span>
                        </div>
                        <div class='detail-row'>
                            <span><strong>Payment Method:</strong></span>
                            <span>{$payment_method_display}</span>
                        </div>
                        <div class='detail-row'>
                            <span><strong>Payment Status:</strong></span>
                            <span>{$payment_status_display}</span>
                        </div>
                        <div class='detail-row'>
                            <span><strong>Received:</strong></span>
                            <span>" . date('Y-m-d H:i:s') . "</span>
                        </div>
                    </div>
                    
                    " . ($payment_info ? "<div class='payment-info'><h4>Payment Information:</h4>{$payment_info}</div>" : "") . "
                    
                    <h3>Next Steps:</h3>
                    <ol>
                        <li>Log into admin panel to view full details</li>
                        <li>Verify payment if applicable</li>
                        <li>Begin unlock process</li>
                        <li>Update request status as needed</li>
                    </ol>
                    
                    <p><a href='admin/' style='background: #007aff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Access Admin Panel</a></p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    // Send custom email
    public function sendCustomEmail($to, $subject, $message) {
        try {
            $headers = $this->getEmailHeaders();
            return mail($to, $subject, $message, $headers);
        } catch (Exception $e) {
            error_log("Custom email error: " . $e->getMessage());
            return false;
        }
    }
}
?>