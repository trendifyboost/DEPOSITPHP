<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SERVER['HTTP_API_KEY'])) {
    http_response_code(401);
    die(json_encode(['error' => 'API key is required']));
}

$api_key = sanitizeInput($_SERVER['HTTP_API_KEY']);
$sql = "SELECT id, name, company_name FROM users WHERE api_key = ? AND status = 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $api_key);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    http_response_code(403);
    die(json_encode(['error' => 'Invalid API key']));
}

// API key is valid
$user = $result->fetch_assoc();
echo json_encode([
    'success' => true,
    'client_id' => $user['id'],
    'client_name' => $user['name'],
    'company_name' => $user['company_name']
]);
?>