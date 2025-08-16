<?php
require_once 'header.php';
$page_title = 'Settings';

if (isset($_POST['regenerate_api_key'])) {
    $new_api_key = md5(uniqid(rand(), true));
    $user_id = $_SESSION['user_id'];
    
    $sql = "UPDATE users SET api_key = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $new_api_key, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['api_key'] = $new_api_key;
        $success = "API key regenerated successfully";
    } else {
        $error = "Failed to regenerate API key";
    }
}
?>

<div class="main-container">
    <div class="sidebar-container">
        <?php include_once 'navbar.php'; ?>
    </div>
    <div class="content-container">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">Settings</h1>
        </div>

        <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
        <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
        <div class="card mb-4">
            <div class="card-header bg-danger text-white">
                <h5>Danger Zone</h5>
            </div>
            <div class="card-body">
                <form method="POST" onsubmit="return confirm('Are you sure? All applications using this API key will stop working!')">
                    <button type="submit" name="regenerate_api_key" class="btn btn-danger">
                        Regenerate API Key
                    </button>
                    <small class="text-muted d-block mt-2">
                        This will invalidate your current API key and generate a new one.
                    </small>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>