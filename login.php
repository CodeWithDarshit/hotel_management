<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error_msg = "";
$email = "";

// Capture dynamic redirects from details checkouts
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password']; // Don't sanitize password to preserve special characters
    
    if (empty($email) || empty($password)) {
        $error_msg = "Please enter both email and password.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Set Session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['fullname'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
                // Redirect user
                if (!empty($redirect)) {
                    header("Location: " . $redirect);
                } else {
                    if ($user['role'] === 'admin') {
                        header("Location: admin/index.php");
                    } else {
                        header("Location: profile.php");
                    }
                }
                exit;
            } else {
                $error_msg = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            $error_msg = "Database Error: " . $e->getMessage();
        }
    }
}

require_once 'includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <h2 class="auth-title">Welcome Back</h2>
        <p class="auth-subtitle">Sign in to your luxury dashboard</p>
        
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger">
                <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>
        
        <form action="login.php?redirect=<?php echo urlencode($redirect); ?>" method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" placeholder="name@domain.com" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            
            <button type="submit" class="btn-primary auth-btn">Log In</button>
        </form>
        
        <p class="auth-redirect">Don't have an account? <a href="register.php?redirect=<?php echo urlencode($redirect); ?>">Register here</a></p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
