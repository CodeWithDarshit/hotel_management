<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error_msg = "";
$success_msg = "";
$fullname = "";
$email = "";
$phone = "";

// Capture redirect parameters
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = sanitizeInput($_POST['fullname']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($fullname) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_msg = "Please fill in all required fields.";
    } elseif ($password !== $confirm_password) {
        $error_msg = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error_msg = "Password must be at least 6 characters long.";
    } else {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $error_msg = "This email address is already registered.";
            } else {
                // Insert new customer
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $insert_stmt = $pdo->prepare("INSERT INTO users (fullname, email, password, phone, role) VALUES (?, ?, ?, ?, 'customer')");
                $insert_stmt->execute([$fullname, $email, $hashed_password, $phone]);
                
                // Get newly created user details for auto-login
                $new_user_id = $pdo->lastInsertId();
                
                // Establish Session
                $_SESSION['user_id'] = $new_user_id;
                $_SESSION['user_name'] = $fullname;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = 'customer';
                
                $success_msg = "Registration successful! Logging you in...";
                
                // Redirect user
                if (!empty($redirect)) {
                    header("refresh:2;url=" . $redirect);
                } else {
                    header("refresh:2;url=profile.php");
                }
            }
        } catch (PDOException $e) {
            $error_msg = "Database Error: " . $e->getMessage();
        }
    }
}

require_once 'includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card" style="max-width: 500px;">
        <h2 class="auth-title">Create Account</h2>
        <p class="auth-subtitle">Register to begin booking luxury stays</p>
        
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger">
                <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success">
                <?php echo $success_msg; ?>
            </div>
        <?php endif; ?>
        
        <form action="register.php?redirect=<?php echo urlencode($redirect); ?>" method="POST">
            <div class="form-group">
                <label for="fullname">Full Name *</label>
                <input type="text" id="fullname" name="fullname" class="form-control" value="<?php echo htmlspecialchars($fullname); ?>" placeholder="John Doe" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" placeholder="john@example.com" required>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($phone); ?>" placeholder="+1 (555) 000-0000">
            </div>
            
            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Minimum 6 characters" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password *</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter password" required>
            </div>
            
            <button type="submit" class="btn-primary auth-btn">Register & Log In</button>
        </form>
        
        <p class="auth-redirect">Already have an account? <a href="login.php?redirect=<?php echo urlencode($redirect); ?>">Log In here</a></p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
