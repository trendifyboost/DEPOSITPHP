<?php
// Site Configuration
define('SITE_URL', 'http://yourdomain.com/bdautopay/');
define('SITE_NAME', 'Bd Auto Pay Solution');

// Start session
session_start();

// Include database connection
require_once 'db.php';
require_once 'functions.php';

// API Key Validation Middleware
function validateApiKey() {
    global $conn;
    
    if (!isset($_SERVER['HTTP_API_KEY'])) {
        http_response_code(401);
        die(json_encode(['error' => 'API key is required']));
    }
    
    $api_key = sanitizeInput($_SERVER['HTTP_API_KEY']);
    $sql = "SELECT id FROM users WHERE api_key = ? AND status = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $api_key);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows == 0) {
        http_response_code(403);
        die(json_encode(['error' => 'Invalid API key']));
    }
}


?>