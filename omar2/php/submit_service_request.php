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
    $required_fields = ['customerEmail', 'deviceType', 'deviceVersion', 'imeiSerial', 'transactionHash', 'amount'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Sanitize input data
    $customerEmail = filter_var($data['customerEmail'], FILTER_SANITIZE_EMAIL);
    $deviceType = sanitize_input($data['deviceType']);
    $deviceVersion = sanitize_input($data['deviceVersion']);
    $imeiSerial = sanitize_input($data['imeiSerial']);
    $description = sanitize_input($data['description'] ?? '');
    $transactionHash = sanitize_input($data['transactionHash']);
    $amount = (float) $data['amount'];
    $walletAddress = sanitize_input($data['walletAddress'] ?? '');

    // Validate email
    if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address');
    }

    // Validate device type
    $valid_devices = ['iphone', 'ipad', 'mac'];
    if (!in_array($deviceType, $valid_devices)) {
        throw new Exception('Invalid device type');
    }

    // Validate transaction hash format (basic validation)
    if (strlen($transactionHash) < 10) {
        throw new Exception('Invalid transaction hash format');
    }

    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception('Database connection failed');
    }

    // Check if transaction hash already exists
    $stmt = $db->prepare("
        SELECT id FROM unlock_requests 
        WHERE JSON_EXTRACT(payment_data, '$.transaction_hash') = ? 
        OR payment_data LIKE ?
    ");
    $stmt->execute([$transactionHash, '%"transaction_hash":"' . $transactionHash . '"%']);
    
    if ($stmt->fetch()) {
        throw new Exception('This transaction hash has already been used');
    }

    // Generate unique request ID
    $request_id = generateRequestId();

    // Prepare payment data for storage
    $payment_data_json = json_encode([
        'payment_method' => 'usdt',
        'transaction_hash' => $transactionHash,
        'wallet_address' => $walletAddress,
        'amount_paid' => $amount,
        'currency' => 'USDT'
    ]);

    // Insert request into database
    $stmt = $db->prepare("
        INSERT INTO unlock_requests 
        (device_type, imei_serial, email, description, payment_method, payment_status, payment_data, amount) 
        VALUES (?, ?, ?, ?, 'usdt', 'pending', ?, ?)
    ");

    $stmt->execute([
        $deviceType,
        $imeiSerial,
        $customerEmail,
        $description,
        $payment_data_json,
        $amount
    ]);

    $db_request_id = $db->lastInsertId();

    // Send emails
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
        'payment_method' => 'USDT',
        'payment_status' => 'pending',
        'transaction_hash' => $transactionHash,
        'wallet_address' => $walletAddress
    ];

    // Send confirmation email to customer
    $email_sent_customer = $email_handler->sendServiceRequestConfirmation($email_data);

    // Send notification email to admin
    $email_sent_admin = $email_handler->sendServiceRequestNotification($email_data);

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Service request submitted successfully',
        'order_id' => $db_request_id,
        'request_id' => $request_id,
        'email_sent' => $email_sent_customer && $email_sent_admin
    ]);

} catch (Exception $e) {
    error_log("Service request submission error: " . $e->getMessage());
    
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
    return 'FS_' . date('Y') . '_' . strtoupper(substr(md5(uniqid()), 0, 8));
}
?>