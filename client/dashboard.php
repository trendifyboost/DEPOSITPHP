<?php
require_once 'header.php';
$page_title = 'Dashboard';

// Get user details
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id='$user_id'";
$user = $conn->query($sql)->fetch_assoc();
?>

<div class="main-container">
    <div class="sidebar-container">
        <?php include_once 'navbar.php'; ?>
    </div>
    <div class="content-container">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">Dashboard</h1>
        </div>

<div class="dashboard-container">
    <!-- Balance and API Key Cards -->
    <div class="card-grid">
        <!-- Balance Card -->
        <div class="dashboard-card balance-card">
            <div class="card-content">
                <div class="card-icon">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="card-details">
                    <span class="card-label">Current Balance</span>
                    <h2 class="card-value">৳<?php echo number_format($user['balance'], 2); ?></h2>
                </div>
            </div>
            <div class="card-actions">
                <button class="action-btn">
                    <i class="fas fa-plus-circle"></i> Add Funds
                </button>
                <button class="action-btn">
                    <i class="fas fa-exchange-alt"></i> Transfer
                </button>
            </div>
        </div>

        <!-- API Key Card -->
        <div class="dashboard-card api-card">
            <div class="card-content">
                <div class="card-icon">
                    <i class="fas fa-key"></i>
                </div>
                <div class="card-details">
                    <span class="card-label">API Key</span>
                    <div class="api-key-wrapper">
                        <span class="api-key-preview"><?php echo substr($user['api_key'], 0, 6) . '••••••••' . substr($user['api_key'], -6); ?></span>
                        <button class="copy-btn" onclick="toggleApiKey()">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-actions">
                <button class="action-btn" onclick="copyApiKey()">
                    <i class="fas fa-copy"></i> Copy Key
                </button>
                <button class="action-btn">
                    <i class="fas fa-redo"></i> Regenerate
                </button>
            </div>
        </div>
    </div>

    <!-- API Key Management Section -->
    <div class="api-management-card">
        <div class="management-header">
            <h3><i class="fas fa-lock"></i> API Key Management</h3>
            <a href="#" class="docs-link">
                <i class="fas fa-book"></i> API Documentation
            </a>
        </div>
        
        <div class="security-alert">
            <i class="fas fa-shield-alt"></i>
            <p>This key provides full access to your account. Keep it secure and never share it publicly.</p>
        </div>
        
        <div class="api-key-container">
            <div class="input-group">
                <input type="password" id="fullApiKey" class="api-key-input" 
                       value="<?php echo $_SESSION['api_key']; ?>" readonly>
                <button class="copy-btn large" onclick="copyApiKey()">
                    <i class="fas fa-copy"></i> Copy
                </button>
            </div>
            
            <div class="action-buttons">
                <button class="btn secondary" onclick="toggleApiKey()">
                    <i class="fas fa-eye"></i> Show Key
                </button>
                <button class="btn danger">
                    <i class="fas fa-sync-alt"></i> Regenerate
                </button>
            </div>
        </div>
    </div>
</div>


<script>
function copyApiKey() {
    const apiKey = document.getElementById("fullApiKey");
    apiKey.select();
    document.execCommand("copy");
    
    // Show notification
    alert("API key copied to clipboard!");
}

function toggleApiKey() {
    const input = document.getElementById("fullApiKey");
    const preview = document.querySelector(".api-key-preview");
    const toggleBtns = document.querySelectorAll("[onclick='toggleApiKey()']");
    
    if (input.type === "password") {
        input.type = "text";
        preview.textContent = "<?php echo $_SESSION['api_key']; ?>";
        toggleBtns.forEach(btn => {
            btn.innerHTML = '<i class="fas fa-eye-slash"></i>' + 
                            (btn.classList.contains('large') ? ' Hide Key' : '');
        });
    } else {
        input.type = "password";
        preview.textContent = "<?php echo substr($_SESSION['api_key'], 0, 6) . '••••••••' . substr($_SESSION['api_key'], -6); ?>";
        toggleBtns.forEach(btn => {
            btn.innerHTML = '<i class="fas fa-eye"></i>' + 
                           (btn.classList.contains('large') ? ' Show Key' : '');
        });
    }
}
</script>

<?php require_once 'footer.php'; ?>