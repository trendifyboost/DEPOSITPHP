<?php
require_once 'header.php';
$page_title = 'Delete Payment Method';

$user_id = $_SESSION['user_id'];

$error = '';
$success = '';

// Get method ID from URL
$method_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($method_id > 0) {
    // First, check if the method belongs to the current user for security
    $check_sql = "SELECT id FROM payment_methods WHERE id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $method_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        // Method belongs to user, proceed with deletion
        $delete_sql = "DELETE FROM payment_methods WHERE id = ? AND user_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("ii", $method_id, $user_id);

        if ($delete_stmt->execute()) {
            $_SESSION['success_message'] = "Payment method deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to delete payment method: " . $conn->error;
        }
    } else {
        $_SESSION['error_message'] = "Payment method not found or you don't have permission to delete it.";
    }
} else {
    $_SESSION['error_message'] = "Invalid payment method ID.";
}

// Redirect back to payment_methods.php
header("Location: payment_methods.php");
exit();
?>