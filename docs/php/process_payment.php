<?php
require_once '../config/database.php';
require_once 'email_handler.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        throw new Exception('Invalid JSON data');
    }

    // Validate required fields
    $required_fields = ['customerEmail', 'deviceType', 'deviceVersion', 'imeiSerial', 'amount', 'paymentMethod', 'paymentData'];
    foreach ($required_fields as $field) {
        if (empty($data[$field]) && $data[$field] !== 0) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Sanitize input data
    $customerEmail = filter_var($data['customerEmail'], FILTER_SANITIZE_EMAIL);
    $deviceType = sanitize_input($data['deviceType']);
    $deviceVersion = sanitize_input($data['deviceVersion']);
    $imeiSerial = sanitize_input($data['imeiSerial']);
    $description = sanitize_input($data['description'] ?? '');
    $amount = (float) $data['amount'];
    $paymentMethod = sanitize_input($data['paymentMethod']);
    $paymentData = $data['paymentData'];

    // Validate email
    if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address');
    }

    // Validate device type
    $valid_devices = ['iphone', 'ipad', 'mac'];
    if (!in_array($deviceType, $valid_devices)) {
        throw new Exception('Invalid device type');
    }

    // Validate payment method
    $valid_payment_methods = ['card', 'paypal', 'usdt'];
    if (!in_array($paymentMethod, $valid_payment_methods)) {
        throw new Exception('Invalid payment method');
    }

    // Validate amount
    if ($amount <= 0) {
        throw new Exception('Invalid payment amount');
    }

    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception('Database connection failed');
    }

    // Process payment based on method
    $payment_status = 'pending';
    $payment_details = [];

    switch ($paymentMethod) {
        case 'card':
            $payment_details = processStripePayment($paymentData, $amount);
            $payment_status = $payment_details['status'];
            break;
            
        case 'paypal':
            $payment_details = processPayPalPayment($paymentData, $amount);
            $payment_status = $payment_details['status'];
            break;
            
        case 'usdt':
            $payment_details = processUSDTPayment($paymentData, $amount, $db);
            $payment_status = $payment_details['status'];
            break;
            
        default:
            throw new Exception('Unsupported payment method');
    }

    // Generate unique request ID
    $request_id = generateRequestId();

    // Prepare payment data for storage
    $payment_data_json = json_encode([
        'payment_method' => $paymentMethod,
        'payment_details' => $payment_details,
        'amount_paid' => $amount,
        'currency' => 'USD',
        'processed_at' => date('Y-m-d H:i:s')
    ]);

    // Insert request into database
    $stmt = $db->prepare("
        INSERT INTO unlock_requests 
        (device_type, imei_serial, email, description, payment_method, payment_status, payment_data, amount, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        $deviceType,
        $imeiSerial,
        $customerEmail,
        $description,
        $paymentMethod,
        $payment_status,
        $payment_data_json,
        $amount
    ]);

    $db_request_id = $db->lastInsertId();

    // Send confirmation emails
    $email_handler = new EmailHandler($db);
    
    // Prepare email data
    $email_data = [
        'request_id' => $db_request_id,
        'device_type' => $deviceType,
        'device_version' => $deviceVersion,
        'imei_serial' => $imeiSerial,
        'email' => $customerEmail,
        'description' => $description,
        'amount' => $amount,
        'payment_method' => strtoupper($paymentMethod),
        'payment_status' => $payment_status,
        'payment_details' => $payment_details
    ];

    // Send confirmation email to customer
    $email_sent_customer = $email_handler->sendCustomerConfirmation($email_data);

    // Send notification email to admin
    $email_sent_admin = $email_handler->sendAdminNotification($email_data);

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Payment processed and service request submitted successfully',
        'order_id' => $db_request_id,
        'request_id' => $request_id,
        'payment_status' => $payment_status,
        'email_sent' => $email_sent_customer && $email_sent_admin
    ]);

} catch (Exception $e) {
    error_log("Payment processing error: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function processStripePayment($paymentData, $amount) {
    // Note: This is a simplified implementation
    // In production, you should use Stripe's server-side API to create charges
    
    try {
        // Validate token
        if (empty($paymentData['token'])) {
            throw new Exception('Invalid Stripe token');
        }

        // For demo purposes, we'll simulate a successful payment
        // In production, you would:
        // 1. Include Stripe PHP library
        // 2. Create a charge using the token
        // 3. Handle the response
        
        return [
            'status' => 'paid',
            'transaction_id' => 'stripe_' . uniqid(),
            'token' => $paymentData['token'],
            'last4' => $paymentData['last4'] ?? '****',
            'brand' => $paymentData['brand'] ?? 'card',
            'processed_at' => date('Y-m-d H:i:s')
        ];
        
    } catch (Exception $e) {
        return [
            'status' => 'failed',
            'error' => $e->getMessage()
        ];
    }
}

function processPayPalPayment($paymentData, $amount) {
    try {
        // Validate PayPal data
        if (empty($paymentData['orderID']) || empty($paymentData['paymentID'])) {
            throw new Exception('Invalid PayPal payment data');
        }

        // In production, you should verify the payment with PayPal's API
        
        return [
            'status' => 'paid',
            'transaction_id' => $paymentData['paymentID'],
            'order_id' => $paymentData['orderID'],
            'payer_id' => $paymentData['payerID'] ?? '',
            'payer_email' => $paymentData['payer']['email_address'] ?? '',
            'processed_at' => date('Y-m-d H:i:s')
        ];
        
    } catch (Exception $e) {
        return [
            'status' => 'failed',
            'error' => $e->getMessage()
        ];
    }
}

function processUSDTPayment($paymentData, $amount, $db) {
    try {
        // Validate transaction hash
        $transactionHash = sanitize_input($paymentData['transactionHash']);
        $walletAddress = sanitize_input($paymentData['walletAddress'] ?? '');
        
        if (strlen($transactionHash) < 10) {
            throw new Exception('Invalid transaction hash format');
        }

        // Check if transaction hash already exists
        $stmt = $db->prepare("
            SELECT id FROM unlock_requests 
            WHERE JSON_EXTRACT(payment_data, '$.payment_details.transaction_hash') = ? 
            OR payment_data LIKE ?
        ");
        $stmt->execute([$transactionHash, '%"transaction_hash":"' . $transactionHash . '"%']);
        
        if ($stmt->fetch()) {
            throw new Exception('This transaction hash has already been used');
        }

        return [
            'status' => 'pending', // USDT payments need manual verification
            'transaction_hash' => $transactionHash,
            'wallet_address' => $walletAddress,
            'currency' => 'USDT',
            'processed_at' => date('Y-m-d H:i:s')
        ];
        
    } catch (Exception $e) {
        return [
            'status' => 'failed',
            'error' => $e->getMessage()
        ];
    }
}

function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generateRequestId() {
    return 'FS_' . date('Y') . '_' . strtoupper(substr(md5(uniqid()), 0, 8));
}
?>