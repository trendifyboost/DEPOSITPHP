<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

// Verify API Key
if (!isset($_SERVER['HTTP_API_KEY'])) {
    http_response_code(401);
    die(json_encode(['error' => 'API key is required']));
}

$api_key = sanitizeInput($_SERVER['HTTP_API_KEY']);
$sql = "SELECT id FROM users WHERE api_key='$api_key'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    http_response_code(403);
    die(json_encode(['error' => 'Invalid API key']));
}

$user = $result->fetch_assoc();
$user_id = $user['id'];

// Process payment request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $client_user_id = sanitizeInput($data['user_id']);
    $method_name = sanitizeInput($data['method_name']);
    $amount = floatval($data['amount']);
    
    // Get available payment channels for this method
    $sql = "SELECT pc.* FROM payment_channels pc
            JOIN payment_methods pm ON pc.method_id = pm.id
            WHERE pm.user_id='$user_id' AND pm.method_name='$method_name'
            AND pc.status=1 AND $amount BETWEEN pc.minimum_amount AND pc.maximum_amount";
    
    $channels = $conn->query($sql);
    
    if ($channels->num_rows > 0) {
        $channel = $channels->fetch_assoc();
        
        // Create transaction
        $transaction_sql = "INSERT INTO transactions 
                           (user_id, client_user_id, method_id, channel_id, amount, status)
                           VALUES ('$user_id', '$client_user_id', '{$channel['method_id']}', '{$channel['id']}', '$amount', 'pending')";
        
        if ($conn->query($transaction_sql)) {
            $transaction_id = $conn->insert_id;
            
            $response = [
                'success' => true,
                'transaction_id' => $transaction_id,
                'payment_number' => $channel['account_number'],
                'account_name' => $channel['account_name'],
                'amount' => $amount
            ];
            
            echo json_encode($response);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create transaction']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'No available payment channel for this amount']);
    }
}

// Verify payment
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['transaction_id'])) {
    $transaction_id = sanitizeInput($_GET['transaction_id']);
    
    $sql = "SELECT * FROM transactions WHERE id='$transaction_id' AND user_id='$user_id'";
    $transaction = $conn->query($sql)->fetch_assoc();
    
    if ($transaction) {
        echo json_encode($transaction);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Transaction not found']);
    }
}
?>