<?php
require_once 'header.php';
$page_title = 'Edit Payment Channel';

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get channel ID from URL
$channel_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch channel details
$channel = [];
$stmt = $conn->prepare(" 
    SELECT pc.*, pm.method_name 
    FROM payment_channels pc
    JOIN payment_methods pm ON pc.method_id = pm.id
    WHERE pc.id = ? AND pc.user_id = ?");
$stmt->bind_param("ii", $channel_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $error = 'Payment channel not found or you don\'t have permission to edit it';
} else {
    $channel = $result->fetch_assoc();
}

// Get all payment methods for dropdown
$methods = $conn->query("SELECT * FROM payment_methods WHERE user_id='$user_id'");

// Update payment channel
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_channel'])) {
    $method_id = intval($_POST['method_id']);
    $account_number = sanitizeInput($_POST['account_number']);
    $account_name = sanitizeInput($_POST['account_name']);
    $min_amount = floatval($_POST['min_amount']);
    $max_amount = floatval($_POST['max_amount']);
    
    // Validation
    if (empty($method_id) || empty($account_number)) {
        $error = 'Method and Account Number are required';
    } elseif ($min_amount >= $max_amount) {
        $error = 'Maximum amount must be greater than minimum amount';
    } else {
        $sql = "UPDATE payment_channels SET
                method_id = ?,
                account_number = ?,
                account_name = ?,
                minimum_amount = ?,
                maximum_amount = ?
                WHERE id = ? AND user_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issddii", 
            $method_id, 
            $account_number, 
            $account_name, 
            $min_amount, 
            $max_amount, 
            $channel_id, 
            $user_id
        );
        
        if ($stmt->execute()) {
            $success = 'Payment channel updated successfully!';
            // Refresh channel data
            $channel = [
                'method_id' => $method_id,
                'account_number' => $account_number,
                'account_name' => $account_name,
                'minimum_amount' => $min_amount,
                'maximum_amount' => $max_amount
            ];
        } else {
            $error = 'Failed to update payment channel: ' . $conn->error;
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
                <h1 class="h2">Edit Payment Channel</h1>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4>Edit Payment Channel</h4>
                    <a href="payment_channels.php" class="btn btn-sm btn-secondary float-right">
                        Back to Channels
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($channel)):
                    ?>
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Payment Method</label>
                                <select name="method_id" class="form-control" required>
                                    <option value="">Select Method</option>
                                    <?php
                                    // Reset pointer of methods result set
                                    $methods->data_seek(0);
                                    while($method = $methods->fetch_assoc()):
                                    ?>
                                        <option value="<?php echo $method['id']; ?>" 
                                            <?php echo $method['id'] == $channel['method_id'] ? 'selected' : ''; ?>>
                                            <?php echo ucfirst($method['method_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Account Number</label>
                                <input type="text" name="account_number" class="form-control" 
                                    value="<?php echo htmlspecialchars($channel['account_number']); ?>" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Account Name (Optional)</label>
                                <input type="text" name="account_name" class="form-control" 
                                    value="<?php echo htmlspecialchars($channel['account_name']); ?>">
                            </div>
                            <div class="form-group col-md-3">
                                <label>Minimum Amount</label>
                                <input type="number" name="min_amount" class="form-control" min="1" 
                                    value="<?php echo $channel['minimum_amount']; ?>" required>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Maximum Amount</label>
                                <input type="number" name="max_amount" class="form-control" min="1" 
                                    value="<?php echo $channel['maximum_amount']; ?>" required>
                            </div>
                        </div>
                        <button type="submit" name="update_channel" class="btn btn-primary">
                            Update Payment Channel
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once 'footer.php'; ?>