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
    $stmt = $db->query("
        SELECT setting_key, setting_value 
        FROM settings 
        WHERE setting_key IN ('usdt_trc20_address', 'usdt_erc20_address')
    ");
    
    $wallets = [
        'trc20' => 'TQn9Y2khEsLJW1ChVWFMSMeRDow5KcbLSE', // Default TRC20 address
        'erc20' => '0x1234567890123456789012345678901234567890' // Default ERC20 address
    ];
    
    while ($row = $stmt->fetch()) {
        if ($row['setting_key'] === 'usdt_trc20_address') {
            $wallets['trc20'] = $row['setting_value'];
        } elseif ($row['setting_key'] === 'usdt_erc20_address') {
            $wallets['erc20'] = $row['setting_value'];
        }
    }

    echo json_encode([
        'success' => true,
        'wallets' => $wallets
    ]);

} catch (Exception $e) {
    error_log("Get wallets error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load wallet addresses',
        'wallets' => [
            'trc20' => 'TQn9Y2khEsLJW1ChVWFMSMeRDow5KcbLSE', // Fallback address
            'erc20' => '0x1234567890123456789012345678901234567890'
        ]
    ]);
}
?>