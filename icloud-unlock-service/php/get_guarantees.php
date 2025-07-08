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

    // Get guarantees content from database
    $stmt = $db->prepare("SELECT content FROM guarantees_content WHERE id = 1");
    $stmt->execute();
    $result = $stmt->fetch();

    $content = '';
    if ($result && !empty($result['content'])) {
        $content = $result['content'];
    }

    // Return content
    echo json_encode([
        'success' => true,
        'content' => $content
    ]);

} catch (Exception $e) {
    error_log("Get guarantees error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch guarantees content'
    ]);
}
?>