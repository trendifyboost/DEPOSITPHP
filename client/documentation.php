<?php
require_once 'header.php';
$page_title = 'API Documentation';
?>

<div class="main-container">
    <div class="sidebar-container">
        <?php include_once 'navbar.php'; ?>
    </div>
    <div class="content-container">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">API Documentation</h1>
        </div>

        <div class="card">
            <div class="card-header">
                <h4>Welcome to the API Documentation</h4>
            </div>
            <div class="card-body">
                <p>This section will contain detailed information about how to use our API.</p>
                <p>Here you will find:</p>
                <ul>
                    <li>API Endpoints</li>
                    <li>Authentication Methods</li>
                    <li>Request and Response Formats</li>
                    <li>Error Codes</li>
                    <li>Example Usage</li>
                </ul>
                <p>Please check back later for updates, or contact support for more information.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>