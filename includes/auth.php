<?php
// includes/auth.php

// User Login Function
function loginUser($email, $password) {
    global $conn;
    
    $email = sanitizeInput($email);
    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = $conn->query($sql);
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];
            return true;
        }
    }
    return false;
}

// User Registration Function (updated and single implementation)
function registerUser($name, $email, $password, $company, $website) {
    global $conn;
    
    // Generate API key
    $api_key = md5(uniqid(rand(), true));
    
    $name = sanitizeInput($name);
    $email = sanitizeInput($email);
    $password = password_hash(sanitizeInput($password), PASSWORD_DEFAULT);
    $company = sanitizeInput($company);
    $website = sanitizeInput($website);
    
    $sql = "INSERT INTO users (name, email, password, company_name, website_url, api_key) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $name, $email, $password, $company, $website, $api_key);
    
    if ($stmt->execute()) {
        return $api_key;
    }
    return false;
}

// Check if email exists
function emailExists($email) {
    global $conn;
    
    $email = sanitizeInput($email);
    $sql = "SELECT id FROM users WHERE email = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    return $stmt->num_rows > 0;
}

// Get user by API key
function getUserByApiKey($api_key) {
    global $conn;
    
    $api_key = sanitizeInput($api_key);
    $sql = "SELECT * FROM users WHERE api_key = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $api_key);
    $stmt->execute();
    
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}
?>