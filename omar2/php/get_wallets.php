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

    // Get wallet addresses from settings
    $stmt = $db->prepare("
        SELECT setting_key, setting_value 
        FROM settings 
        WHERE setting_key IN ('usdt_trc20_address', 'usdt_erc20_address')
    ");
    $stmt->execute();
    $results = $stmt->fetchAll();

    // Format wallet data
    $wallets = [];
    foreach ($results as $row) {
        if ($row['setting_key'] === 'usdt_trc20_address') {
            $wallets['trc20'] = $row['setting_value'];
        } elseif ($row['setting_key'] === 'usdt_erc20_address') {
            $wallets['erc20'] = $row['setting_value'];
        }
    }

    // Provide default values if not set
    if (!isset($wallets['trc20'])) {
        $wallets['trc20'] = 'TQn9Y2khEsLJW1ChVWFMSMeRDow5KcbLSE';
    }
    if (!isset($wallets['erc20'])) {
        $wallets['erc20'] = '0x1234567890123456789012345678901234567890';
    }

    // Return wallet data
    echo json_encode([
        'success' => true,
        'wallets' => $wallets
    ]);

} catch (Exception $e) {
    error_log("Get wallets error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch wallet addresses'
    ]);
}
?>