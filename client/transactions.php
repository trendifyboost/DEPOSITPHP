<?php
require_once 'header.php';
$page_title = 'Transactions';

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Date range filter
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Status filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Get all transactions with filters
$sql = "SELECT t.*, m.method_name, c.account_number 
        FROM transactions t
        JOIN payment_methods m ON t.method_id = m.id
        JOIN payment_channels c ON t.channel_id = c.id
        WHERE t.user_id = ?";

$params = [$user_id];
$types = "i";

// Add date filter
$sql .= " AND DATE(t.created_at) BETWEEN ? AND ?";
$params[] = $start_date;
$params[] = $end_date;
$types .= "ss";

// Add status filter
if ($status_filter !== 'all') {
    $sql .= " AND t.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$sql .= " ORDER BY t.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$transactions = $stmt->get_result();
?>

<div class="main-container">
    <div class="sidebar-container">
        <?php include_once 'navbar.php'; ?>
    </div>
    <div class="content-container">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">Transactions</h1>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h4>Transaction History</h4>
                    <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#filterModal">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <!-- Filter Summary -->
                <div class="alert alert-info">
                    Showing transactions: 
                    <strong><?php echo $status_filter === 'all' ? 'All Statuses' : ucfirst($status_filter); ?></strong>
                    from <strong><?php echo date('M j, Y', strtotime($start_date)); ?></strong>
                    to <strong><?php echo date('M j, Y', strtotime($end_date)); ?></strong>
                </div>

                <!-- Transactions Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>Date</th>
                                <th>Transaction ID</th>
                                <th>Method</th>
                                <th>Account</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($transactions->num_rows > 0): ?>
                                <?php while($txn = $transactions->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('M j, Y h:i A', strtotime($txn['created_at'])); ?></td>
                                    <td><?php echo $txn['transaction_number'] ?? 'N/A'; ?></td>
                                    <td><?php echo ucfirst($txn['method_name']); ?></td>
                                    <td><?php echo $txn['account_number']; ?></td>
                                    <td class="font-weight-bold">৳<?php echo number_format($txn['amount'], 2); ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $txn['status'] === 'completed' ? 'success' : 
                                                 ($txn['status'] === 'pending' ? 'warning' : 'danger');
                                        ?>">
                                            <?php echo ucfirst($txn['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info" data-toggle="modal" 
                                            data-target="#detailsModal" 
                                            data-txn='<?php echo json_encode($txn); ?>'>
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">No transactions found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Transaction Details Modal -->
                <div class="modal fade" id="detailsModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Transaction Details</h5>
                                <button type="button" class="close" data-dismiss="modal">
                                    <span>&times;</span>
                                </button>
                            </div>
                            <div class="modal-body" id="txnDetails">
                                <!-- Details will be loaded via JavaScript -->
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Modal -->
                <div class="modal fade" id="filterModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="GET">
                                <div class="modal-header">
                                    <h5 class="modal-title">Filter Transactions</h5>
                                    <button type="button" class="close" data-dismiss="modal">
                                        <span>&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label>Date Range</label>
                                        <div class="form-row">
                                            <div class="col">
                                                <input type="date" name="start_date" class="form-control" 
                                                    value="<?php echo $start_date; ?>">
                                            </div>
                                            <div class="col">
                                                <input type="date" name="end_date" class="form-control" 
                                                    value="<?php echo $end_date; ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Status</label>
                                        <select name="status" class="form-control">
                                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Transaction Details Modal
$('#detailsModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var txn = button.data('txn');
    var modal = $(this);
    
    var detailsHtml = `
        <div class="row mb-3">
            <div class="col-md-6">
                <strong>Transaction ID:</strong>
                <p>${txn.transaction_number || 'N/A'}</p>
            </div>
            <div class="col-md-6">
                <strong>Date:</strong>
                <p>${new Date(txn.created_at).toLocaleString()}</p>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <strong>Payment Method:</strong>
                <p>${txn.method_name}</p>
            </div>
            <div class="col-md-6">
                <strong>Account Number:</strong>
                <p>${txn.account_number}</p>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <strong>Amount:</strong>
                <p>৳${parseFloat(txn.amount).toFixed(2)}</p>
            </div>
            <div class="col-md-6">
                <strong>Status:</strong>
                <p><span class="badge badge-${txn.status === 'completed' ? 'success' : 
                    (txn.status === 'pending' ? 'warning' : 'danger')}">
                    ${txn.status.charAt(0).toUpperCase() + txn.status.slice(1)}
                </span></p>
            </div>
        </div>
        ${txn.status === 'failed' ? `
        <div class="alert alert-danger">
            <strong>Failure Reason:</strong>
            <p>${txn.failure_reason || 'Not specified'}</p>
        </div>` : ''}
    `;
    
    modal.find('#txnDetails').html(detailsHtml);
});
</script>

<?php require_once 'footer.php'; ?>