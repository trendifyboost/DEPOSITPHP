<?php
require_once 'header.php';
$page_title = 'API Settings';

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Check if webhook_url column exists
$column_check = $conn->query("SHOW COLUMNS FROM `users` LIKE 'webhook_url'");
$has_webhook_column = ($column_check->num_rows > 0);

// Get user data
if ($has_webhook_column) {
    $user_query = $conn->query("SELECT api_key, webhook_url FROM users WHERE id='$user_id'");
} else {
    $user_query = $conn->query("SELECT api_key FROM users WHERE id='$user_id'");
}

if (!$user_query) {
    die("Database error: " . $conn->error);
}

$user = $user_query->fetch_assoc();
if (!$user) {
    die("User data not found. Please contact support.");
}

// Initialize webhook_url if column doesn't exist
if (!$has_webhook_column) {
    $user['webhook_url'] = null;
}

// Regenerate API Key
if (isset($_POST['regenerate_key'])) {
    $new_api_key = md5(uniqid(rand(), true));
    
    $stmt = $conn->prepare("UPDATE users SET api_key=? WHERE id=?");
    if (!$stmt) {
        $error = "Prepare failed: " . $conn->error;
    } else {
        $stmt->bind_param("si", $new_api_key, $user_id);
        
        if ($stmt->execute()) {
            $success = "API Key regenerated successfully!";
            $user['api_key'] = $new_api_key;
            $_SESSION['api_key'] = $new_api_key;
        } else {
            $error = "Failed to regenerate API Key: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Update Webhook URL (only if column exists)
if (isset($_POST['update_webhook'])) {
    if (!$has_webhook_column) {
        $error = "Webhook feature is not available. Please contact support.";
    } else {
        $webhook_url = !empty($_POST['webhook_url']) ? filter_var($_POST['webhook_url'], FILTER_VALIDATE_URL) : '';
        
        if ($webhook_url === false) {
            $error = "Please enter a valid URL for the webhook";
        } else {
            $stmt = $conn->prepare("UPDATE users SET webhook_url=? WHERE id=?");
            if (!$stmt) {
                $error = "Prepare failed: " . $conn->error;
            } else {
                $stmt->bind_param("si", $webhook_url, $user_id);
                
                if ($stmt->execute()) {
                    $success = "Webhook URL updated successfully!";
                    $user['webhook_url'] = $webhook_url;
                } else {
                    $error = "Failed to update webhook URL: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

// Get API usage statistics
$usage_stats = [
    'total_calls' => 0,
    'success_calls' => 0,
    'error_calls' => 0,
    'last_used' => null
];

$usage_query = $conn->query("
    SELECT 
        COUNT(*) as total_calls,
        SUM(CASE WHEN status='success' THEN 1 ELSE 0 END) as success_calls,
        SUM(CASE WHEN status='error' THEN 1 ELSE 0 END) as error_calls,
        MAX(created_at) as last_used
    FROM api_logs 
    WHERE user_id='$user_id'
");

if ($usage_query) {
    $stats = $usage_query->fetch_assoc();
    if ($stats) {
        $usage_stats = $stats;
    }
}
?>

<div class="main-container">
    <div class="sidebar-container">
        <?php include_once 'navbar.php'; ?>
    </div>
    <div class="content-container">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">API Settings</h1>
        </div>

        <div class="card">
            <div class="card-header">
                <h4>API Settings</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <!-- API Key Section -->
                <div class="card mb-4">
                    <div class="card-header bg-dark text-white">
                        <h5>API Credentials</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Your API Key</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="apiKey" 
                                    value="<?php echo htmlspecialchars($user['api_key']); ?>" readonly>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" onclick="copyToClipboard('apiKey')" 
                                            type="button">
                                        <i class="fas fa-copy"></i> Copy
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted">
                                Keep this key secure. Do not share it publicly.
                            </small>
                        </div>
                        
                        <form method="POST">
                            <div class="alert alert-warning">
                                <strong>Warning:</strong> Regenerating your API key will invalidate the current key.
                                Any applications using the current key will stop working until updated.
                            </div>
                            <button type="submit" name="regenerate_key" class="btn btn-danger"
                                    onclick="return confirm('Are you sure you want to regenerate your API key?')">
                                <i class="fas fa-sync-alt"></i> Regenerate API Key
                            </button>
                        </form>
                    </div>
                </div>

                <!-- API Usage Statistics -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5>API Usage Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <div class="card">
                                    <div class="card-body">
                                        <h3><?php echo (int)$usage_stats['total_calls']; ?></h3>
                                        <p class="text-muted">Total API Calls</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="card">
                                    <div class="card-body">
                                        <h3 class="text-success"><?php echo (int)$usage_stats['success_calls']; ?></h3>
                                        <p class="text-muted">Successful Calls</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="card">
                                    <div class="card-body">
                                        <h3 class="text-danger"><?php echo (int)$usage_stats['error_calls']; ?></h3>
                                        <p class="text-muted">Failed Calls</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 text-center">
                            <small class="text-muted">
                                Last used: <?php echo $usage_stats['last_used'] ? date('M j, Y g:i A', strtotime($usage_stats['last_used'])) : 'Never'; ?>
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Webhook Settings -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5>Webhook Settings</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!$has_webhook_column): ?>
                            <div class="alert alert-warning">
                                Webhook functionality is not currently available. Please contact system administrator.
                            </div>
                        <?php else: ?>
                            <form method="POST">
                                <div class="form-group">
                                    <label>Webhook URL</label>
                                    <input type="url" name="webhook_url" class="form-control" 
                                        placeholder="https://yourdomain.com/webhook"
                                        value="<?php echo htmlspecialchars($user['webhook_url'] ?? ''); ?>">
                                    <small class="text-muted">
                                        We will send POST requests to this URL for payment notifications.
                                    </small>
                                </div>
                                <div class="form-group">
                                    <label>Webhook Secret</label>
                                    <input type="text" class="form-control" readonly
                                        value="<?php echo htmlspecialchars(substr($user['api_key'], 0, 16)); ?>">
                                    <small class="text-muted">
                                        Use part of your API key to verify webhook requests.
                                    </small>
                                </div>
                                <button type="submit" name="update_webhook" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Webhook Settings
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard(elementId) {
    var copyText = document.getElementById(elementId);
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    document.execCommand("copy");
    
    // Show feedback
    var originalText = event.target.innerHTML;
    event.target.innerHTML = '<i class="fas fa-check"></i> Copied!';
    setTimeout(function() {
        event.target.innerHTML = originalText;
    }, 2000);
}
</script>

<?php require_once 'footer.php'; ?>