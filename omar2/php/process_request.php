<?php
require_once '../config/database.php';
require_once 'email_handler.php';
require_once 'payment_handler.php';

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
    $required_fields = ['device_type', 'imei_serial', 'email', 'payment_method', 'amount'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Sanitize input data
    $device_type = sanitize_input($data['device_type']);
    $imei_serial = sanitize_input($data['imei_serial']);
    $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    $description = sanitize_input($data['description'] ?? '');
    $payment_method = sanitize_input($data['payment_method']);
    $amount = (float) $data['amount'];

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address');
    }

    // Validate device type
    $valid_devices = ['iphone', 'ipad', 'mac'];
    if (!in_array($device_type, $valid_devices)) {
        throw new Exception('Invalid device type');
    }

    // Validate payment method
    $valid_payment_methods = ['card', 'paypal', 'usdt'];
    if (!in_array($payment_method, $valid_payment_methods)) {
        throw new Exception('Invalid payment method');
    }

    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception('Database connection failed');
    }

    // Verify pricing
    $stmt = $db->prepare("SELECT price FROM pricing WHERE device_type = ?");
    $stmt->execute([$device_type]);
    $pricing = $stmt->fetch();

    if (!$pricing || abs($pricing['price'] - $amount) > 0.01) {
        throw new Exception('Invalid pricing amount');
    }

    // Process payment
    $payment_handler = new PaymentHandler($db);
    $payment_result = null;

    switch ($payment_method) {
        case 'card':
            if (empty($data['stripe_token'])) {
                throw new Exception('Missing Stripe token');
            }
            $payment_result = $payment_handler->processStripePayment($data['stripe_token'], $amount, $email);
            break;

        case 'paypal':
            if (empty($data['payment_data'])) {
                throw new Exception('Missing PayPal payment data');
            }
            $payment_data = json_decode($data['payment_data'], true);
            $payment_result = $payment_handler->processPayPalPayment($payment_data, $amount);
            break;

        case 'usdt':
            if (empty($data['tx_hash'])) {
                throw new Exception('Missing transaction hash');
            }
            $payment_result = $payment_handler->processUSDTPayment($data['tx_hash'], $amount);
            break;
    }

    // Generate unique request ID
    $request_id = generateRequestId();

    // Prepare payment data for storage
    $payment_data_json = json_encode([
        'payment_method' => $payment_method,
        'payment_result' => $payment_result,
        'raw_data' => $data['payment_data'] ?? null,
        'tx_hash' => $data['tx_hash'] ?? null,
        'stripe_token' => $data['stripe_token'] ?? null
    ]);

    // Insert request into database
    $stmt = $db->prepare("
        INSERT INTO unlock_requests 
        (device_type, imei_serial, email, description, payment_method, payment_status, payment_data, amount) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $payment_status = $payment_result['success'] ? 'paid' : 'pending';
    
    $stmt->execute([
        $device_type,
        $imei_serial,
        $email,
        $description,
        $payment_method,
        $payment_status,
        $payment_data_json,
        $amount
    ]);

    $request_id = $db->lastInsertId();

    // Send emails
    $email_handler = new EmailHandler($db);
    
    // Send confirmation email to customer
    $email_sent_customer = $email_handler->sendCustomerConfirmation([
        'request_id' => $request_id,
        'device_type' => $device_type,
        'imei_serial' => $imei_serial,
        'email' => $email,
        'amount' => $amount,
        'payment_method' => $payment_method,
        'payment_status' => $payment_status
    ]);

    // Send notification email to admin
    $email_sent_admin = $email_handler->sendAdminNotification([
        'request_id' => $request_id,
        'device_type' => $device_type,
        'imei_serial' => $imei_serial,
        'customer_email' => $email,
        'description' => $description,
        'amount' => $amount,
        'payment_method' => $payment_method,
        'payment_status' => $payment_status,
        'payment_data' => $payment_result
    ]);

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Request submitted successfully',
        'request_id' => $request_id,
        'payment_status' => $payment_status,
        'email_sent' => $email_sent_customer && $email_sent_admin
    ]);

} catch (Exception $e) {
    error_log("Request processing error: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generateRequestId() {
    return 'REQ_' . date('Y') . '_' . strtoupper(substr(md5(uniqid()), 0, 8));
}
?>