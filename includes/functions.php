<?php
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function sanitizeInput($data) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($data));
}

function displayError($error) {
    return '<div class="alert alert-danger">'.$error.'</div>';
}

function displaySuccess($message) {
    return '<div class="alert alert-success">'.$message.'</div>';
}
?>