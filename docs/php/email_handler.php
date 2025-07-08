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
            $subject = "Fix Smart - Service Request Confirmation - Order #" . $data['request_id'];
            
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
            $to = $this->settings['admin_email'] ?? 'admin@fixsmart.com';
            $subject = "New Fix Smart Service Request - Order #" . $data['request_id'];
            
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
        $site_name = $this->settings['site_name'] ?? 'Fix Smart';
        $admin_email = $this->settings['admin_email'] ?? 'admin@fixsmart.com';
        
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
                    <h1>🔓 Fix Smart</h1>
                    <p>Order Confirmation</p>
                </div>
                
                <div class='content'>
                    <h2>Thank you for your order!</h2>
                    <p><strong>Your unlock request has been received. We are currently working on unlocking your device. Thank you for choosing Fix Smart.</strong></p>
                    <p>Your payment has been confirmed and we will begin processing your device unlock immediately.</p>
                    
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
                        <li>📧 Email: support@fixsmart.com</li>
                        <li>📱 WhatsApp: {$this->settings['whatsapp_number']}</li>
                    </ul>
                </div>
                
                <div class='footer'>
                    <p>© 2024 Fix Smart. All rights reserved.</p>
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

    // Send service request confirmation to customer
    public function sendServiceRequestConfirmation($data) {
        try {
            $to = $data['email'];
            $subject = "Service Request Confirmation - Fix Smart";
            
            $message = $this->getServiceRequestCustomerTemplate($data);
            $headers = $this->getEmailHeaders();

            return mail($to, $subject, $message, $headers);
        } catch (Exception $e) {
            error_log("Service request customer email error: " . $e->getMessage());
            return false;
        }
    }

    // Send service request notification to admin
    public function sendServiceRequestNotification($data) {
        try {
            $to = $this->settings['admin_email'] ?? 'admin@fixsmart.com';
            $subject = "New Service Request - Order #{$data['request_id']}";
            
            $message = $this->getServiceRequestAdminTemplate($data);
            $headers = $this->getEmailHeaders();

            return mail($to, $subject, $message, $headers);
        } catch (Exception $e) {
            error_log("Service request admin email error: " . $e->getMessage());
            return false;
        }
    }

    private function getServiceRequestCustomerTemplate($data) {
        $amount_formatted = number_format($data['amount'], 2);
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Service Request Confirmation</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background: #f8faff; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 8px 30px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #007aff, #0056b3); color: white; padding: 2rem; text-align: center; }
                .header h1 { margin: 0; font-size: 1.8rem; }
                .content { padding: 2rem; }
                .order-details { background: #f8faff; padding: 1.5rem; border-radius: 12px; margin: 1.5rem 0; }
                .detail-row { display: flex; justify-content: space-between; margin-bottom: 0.75rem; }
                .detail-row:last-child { margin-bottom: 0; font-weight: bold; border-top: 2px solid #ddd; padding-top: 0.75rem; }
                .success-icon { font-size: 3rem; color: #28a745; text-align: center; margin: 1rem 0; }
                .steps { margin: 2rem 0; }
                .step { display: flex; align-items: center; margin-bottom: 1rem; }
                .step-number { background: #007aff; color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 1rem; font-weight: bold; }
                .cta-button { display: inline-block; background: #25d366; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; margin: 1rem 0; }
                .footer { background: #f8f9fa; padding: 1.5rem; text-align: center; font-size: 0.9rem; color: #666; }
                .hash-display { font-family: monospace; word-break: break-all; background: #f5f5f5; padding: 0.5rem; border-radius: 4px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>✅ Request Confirmed!</h1>
                    <p>Thank you for choosing Fix Smart</p>
                </div>
                
                <div class='content'>
                    <div class='success-icon'>🎉</div>
                    
                    <h2>Your unlock request has been received</h2>
                    <p>Hi there! We've successfully received your device unlock request and our expert team is ready to get started.</p>
                    
                    <div class='order-details'>
                        <h3>Order Details</h3>
                        <div class='detail-row'>
                            <span>Order ID:</span>
                            <span>#{$data['request_id']}</span>
                        </div>
                        <div class='detail-row'>
                            <span>Device:</span>
                            <span>" . ucfirst($data['device_type']) . "</span>
                        </div>
                        <div class='detail-row'>
                            <span>Model:</span>
                            <span>{$data['device_version']}</span>
                        </div>
                        <div class='detail-row'>
                            <span>IMEI/Serial:</span>
                            <span>{$data['imei_serial']}</span>
                        </div>
                        <div class='detail-row'>
                            <span>Payment Method:</span>
                            <span>USDT Cryptocurrency</span>
                        </div>
                        <div class='detail-row'>
                            <span>Transaction Hash:</span>
                            <div class='hash-display'>{$data['transaction_hash']}</div>
                        </div>
                        <div class='detail-row'>
                            <span>Total Amount:</span>
                            <span>\${$amount_formatted}</span>
                        </div>
                    </div>
                    
                    <div class='steps'>
                        <h3>What happens next?</h3>
                        <div class='step'>
                            <div class='step-number'>1</div>
                            <div>
                                <strong>Payment Verification</strong><br>
                                We'll verify your USDT transaction within 1-2 hours.
                            </div>
                        </div>
                        <div class='step'>
                            <div class='step-number'>2</div>
                            <div>
                                <strong>Processing Begins</strong><br>
                                Our experts start working on your device unlock immediately.
                            </div>
                        </div>
                        <div class='step'>
                            <div class='step-number'>3</div>
                            <div>
                                <strong>Updates & Completion</strong><br>
                                You'll receive email updates and detailed unlock instructions (24-72 hours).
                            </div>
                        </div>
                    </div>
                    
                    <p><strong>Need help?</strong> If you have any questions about your order, don't hesitate to contact our support team.</p>
                    
                    <a href='https://wa.me/15551234567' class='cta-button'>💬 WhatsApp Support</a>
                </div>
                
                <div class='footer'>
                    <p>This is an automated confirmation email from Fix Smart.<br>
                    Please keep this email for your records.</p>
                    <p>© 2024 Fix Smart. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    private function getServiceRequestAdminTemplate($data) {
        $amount_formatted = number_format($data['amount'], 2);
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>New Service Request</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; background: #f5f5f5; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
                .header { background: #dc3545; color: white; padding: 1.5rem; text-align: center; }
                .content { padding: 2rem; }
                .alert { background: #fff3cd; border: 1px solid #ffeaa7; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; }
                .details { background: #f8f9fa; padding: 1.5rem; border-radius: 6px; }
                .detail-row { margin-bottom: 0.75rem; }
                .label { font-weight: bold; color: #555; }
                .value { color: #333; }
                .highlight { background: #e7f3ff; padding: 0.5rem; border-radius: 4px; font-family: monospace; word-break: break-all; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🚨 New Service Request</h1>
                    <p>Order #{$data['request_id']}</p>
                </div>
                
                <div class='content'>
                    <div class='alert'>
                        <strong>Action Required:</strong> A new unlock request has been submitted and requires your attention.
                    </div>
                    
                    <div class='details'>
                        <h3>Request Details</h3>
                        <div class='detail-row'>
                            <span class='label'>Order ID:</span>
                            <span class='value'>#{$data['request_id']}</span>
                        </div>
                        <div class='detail-row'>
                            <span class='label'>Customer Email:</span>
                            <span class='value'>{$data['email']}</span>
                        </div>
                        <div class='detail-row'>
                            <span class='label'>Device Type:</span>
                            <span class='value'>" . ucfirst($data['device_type']) . "</span>
                        </div>
                        <div class='detail-row'>
                            <span class='label'>Device Model:</span>
                            <span class='value'>{$data['device_version']}</span>
                        </div>
                        <div class='detail-row'>
                            <span class='label'>IMEI/Serial:</span>
                            <span class='value'>{$data['imei_serial']}</span>
                        </div>
                        <div class='detail-row'>
                            <span class='label'>Description:</span>
                            <span class='value'>" . ($data['description'] ?: 'N/A') . "</span>
                        </div>
                        <div class='detail-row'>
                            <span class='label'>Amount:</span>
                            <span class='value'>\${$amount_formatted} USDT</span>
                        </div>
                        <div class='detail-row'>
                            <span class='label'>Payment Status:</span>
                            <span class='value'>Pending Verification</span>
                        </div>
                        <div class='detail-row'>
                            <span class='label'>Transaction Hash:</span>
                            <div class='highlight'>{$data['transaction_hash']}</div>
                        </div>
                        <div class='detail-row'>
                            <span class='label'>Wallet Address:</span>
                            <div class='highlight'>{$data['wallet_address']}</div>
                        </div>
                        <div class='detail-row'>
                            <span class='label'>Submitted:</span>
                            <span class='value'>" . date('Y-m-d H:i:s') . "</span>
                        </div>
                    </div>
                    
                    <h3>Next Steps</h3>
                    <ol>
                        <li>Verify the USDT transaction on the blockchain</li>
                        <li>Update the payment status in the admin panel</li>
                        <li>Begin processing the device unlock</li>
                        <li>Send progress updates to the customer</li>
                    </ol>
                    
                    <p><strong>Please log in to the admin panel to manage this request.</strong></p>
                </div>
            </div>
        </body>
        </html>";
    }
}
?>