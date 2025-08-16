<?php
require_once 'header.php';
$page_title = 'Payment Management'; // Changed page title to be more generic

$user_id = $_SESSION['user_id'];
$error = ''; // Initialize error and success
$success = '';

// Handle Add Payment Method
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_method'])) {
    $method_name = sanitizeInput($_POST['method_name']);
    
    $sql = "INSERT INTO payment_methods (user_id, method_name) VALUES ('$user_id', '$method_name')";
    if ($conn->query($sql)) {
        $success = "Payment method added successfully";
    } else {
        $error = "Error adding payment method";
    }
}

// Handle Add Payment Channel
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_channel'])) {
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
        $sql = "INSERT INTO payment_channels 
                (method_id, user_id, account_number, account_name, minimum_amount, maximum_amount) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iissdd", $method_id, $user_id, $account_number, $account_name, $min_amount, $max_amount);
        
        if ($stmt->execute()) {
            $success = 'Payment channel added successfully!';
        } else {
            $error = 'Failed to add payment channel: ' . $conn->error;
        }
    }
}

// Handle Delete Payment Channel
if (isset($_GET['delete_channel'])) { // Changed parameter name to avoid conflict if payment_methods.php also uses 'delete'
    $channel_id = intval($_GET['delete_channel']);
    $sql = "DELETE FROM payment_channels WHERE id=? AND user_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $channel_id, $user_id);
    
    if ($stmt->execute()) {
        $success = 'Payment channel deleted successfully!';
    } else {
        $error = 'Failed to delete payment channel';
    }
}

// Get all payment methods (for both display and channel dropdown)
$sql_methods = "SELECT * FROM payment_methods WHERE user_id='$user_id'";
$methods = $conn->query($sql_methods);

// Get all payment channels
$sql_channels = "
    SELECT pc.*, pm.method_name 
    FROM payment_channels pc
    JOIN payment_methods pm ON pc.method_id = pm.id
    WHERE pc.user_id='$user_id'
    ORDER BY pm.method_name, pc.account_number
";
$channels = $conn->query($sql_channels);
?>

<div class="main-container">
    <div class="sidebar-container">
        <?php include_once 'navbar.php'; ?>
    </div>
    <div class="content-container">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">Payment Management</h1>
        </div>

        <?php 
        if (isset($success) && $success) echo displaySuccess($success);
        if (isset($error) && $error) echo displayError($error);
        ?>

<!-- Tab Content -->
<div class="tab-content p-3 border-left border-right border-bottom rounded-bottom" id="paymentTabsContent">
    <!-- Payment Methods Tab -->
    <div class="tab-pane fade show active" id="methods" role="tabpanel" aria-labelledby="methods-tab">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-cog mr-2"></i>Manage Payment Methods</h4>
            </div>
            <div class="card-body">
                <!-- Add Method Form -->
                <form method="POST" class="mb-4 p-4 border rounded bg-light">
                    <div class="form-row align-items-center">
                        <div class="col-md-8 mb-2 mb-md-0">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-plus-circle"></i></span>
                                </div>
                                <input type="text" name="method_name" class="form-control" placeholder="Method Name (e.g. bKash, Nagad, Rocket)" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" name="add_method" class="btn btn-primary btn-block">
                                <i class="fas fa-plus mr-2"></i>Add Method
                            </button>
                        </div>
                    </div>
                </form>
                
                <!-- Methods Table -->
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="thead-light">
                            <tr>
                                <th width="10%">ID</th>
                                <th width="30%">Method Name</th>
                                <th width="20%">Status</th>
                                <th width="40%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($method = $methods->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $method['id']; ?></td>
                                <td>
                                    <i class="fab fa-cc-<?php echo strtolower($method['method_name']); ?> mr-2 text-primary"></i>
                                    <?php echo ucfirst($method['method_name']); ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo ($method['status'] == 1) ? 'success' : 'secondary'; ?> p-2">
                                        <?php echo ($method['status'] == 1) ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="edit_method.php?id=<?php echo $method['id']; ?>" class="btn btn-sm btn-outline-info">
                                        <i class="fas fa-edit mr-1"></i>Edit
                                    </a>
                                    <a href="delete_method.php?id=<?php echo $method['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this method?')">
                                        <i class="fas fa-trash-alt mr-1"></i>Delete
                                    </a>
                                    <a href="#" class="btn btn-sm btn-<?php echo ($method['status'] == 1) ? 'warning' : 'success'; ?> toggle-status" data-id="<?php echo $method['id']; ?>">
                                        <i class="fas fa-power-off mr-1"></i><?php echo ($method['status'] == 1) ? 'Deactivate' : 'Activate'; ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Channels Tab -->
    <div class="tab-pane fade" id="channels" role="tabpanel" aria-labelledby="channels-tab">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0"><i class="fas fa-sliders-h mr-2"></i>Manage Payment Channels</h4>
            </div>
            <div class="card-body">
                <!-- Add New Channel Form -->
                <form method="POST" class="mb-4 p-4 border rounded bg-light">
                    <h5 class="mb-4 text-primary"><i class="fas fa-plus-circle mr-2"></i>Add New Payment Channel</h5>
                    
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label><i class="fas fa-credit-card mr-2"></i>Payment Method</label>
                            <select name="method_id" class="form-control selectpicker" data-live-search="true" required>
                                <option value="">Select Method</option>
                                <?php
                                $methods->data_seek(0);
                                while($method = $methods->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $method['id']; ?>">
                                        <?php echo ucfirst($method['method_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label><i class="fas fa-hashtag mr-2"></i>Account Number</label>
                            <input type="text" name="account_number" class="form-control" placeholder="e.g. 017XXXXXXXX" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label><i class="fas fa-user-tag mr-2"></i>Account Name (Optional)</label>
                            <input type="text" name="account_name" class="form-control" placeholder="Account holder name">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label><i class="fas fa-arrow-down mr-2"></i>Minimum Amount</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">৳</span>
                                </div>
                                <input type="number" name="min_amount" class="form-control" min="1" value="10" required>
                            </div>
                        </div>
                        <div class="form-group col-md-6">
                            <label><i class="fas fa-arrow-up mr-2"></i>Maximum Amount</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">৳</span>
                                </div>
                                <input type="number" name="max_amount" class="form-control" min="1" value="100000" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-right mt-3">
                        <button type="submit" name="add_channel" class="btn btn-success px-4">
                            <i class="fas fa-save mr-2"></i>Save Channel
                        </button>
                    </div>
                </form>
                
                <!-- Channels List -->
                <div class="mt-5">
                    <h5 class="mb-3 text-success"><i class="fas fa-list mr-2"></i>Your Payment Channels</h5>
                    
                    <?php if ($channels->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Method</th>
                                        <th>Account Details</th>
                                        <th>Amount Range</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($channel = $channels->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fab fa-cc-<?php echo strtolower($channel['method_name']); ?> fa-2x mr-3 text-primary"></i>
                                                <strong><?php echo ucfirst($channel['method_name']); ?></strong>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="font-weight-bold"><?php echo $channel['account_number']; ?></span>
                                                <small class="text-muted"><?php echo $channel['account_name'] ?: 'No name provided'; ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="text-success">৳<?php echo number_format($channel['minimum_amount'], 2); ?></span>
                                                <span class="text-danger">৳<?php echo number_format($channel['maximum_amount'], 2); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="edit_channel.php?id=<?php echo $channel['id']; ?>" 
                                                   class="btn btn-outline-primary"
                                                   data-toggle="tooltip" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="payment_methods.php?delete_channel=<?php echo $channel['id']; ?>" 
                                                   class="btn btn-outline-danger"
                                                   onclick="return confirm('Are you sure you want to delete this channel?')"
                                                   data-toggle="tooltip" title="Delete">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                                <button class="btn btn-outline-secondary" data-toggle="tooltip" title="View Transactions">
                                                    <i class="fas fa-history"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-wallet fa-4x text-muted mb-4"></i>
                            <h4 class="text-muted">No Payment Channels Found</h4>
                            <p class="text-muted">Add your first payment channel to start accepting payments</p>
                            
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize tooltips
$(function () {
  $('[data-toggle="tooltip"]').tooltip()
});

// Toggle status button functionality
$('.toggle-status').click(function(e) {
    e.preventDefault();
    var methodId = $(this).data('id');
    // AJAX call to toggle status would go here
    alert('Status toggle functionality would be implemented here for method ID: ' + methodId);
});
</script>



<?php require_once 'footer.php'; ?>