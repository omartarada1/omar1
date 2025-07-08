<?php
require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception('Database connection failed');
    }

    // Get device type from query parameter
    $device_type = $_GET['device_type'] ?? '';
    
    if (empty($device_type)) {
        throw new Exception('Device type is required');
    }

    // Validate device type
    $valid_devices = ['iphone', 'ipad', 'mac'];
    if (!in_array($device_type, $valid_devices)) {
        throw new Exception('Invalid device type');
    }

    // Get device versions from database
    $stmt = $db->prepare("
        SELECT id, name, price 
        FROM device_versions 
        WHERE device_type = ? AND is_active = 1 
        ORDER BY sort_order ASC, name ASC
    ");
    $stmt->execute([$device_type]);
    $versions = $stmt->fetchAll();

    // Return versions data
    echo json_encode([
        'success' => true,
        'device_type' => $device_type,
        'versions' => $versions
    ]);

} catch (Exception $e) {
    error_log("Get device versions error: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>