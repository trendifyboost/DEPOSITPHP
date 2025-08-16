<?php
require_once 'includes/config.php';

if (isLoggedIn()) {
    if ($_SESSION['user_type'] == 'admin') {
        redirect('admin/dashboard.php');
    } else {
        redirect('client/dashboard.php');
    }
} else {
    redirect('client/login.php');
}
?>