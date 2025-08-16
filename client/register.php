<?php
$page_title = 'Register - ' . SITE_NAME;
require_once 'header.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $company = trim($_POST['company']);
    $website = trim($_POST['website']);

    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password) || empty($company)) {
        $error = 'All fields are required';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } else {
        // Check if email already exists
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $error = 'Email already registered';
        } else {
            // Generate API key
            $api_key = md5(uniqid(rand(), true));
            
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert into database
            $sql = "INSERT INTO users (name, email, password, company_name, website_url, api_key) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssss", $name, $email, $hashed_password, $company, $website, $api_key);
            
            if ($stmt->execute()) {
                $success = 'Registration successful! Your API key has been generated.';
                
                // Send email with API key (optional)
                // sendRegistrationEmail($email, $name, $api_key);
                
                // Redirect to login after 5 seconds
                header("refresh:5;url=login.php");
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
        $stmt->close();
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="text-center">Register for Bd Auto Pay Solution</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?php echo $success; ?>
                            <p class="mt-3"><strong>Your API Key:</strong> <code><?php echo $api_key; ?></code></p>
                            <p class="text-danger">Please save this API key securely. You won't be able to see it again.</p>
                        </div>
                    <?php else: ?>
                        <form method="POST">
                            <div class="form-group">
                                <label>Full Name</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Company Name</label>
                                <input type="text" name="company" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Website URL (optional)</label>
                                <input type="url" name="website" class="form-control">
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Register</button>
                        </form>
                        <div class="text-center mt-3">
                            Already have an account? <a href="login.php">Login here</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>