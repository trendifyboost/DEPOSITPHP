<?php
require_once 'header.php';
$page_title = 'Edit Payment Method';

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get method ID from URL
$method_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch existing method details
if ($method_id > 0) {
    $sql = "SELECT * FROM payment_methods WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $method_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $method = $result->fetch_assoc();

    if (!$method) {
        $error = "Payment method not found or you don't have permission to edit it.";
        $method_id = 0; // Invalidate method_id if not found
    }
} else {
    $error = "Invalid payment method ID.";
}

// Handle form submission for updating method
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $method_id > 0) {
    $new_method_name = sanitizeInput($_POST['method_name']);

    if (empty($new_method_name)) {
        $error = "Method name cannot be empty.";
    } else {
        $sql = "UPDATE payment_methods SET method_name = ? WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $new_method_name, $method_id, $user_id);

        if ($stmt->execute()) {
            $success = "Payment method updated successfully!";
            // Update the fetched method data to reflect the change immediately
            $method['method_name'] = $new_method_name;
        } else {
            $error = "Failed to update payment method: " . $conn->error;
        }
    }
}
?>

<div class="main-container">
    <div class="sidebar-container">
        <?php include_once 'navbar.php'; ?>
    </div>
    <div class="content-container">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">Edit Payment Method</h1>
        </div>

        <div class="card">
            <div class="card-header">
                <h4>Edit Method</h4>
            </div>
            <div class="card-body">
                <?php 
                if (isset($success) && $success) echo displaySuccess($success);
                if (isset($error) && $error) echo displayError($error);
                ?>
                
                <?php if ($method_id > 0 && $method): // Only show form if method is valid ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="method_name">Method Name</label>
                        <input type="text" class="form-control" id="method_name" name="method_name" value="<?php echo htmlspecialchars($method['method_name']); ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Method</button>
                    <a href="payment_methods.php" class="btn btn-secondary">Back to Payment Methods</a>
                </form>
                <?php elseif (!$error): // If method_id is 0 but no specific error, show a generic message ?>
                    <div class="alert alert-info">Please select a valid payment method to edit.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>