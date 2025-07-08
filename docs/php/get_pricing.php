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

    // Get pricing from database
    $stmt = $db->prepare("SELECT device_type, price FROM pricing ORDER BY device_type");
    $stmt->execute();
    $results = $stmt->fetchAll();

    // Format pricing data
    $pricing = [];
    foreach ($results as $row) {
        $pricing[$row['device_type']] = (float) $row['price'];
    }

    // Return pricing data
    echo json_encode([
        'success' => true,
        'pricing' => $pricing
    ]);

} catch (Exception $e) {
    error_log("Get pricing error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch pricing'
    ]);
}
?>